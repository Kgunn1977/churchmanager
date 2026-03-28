<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$db     = getDB();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    // ── GET floors for a building ─────────────────────────────
    case 'get_floors':
        $bid = (int)($_GET['building_id'] ?? 0);
        if (!$bid) { echo json_encode([]); exit; }
        $stmt = $db->prepare("SELECT id, name FROM floors WHERE building_id = ? ORDER BY floor_order, name");
        $stmt->execute([$bid]);
        echo json_encode($stmt->fetchAll());
        break;

    // ── GET rooms for a floor ─────────────────────────────────
    case 'get_rooms':
        $fid = (int)($_GET['floor_id'] ?? 0);
        if (!$fid) { echo json_encode([]); exit; }
        $stmt = $db->prepare("SELECT id, name, room_number FROM rooms WHERE floor_id = ? ORDER BY room_number, name");
        $stmt->execute([$fid]);
        echo json_encode($stmt->fetchAll());
        break;

    // ── GET dates with reservations (calendar dots) ───────────
    case 'get_calendar_dots':
        $year  = (int)($_GET['year']  ?? date('Y'));
        $month = (int)($_GET['month'] ?? date('n'));
        $start = sprintf('%04d-%02d-01', $year, $month);
        $end   = date('Y-m-t', strtotime($start));
        $rawIds = trim($_GET['room_ids'] ?? '');

        if ($rawIds !== '') {
            $ids = array_values(array_filter(array_map('intval', explode(',', $rawIds))));
            if ($ids) {
                $ph   = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $db->prepare("
                    SELECT DISTINCT DATE(r.start_datetime) AS d
                    FROM reservations r
                    JOIN reservation_rooms rr ON rr.reservation_id = r.id
                    WHERE DATE(r.start_datetime) BETWEEN ? AND ?
                      AND rr.room_id IN ($ph)
                    ORDER BY d
                ");
                $stmt->execute(array_merge([$start, $end], $ids));
            } else {
                echo json_encode([]); exit;
            }
        } else {
            $stmt = $db->prepare("
                SELECT DISTINCT DATE(start_datetime) AS d
                FROM reservations
                WHERE DATE(start_datetime) BETWEEN ? AND ?
                ORDER BY d
            ");
            $stmt->execute([$start, $end]);
        }
        echo json_encode(array_column($stmt->fetchAll(), 'd'));
        break;

    // ── GET reservations for a date ───────────────────────────
    case 'get_reservations':
        $date   = $_GET['date'] ?? date('Y-m-d');
        $rawIds = trim($_GET['room_ids'] ?? '');

        $base = "
            SELECT r.*,
                   o.name AS organization_name,
                   GROUP_CONCAT(CONCAT(rm.id,':',rm.name,':',fl.name,':',b.name)
                                ORDER BY rm.name SEPARATOR '|') AS rooms_raw
            FROM reservations r
            LEFT JOIN organizations o       ON o.id  = r.organization_id
            JOIN  reservation_rooms rr      ON rr.reservation_id = r.id
            JOIN  rooms rm                  ON rm.id = rr.room_id
            JOIN  floors fl                 ON fl.id = rm.floor_id
            JOIN  buildings b               ON b.id  = fl.building_id
            WHERE DATE(r.start_datetime) = ?
        ";

        if ($rawIds !== '') {
            $ids = array_values(array_filter(array_map('intval', explode(',', $rawIds))));
            if ($ids) {
                $ph   = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $db->prepare($base . " AND rr.room_id IN ($ph) GROUP BY r.id ORDER BY r.start_datetime");
                $stmt->execute(array_merge([$date], $ids));
            } else {
                echo json_encode([]); exit;
            }
        } else {
            $stmt = $db->prepare($base . " GROUP BY r.id ORDER BY r.start_datetime");
            $stmt->execute([$date]);
        }

        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $rooms = [];
            if ($row['rooms_raw']) {
                foreach (explode('|', $row['rooms_raw']) as $ri) {
                    [$rid, $rname, $fname, $bname] = explode(':', $ri, 4);
                    $rooms[] = ['id' => (int)$rid, 'name' => $rname, 'floor' => $fname, 'building' => $bname];
                }
            }
            $row['rooms'] = $rooms;
            unset($row['rooms_raw']);
        }
        echo json_encode(array_values($rows));
        break;

    // ── GET single reservation (for edit modal) ───────────────
    case 'get_reservation':
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { echo json_encode(['error' => 'ID required']); exit; }
        $stmt = $db->prepare("
            SELECT r.*, o.name AS organization_name
            FROM reservations r
            LEFT JOIN organizations o ON o.id = r.organization_id
            WHERE r.id = ?
        ");
        $stmt->execute([$id]);
        $res = $stmt->fetch();
        if (!$res) { echo json_encode(['error' => 'Not found']); exit; }
        $rStmt = $db->prepare("SELECT rm.id, rm.name FROM reservation_rooms rr JOIN rooms rm ON rm.id = rr.room_id WHERE rr.reservation_id = ?");
        $rStmt->execute([$id]);
        $res['rooms'] = $rStmt->fetchAll();
        echo json_encode($res);
        break;

    // ── GET organizations (autocomplete) ─────────────────────
    case 'get_organizations':
        $q = trim($_GET['q'] ?? '');
        if ($q !== '') {
            $stmt = $db->prepare("SELECT id, name FROM organizations WHERE name LIKE ? ORDER BY name LIMIT 20");
            $stmt->execute(['%' . $q . '%']);
        } else {
            $stmt = $db->query("SELECT id, name FROM organizations ORDER BY name LIMIT 20");
        }
        echo json_encode($stmt->fetchAll());
        break;

    // ── POST create organization ──────────────────────────────
    case 'create_organization':
        $name = trim($_POST['name'] ?? '');
        if (!$name) { echo json_encode(['error' => 'Name required']); exit; }
        try {
            $stmt = $db->prepare("INSERT INTO organizations (name) VALUES (?)");
            $stmt->execute([$name]);
            echo json_encode(['id' => (int)$db->lastInsertId(), 'name' => $name]);
        } catch (PDOException $e) {
            // Duplicate — return existing
            $stmt = $db->prepare("SELECT id, name FROM organizations WHERE name = ?");
            $stmt->execute([$name]);
            echo json_encode($stmt->fetch());
        }
        break;

    // ── POST save reservation (create or update) ──────────────
    case 'save_reservation':
        $id          = (int)($_POST['id'] ?? 0);
        $title       = trim($_POST['title'] ?? '') ?: null;
        $orgId       = ($_POST['organization_id'] ?? '') !== '' ? (int)$_POST['organization_id'] : null;
        $startDt     = $_POST['start_datetime'] ?? '';
        $endDt       = $_POST['end_datetime']   ?? '';
        $notes       = trim($_POST['notes'] ?? '') ?: null;
        $isRecurring = (int)($_POST['is_recurring'] ?? 0);
        $recurRule   = $isRecurring ? ($_POST['recurrence_rule'] ?? null)      : null;
        $recurEnd    = $isRecurring ? ($_POST['recurrence_end_date'] ?? null)   : null;
        $rawIds      = $_POST['room_ids'] ?? '';
        $roomIds     = array_values(array_filter(array_map('intval', explode(',', $rawIds))));
        $userId      = getCurrentUser()['id'] ?? null;

        if (!$startDt || !$endDt) {
            echo json_encode(['error' => 'Start and end times are required']); exit;
        }
        if (empty($roomIds)) {
            echo json_encode(['error' => 'At least one room must be selected']); exit;
        }

        if ($id) {
            $stmt = $db->prepare("
                UPDATE reservations
                SET title=?, organization_id=?, start_datetime=?, end_datetime=?,
                    notes=?, is_recurring=?, recurrence_rule=?, recurrence_end_date=?
                WHERE id=?
            ");
            $stmt->execute([$title, $orgId, $startDt, $endDt, $notes, $isRecurring, $recurRule, $recurEnd, $id]);
            $db->prepare("DELETE FROM reservation_rooms WHERE reservation_id=?")->execute([$id]);
        } else {
            $stmt = $db->prepare("
                INSERT INTO reservations
                    (title, organization_id, start_datetime, end_datetime, notes,
                     is_recurring, recurrence_rule, recurrence_end_date, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$title, $orgId, $startDt, $endDt, $notes, $isRecurring, $recurRule, $recurEnd, $userId]);
            $id = (int)$db->lastInsertId();
        }

        $rrStmt = $db->prepare("INSERT INTO reservation_rooms (reservation_id, room_id) VALUES (?, ?)");
        foreach ($roomIds as $rid) {
            $rrStmt->execute([$id, $rid]);
        }

        echo json_encode(['success' => true, 'id' => $id]);
        break;

    // ── POST delete reservation ───────────────────────────────
    case 'delete_reservation':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['error' => 'ID required']); exit; }
        $db->prepare("DELETE FROM reservations WHERE id=?")->execute([$id]);
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['error' => 'Unknown action: ' . htmlspecialchars($action)]);
}
