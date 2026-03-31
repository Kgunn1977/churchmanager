<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$db     = getDB();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ── Recurrence helper ─────────────────────────────────────────────────────────
// Returns array of 'Y-m-d' dates that a recurring reservation falls on
// within [$rangeStart, $rangeEnd] (both inclusive, Y-m-d strings).
// Skips dates listed in $exceptions (array of Y-m-d strings).
// If $recurEnd is null the rule repeats indefinitely through $rangeEnd.
function recurOccurrences(string $rule, string $startDt, ?string $recurEnd, string $rangeStart, string $rangeEnd, array $exceptions = []): array {
    $originDate = (new DateTime($startDt))->setTime(0, 0, 0);
    $rsDate     = new DateTime($rangeStart);
    $reDate     = new DateTime($rangeEnd);
    $exSet      = array_flip($exceptions);

    // hard cap: never iterate past rangeEnd (or recurEnd if set)
    $hardEnd = $reDate;
    if ($recurEnd !== null && $recurEnd !== '' && $recurEnd !== '0000-00-00') {
        $rEnd = new DateTime($recurEnd);
        if ($rEnd < $hardEnd) $hardEnd = $rEnd;
    }

    $parts = explode(':', $rule);
    $freq  = $parts[0]; // daily | weekly | biweekly | monthly

    $results = [];

    // helper: add date if in range and not excepted
    $add = function(DateTime $d) use ($rsDate, $hardEnd, &$exSet, &$results) {
        if ($d < $rsDate || $d > $hardEnd) return;
        $key = $d->format('Y-m-d');
        if (!isset($exSet[$key])) $results[] = $key;
    };

    if ($freq === 'daily') {
        $cur = clone $originDate;
        while ($cur <= $hardEnd) {
            $add($cur);
            $cur->modify('+1 day');
        }
    }

    elseif ($freq === 'weekly' || $freq === 'biweekly') {
        // parts[1] = comma-separated day codes: SUN,MON,TUE,WED,THU,FRI,SAT
        $dayMap = ['SUN'=>0,'MON'=>1,'TUE'=>2,'WED'=>3,'THU'=>4,'FRI'=>5,'SAT'=>6];
        $targetDays = [];
        if (!empty($parts[1])) {
            foreach (explode(',', $parts[1]) as $dc) {
                $dc = strtoupper(trim($dc));
                if (isset($dayMap[$dc])) $targetDays[] = $dayMap[$dc];
            }
        }
        if (empty($targetDays)) $targetDays = [(int)$originDate->format('w')];

        $step = ($freq === 'biweekly') ? 14 : 7;

        // Find the Monday of origin's week as anchor
        $anchor = clone $originDate;
        $dow = (int)$anchor->format('w');
        $anchor->modify("-{$dow} days"); // rewind to Sunday

        // walk week by week from anchor
        $weekStart = clone $anchor;
        while ($weekStart <= $hardEnd) {
            foreach ($targetDays as $td) {
                $d = clone $weekStart;
                $d->modify("+{$td} days");
                if ($d >= $originDate) $add($d);
            }
            $weekStart->modify("+{$step} days");
        }
    }

    elseif ($freq === 'monthly') {
        // monthly:day:N  — day N of each month
        // monthly:nth:first|second|third|fourth|last:DAYCODE
        $subtype = $parts[1] ?? 'day';

        if ($subtype === 'day') {
            $dayNum = isset($parts[2]) ? (int)$parts[2] : (int)$originDate->format('j');
            $cur = clone $originDate;
            $cur->setDate((int)$cur->format('Y'), (int)$cur->format('n'), 1);
            while ($cur <= $hardEnd) {
                $daysInMonth = (int)$cur->format('t');
                $target = clone $cur;
                $target->setDate((int)$cur->format('Y'), (int)$cur->format('n'), min($dayNum, $daysInMonth));
                if ($target >= $originDate) $add($target);
                $cur->modify('+1 month');
            }
        } else {
            // nth weekday: parts[2]=first|second|third|fourth|last  parts[3]=DAYCODE
            $nthWord = $parts[2] ?? 'first';
            $dayCode = strtoupper($parts[3] ?? 'SUN');
            $dayMap  = ['SUN'=>'Sunday','MON'=>'Monday','TUE'=>'Tuesday','WED'=>'Wednesday',
                        'THU'=>'Thursday','FRI'=>'Friday','SAT'=>'Saturday'];
            $dayName = $dayMap[$dayCode] ?? 'Sunday';

            // PHP's relative format: "first Sunday of March 2026"
            $nthMap = ['first'=>'first','second'=>'second','third'=>'third','fourth'=>'fourth','last'=>'last'];
            $nthStr = $nthMap[$nthWord] ?? 'first';

            $cur = clone $originDate;
            $cur->setDate((int)$cur->format('Y'), (int)$cur->format('n'), 1);
            while ($cur <= $hardEnd) {
                $monthStr = $cur->format('F Y');
                $target   = new DateTime("{$nthStr} {$dayName} of {$monthStr}");
                if ($target >= $originDate) $add($target);
                $cur->modify('+1 month');
            }
        }
    }

    sort($results);
    return array_values(array_unique($results));
}

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

        // 1) Non-recurring one-off dates
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
        $dotSet = array_flip(array_column($stmt->fetchAll(), 'd'));

        // 2) Expand recurring reservations into this month
        if ($rawIds !== '' && !empty($ids)) {
            $ph2  = implode(',', array_fill(0, count($ids), '?'));
            $rStmt = $db->prepare("
                SELECT r.id, r.start_datetime, r.recurrence_rule, r.recurrence_end_date
                FROM reservations r
                JOIN reservation_rooms rr ON rr.reservation_id = r.id
                WHERE r.is_recurring = 1
                  AND rr.room_id IN ($ph2)
                GROUP BY r.id
            ");
            $rStmt->execute($ids);
        } else {
            $rStmt = $db->prepare("
                SELECT id, start_datetime, recurrence_rule, recurrence_end_date
                FROM reservations
                WHERE is_recurring = 1
            ");
            $rStmt->execute();
        }
        $recurring = $rStmt->fetchAll();
        foreach ($recurring as $rec) {
            // Load exceptions for this reservation
            $exStmt = $db->prepare("SELECT exception_date FROM reservation_exceptions WHERE reservation_id = ?");
            $exStmt->execute([$rec['id']]);
            $exceptions = array_column($exStmt->fetchAll(), 'exception_date');

            $dates = recurOccurrences(
                $rec['recurrence_rule'],
                $rec['start_datetime'],
                $rec['recurrence_end_date'],
                $start,
                $end,
                $exceptions
            );
            foreach ($dates as $d) { $dotSet[$d] = 1; }
        }

        $allDots = array_keys($dotSet);
        sort($allDots);
        echo json_encode(array_values($allDots));
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

        // Also find recurring reservations that expand onto this date
        $recurBase = "
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
            WHERE r.is_recurring = 1
              AND DATE(r.start_datetime) != ?
        ";

        if ($rawIds !== '' && !empty($ids)) {
            $ph2 = implode(',', array_fill(0, count($ids), '?'));
            $rStmt = $db->prepare($recurBase . " AND rr.room_id IN ($ph2) GROUP BY r.id");
            $rStmt->execute(array_merge([$date], $ids));
        } else {
            $rStmt = $db->prepare($recurBase . " GROUP BY r.id");
            $rStmt->execute([$date]);
        }

        $recurRows = $rStmt->fetchAll();
        $seenIds = array_column($rows, 'id');

        foreach ($recurRows as $rec) {
            if (in_array($rec['id'], $seenIds)) continue; // already in results

            // Load exceptions
            $exStmt = $db->prepare("SELECT exception_date FROM reservation_exceptions WHERE reservation_id = ?");
            $exStmt->execute([$rec['id']]);
            $exceptions = array_column($exStmt->fetchAll(), 'exception_date');

            $dates = recurOccurrences(
                $rec['recurrence_rule'],
                $rec['start_datetime'],
                $rec['recurrence_end_date'],
                $date, $date,
                $exceptions
            );

            if (in_array($date, $dates)) {
                // Build a virtual instance: same times, shifted to this date
                $origTime = substr($rec['start_datetime'], 11);
                $endTime  = substr($rec['end_datetime'], 11);
                $rec['start_datetime']    = $date . ' ' . $origTime;
                $rec['end_datetime']      = $date . ' ' . $endTime;
                $rec['_virtual_date']     = $date;      // flag for the front-end
                $rec['_parent_id']        = $rec['id']; // same as id for recurring masters
                $rows[] = $rec;
            }
        }

        // Parse rooms_raw for all rows
        foreach ($rows as &$row) {
            $rooms = [];
            if (!empty($row['rooms_raw'])) {
                foreach (explode('|', $row['rooms_raw']) as $ri) {
                    [$rid, $rname, $fname, $bname] = explode(':', $ri, 4);
                    $rooms[] = ['id' => (int)$rid, 'name' => $rname, 'floor' => $fname, 'building' => $bname];
                }
            }
            $row['rooms'] = $rooms;
            unset($row['rooms_raw']);
        }

        // Sort by start time
        usort($rows, fn($a, $b) => strcmp($a['start_datetime'], $b['start_datetime']));
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
        $recurEndRaw = $isRecurring ? trim($_POST['recurrence_end_date'] ?? '') : '';
        $recurEnd    = ($recurEndRaw !== '' && $recurEndRaw !== '0000-00-00') ? $recurEndRaw : null;
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

    // ── POST delete reservation (all instances / non-recurring) ─
    case 'delete_reservation':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['error' => 'ID required']); exit; }
        $db->prepare("DELETE FROM reservations WHERE id=?")->execute([$id]);
        echo json_encode(['success' => true]);
        break;

    // ── POST delete single recurring instance (add exception) ──
    case 'delete_recurring_instance':
        $id   = (int)($_POST['id'] ?? 0);
        $date = trim($_POST['date'] ?? '');
        if (!$id || !$date) { echo json_encode(['error' => 'ID and date required']); exit; }
        try {
            $stmt = $db->prepare("INSERT INTO reservation_exceptions (reservation_id, exception_date) VALUES (?, ?)");
            $stmt->execute([$id, $date]);
        } catch (PDOException $e) {
            // duplicate — already excepted, that's fine
        }
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['error' => 'Unknown action: ' . htmlspecialchars($action)]);
}
