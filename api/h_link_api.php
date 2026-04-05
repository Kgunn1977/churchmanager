<?php
/**
 * H-Link API — Horizontal room linking (combinable partitions)
 *
 * Actions:
 *   get_groups          — all H-Link groups with members + combinations
 *   create_group        — create a new H-Link group from selected rooms
 *   delete_group        — remove an entire H-Link group (+ virtual rooms)
 *   add_combination     — add a named combination to an existing group
 *   delete_combination  — remove one combination (+ its virtual room)
 *   get_conflicts       — check reservation conflicts for H-Link rooms
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST)) {
    $_POST = json_decode(file_get_contents('php://input'), true) ?: [];
}
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    // ── get_groups ─────────────────────────────────────────────────
    case 'get_groups': {
        $rows = $db->query("
            SELECT g.id, g.name, g.floor_id
            FROM h_link_groups g
            ORDER BY g.name
        ")->fetchAll();

        $groups = [];
        foreach ($rows as $g) {
            $gid = (int)$g['id'];

            // Members
            $mStmt = $db->prepare("SELECT room_id FROM h_link_group_members WHERE group_id = ?");
            $mStmt->execute([$gid]);
            $memberIds = array_map('intval', array_column($mStmt->fetchAll(), 'room_id'));

            // Combinations
            $cStmt = $db->prepare("
                SELECT c.id, c.name, c.virtual_room_id
                FROM h_link_combinations c
                WHERE c.group_id = ?
                ORDER BY (SELECT COUNT(*) FROM h_link_combination_rooms cr WHERE cr.combination_id = c.id) DESC, c.name
            ");
            $cStmt->execute([$gid]);
            $combos = [];
            foreach ($cStmt->fetchAll() as $c) {
                $crStmt = $db->prepare("SELECT room_id FROM h_link_combination_rooms WHERE combination_id = ?");
                $crStmt->execute([$c['id']]);
                $comboRoomIds = array_map('intval', array_column($crStmt->fetchAll(), 'room_id'));
                $combos[] = [
                    'id'              => (int)$c['id'],
                    'name'            => $c['name'],
                    'virtual_room_id' => $c['virtual_room_id'] ? (int)$c['virtual_room_id'] : null,
                    'room_ids'        => $comboRoomIds,
                ];
            }

            $groups[] = [
                'id'        => $gid,
                'name'      => $g['name'],
                'floor_id'  => (int)$g['floor_id'],
                'room_ids'  => $memberIds,
                'combinations' => $combos,
            ];
        }
        echo json_encode($groups);
        break;
    }

    // ── create_group ───────────────────────────────────────────────
    // POST: name, floor_id, room_ids (JSON array, min 2)
    case 'create_group': {
        if (!isAdmin()) { echo json_encode(['error' => 'Admin only']); break; }

        $name    = trim($_POST['name'] ?? '');
        $floorId = intval($_POST['floor_id'] ?? 0);
        $rawIds  = $_POST['room_ids'] ?? '[]';
        $roomIds = is_array($rawIds) ? $rawIds : json_decode($rawIds, true);
        $roomIds = array_map('intval', $roomIds ?: []);

        if (!$name)              { echo json_encode(['error' => 'Name is required']); break; }
        if ($floorId < 1)        { echo json_encode(['error' => 'floor_id is required']); break; }
        if (count($roomIds) < 2) { echo json_encode(['error' => 'At least 2 rooms required']); break; }

        // Verify rooms exist and are on the same floor
        $ph = implode(',', array_fill(0, count($roomIds), '?'));
        $stmt = $db->prepare("SELECT id, floor_id FROM rooms WHERE id IN ($ph)");
        $stmt->execute($roomIds);
        $rooms = $stmt->fetchAll();
        if (count($rooms) !== count($roomIds)) {
            echo json_encode(['error' => 'One or more rooms not found']); break;
        }
        foreach ($rooms as $r) {
            if ((int)$r['floor_id'] !== $floorId) {
                echo json_encode(['error' => 'All rooms must be on the same floor']); break 2;
            }
        }

        // Check none are already in an H-Link group
        $stmt = $db->prepare("SELECT room_id FROM h_link_group_members WHERE room_id IN ($ph)");
        $stmt->execute($roomIds);
        if ($stmt->fetch()) {
            echo json_encode(['error' => 'One or more rooms are already in an H-Link group']); break;
        }

        $db->beginTransaction();
        try {
            $stmt = $db->prepare("INSERT INTO h_link_groups (name, floor_id) VALUES (?, ?)");
            $stmt->execute([$name, $floorId]);
            $groupId = (int)$db->lastInsertId();

            $ins = $db->prepare("INSERT INTO h_link_group_members (group_id, room_id) VALUES (?, ?)");
            foreach ($roomIds as $rid) {
                $ins->execute([$groupId, (int)$rid]);
            }

            $db->commit();
            echo json_encode(['success' => true, 'group_id' => $groupId]);
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
    }

    // ── delete_group ───────────────────────────────────────────────
    // POST: group_id
    case 'delete_group': {
        if (!isAdmin()) { echo json_encode(['error' => 'Admin only']); break; }

        $groupId = intval($_POST['group_id'] ?? 0);
        if (!$groupId) { echo json_encode(['error' => 'group_id required']); break; }

        $db->beginTransaction();
        try {
            // Delete all virtual rooms created for this group's combinations
            $stmt = $db->prepare("
                SELECT virtual_room_id FROM h_link_combinations
                WHERE group_id = ? AND virtual_room_id IS NOT NULL
            ");
            $stmt->execute([$groupId]);
            $virtualIds = array_column($stmt->fetchAll(), 'virtual_room_id');

            foreach ($virtualIds as $vid) {
                $db->prepare("DELETE FROM rooms WHERE id = ?")->execute([$vid]);
            }

            // Cascade deletes handle h_link_combinations, h_link_combination_rooms, h_link_group_members
            $db->prepare("DELETE FROM h_link_groups WHERE id = ?")->execute([$groupId]);
            $db->commit();
            echo json_encode(['success' => true, 'deleted_virtual_rooms' => array_map('intval', $virtualIds)]);
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
    }

    // ── add_combination ────────────────────────────────────────────
    // POST: group_id, name, room_ids (JSON array — subset of group members)
    case 'add_combination': {
        if (!isAdmin()) { echo json_encode(['error' => 'Admin only']); break; }

        $groupId = intval($_POST['group_id'] ?? 0);
        $name    = trim($_POST['name'] ?? '');
        $rawIds  = $_POST['room_ids'] ?? '[]';
        $roomIds = is_array($rawIds) ? $rawIds : json_decode($rawIds, true);
        $roomIds = array_map('intval', $roomIds ?: []);

        if (!$groupId) { echo json_encode(['error' => 'group_id required']); break; }
        if (!$name)    { echo json_encode(['error' => 'Name is required']); break; }
        if (count($roomIds) < 2) { echo json_encode(['error' => 'At least 2 rooms for a combination']); break; }

        // Verify group exists
        $stmt = $db->prepare("SELECT floor_id FROM h_link_groups WHERE id = ?");
        $stmt->execute([$groupId]);
        $group = $stmt->fetch();
        if (!$group) { echo json_encode(['error' => 'Group not found']); break; }

        // Verify all room_ids are members of this group
        $ph = implode(',', array_fill(0, count($roomIds), '?'));
        $stmt = $db->prepare("SELECT room_id FROM h_link_group_members WHERE group_id = ? AND room_id IN ($ph)");
        $stmt->execute(array_merge([$groupId], $roomIds));
        $valid = array_column($stmt->fetchAll(), 'room_id');
        if (count($valid) !== count($roomIds)) {
            echo json_encode(['error' => 'Some rooms are not members of this group']); break;
        }

        $db->beginTransaction();
        try {
            // Create virtual room — merge capacity from member rooms
            $stmt = $db->prepare("SELECT COALESCE(SUM(capacity), 0) AS total_cap FROM rooms WHERE id IN ($ph)");
            $stmt->execute($roomIds);
            $totalCap = (int)$stmt->fetchColumn();

            // Merge polygons: collect all map_points from member rooms
            $stmt = $db->prepare("SELECT id, map_points FROM rooms WHERE id IN ($ph)");
            $stmt->execute($roomIds);
            $memberRooms = $stmt->fetchAll();
            $mergedPoints = _mergePolygons($memberRooms);

            // Create virtual room on same floor
            $floorId = (int)$group['floor_id'];
            $abbr = _autoAbbrev($name);
            $stmt = $db->prepare("
                INSERT INTO rooms (floor_id, name, abbreviation, capacity, map_points, is_reservable, is_storage, is_virtual)
                VALUES (?, ?, ?, ?, ?, 1, 0, 1)
            ");
            $stmt->execute([$floorId, $name, $abbr, $totalCap, $mergedPoints ? json_encode($mergedPoints) : null]);
            $virtualRoomId = (int)$db->lastInsertId();

            // Create the combination record
            $stmt = $db->prepare("INSERT INTO h_link_combinations (group_id, name, virtual_room_id) VALUES (?, ?, ?)");
            $stmt->execute([$groupId, $name, $virtualRoomId]);
            $comboId = (int)$db->lastInsertId();

            // Link combination to member rooms
            $ins = $db->prepare("INSERT INTO h_link_combination_rooms (combination_id, room_id) VALUES (?, ?)");
            foreach ($roomIds as $rid) {
                $ins->execute([$comboId, (int)$rid]);
            }

            // Update rooms.h_link_combination_id for the virtual room
            $db->prepare("UPDATE rooms SET h_link_combination_id = ? WHERE id = ?")->execute([$comboId, $virtualRoomId]);

            $db->commit();
            echo json_encode([
                'success'         => true,
                'combination_id'  => $comboId,
                'virtual_room_id' => $virtualRoomId,
            ]);
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
    }

    // ── delete_combination ─────────────────────────────────────────
    // POST: combination_id
    case 'delete_combination': {
        if (!isAdmin()) { echo json_encode(['error' => 'Admin only']); break; }

        $comboId = intval($_POST['combination_id'] ?? 0);
        if (!$comboId) { echo json_encode(['error' => 'combination_id required']); break; }

        $db->beginTransaction();
        try {
            // Get virtual room id before deleting
            $stmt = $db->prepare("SELECT virtual_room_id, group_id FROM h_link_combinations WHERE id = ?");
            $stmt->execute([$comboId]);
            $combo = $stmt->fetch();
            if (!$combo) { echo json_encode(['error' => 'Combination not found']); $db->rollBack(); break; }

            $virtualId = $combo['virtual_room_id'] ? (int)$combo['virtual_room_id'] : null;
            $groupId   = (int)$combo['group_id'];

            // Delete virtual room
            if ($virtualId) {
                $db->prepare("DELETE FROM reservation_rooms WHERE room_id = ?")->execute([$virtualId]);
                $db->prepare("DELETE FROM rooms WHERE id = ?")->execute([$virtualId]);
            }

            // Cascade deletes h_link_combination_rooms
            $db->prepare("DELETE FROM h_link_combinations WHERE id = ?")->execute([$comboId]);

            // If group now has zero combinations, that's fine — group still exists with members

            $db->commit();
            echo json_encode(['success' => true, 'deleted_virtual_room' => $virtualId]);
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
    }

    // ── get_conflicts ──────────────────────────────────────────────
    // GET: room_ids (comma-sep), date, start_time, end_time
    // Returns reservations that conflict with any of the given rooms
    // (including rooms in overlapping H-Link combinations)
    case 'get_conflicts': {
        $rawIds   = trim($_GET['room_ids'] ?? '');
        $date     = $_GET['date'] ?? date('Y-m-d');
        $startDt  = $date . ' ' . ($_GET['start_time'] ?? '00:00');
        $endDt    = $date . ' ' . ($_GET['end_time'] ?? '23:59');

        if (!$rawIds) { echo json_encode([]); break; }
        $roomIds = array_values(array_filter(array_map('intval', explode(',', $rawIds))));
        if (empty($roomIds)) { echo json_encode([]); break; }

        // Expand: find all H-Link combinations that overlap with the given rooms
        $allConflictRoomIds = $roomIds; // start with the given rooms

        // For each given room, find all combos it belongs to, then collect all rooms in those combos
        $ph = implode(',', array_fill(0, count($roomIds), '?'));

        // Direct member rooms → get their combos' virtual rooms
        $stmt = $db->prepare("
            SELECT DISTINCT c.virtual_room_id
            FROM h_link_combination_rooms cr
            JOIN h_link_combinations c ON c.id = cr.combination_id
            WHERE cr.room_id IN ($ph) AND c.virtual_room_id IS NOT NULL
        ");
        $stmt->execute($roomIds);
        $virtualIds = array_map('intval', array_column($stmt->fetchAll(), 'virtual_room_id'));
        $allConflictRoomIds = array_merge($allConflictRoomIds, $virtualIds);

        // If any given room is a virtual combo room, also block its member rooms + overlapping combos
        $stmt = $db->prepare("
            SELECT DISTINCT cr.room_id
            FROM h_link_combinations c
            JOIN h_link_combination_rooms cr ON cr.combination_id = c.id
            WHERE c.virtual_room_id IN ($ph)
        ");
        $stmt->execute($roomIds);
        $memberOfVirtual = array_map('intval', array_column($stmt->fetchAll(), 'room_id'));
        $allConflictRoomIds = array_merge($allConflictRoomIds, $memberOfVirtual);

        // Also find combos that share any of those member rooms
        if (!empty($memberOfVirtual)) {
            $ph2 = implode(',', array_fill(0, count($memberOfVirtual), '?'));
            $stmt = $db->prepare("
                SELECT DISTINCT c.virtual_room_id
                FROM h_link_combination_rooms cr
                JOIN h_link_combinations c ON c.id = cr.combination_id
                WHERE cr.room_id IN ($ph2) AND c.virtual_room_id IS NOT NULL
            ");
            $stmt->execute($memberOfVirtual);
            $moreVirtual = array_map('intval', array_column($stmt->fetchAll(), 'virtual_room_id'));
            $allConflictRoomIds = array_merge($allConflictRoomIds, $moreVirtual);
        }

        $allConflictRoomIds = array_values(array_unique($allConflictRoomIds));

        if (empty($allConflictRoomIds)) { echo json_encode([]); break; }

        $ph3 = implode(',', array_fill(0, count($allConflictRoomIds), '?'));
        $stmt = $db->prepare("
            SELECT r.id, r.title, r.start_datetime, r.end_datetime,
                   GROUP_CONCAT(rr.room_id) AS booked_room_ids
            FROM reservations r
            JOIN reservation_rooms rr ON rr.reservation_id = r.id
            WHERE rr.room_id IN ($ph3)
              AND r.start_datetime < ?
              AND r.end_datetime > ?
            GROUP BY r.id
        ");
        $stmt->execute(array_merge($allConflictRoomIds, [$endDt, $startDt]));
        echo json_encode($stmt->fetchAll());
        break;
    }

    default:
        echo json_encode(['error' => 'Unknown action: ' . htmlspecialchars($action)]);
}

// ── Helper: merge polygons into an outer hull ─────────────────────────
// For virtual rooms: collects all points from member rooms and computes convex hull.
// This gives a reasonable "combined" outline.
function _mergePolygons(array $memberRooms): ?array {
    $allPts = [];
    foreach ($memberRooms as $r) {
        $pts = $r['map_points'] ? json_decode($r['map_points'], true) : null;
        if ($pts && is_array($pts)) {
            foreach ($pts as $p) $allPts[] = $p;
        }
    }
    if (count($allPts) < 3) return null;

    // Convex hull (Graham scan)
    usort($allPts, fn($a, $b) => $a[0] === $b[0] ? $a[1] <=> $b[1] : $a[0] <=> $b[0]);
    $allPts = array_values(array_unique(array_map('json_encode', $allPts)));
    $allPts = array_map('json_decode', $allPts);

    if (count($allPts) < 3) return array_map(fn($p) => [(float)$p[0], (float)$p[1]], $allPts);

    $cross = fn($O, $A, $B) => ($A[0] - $O[0]) * ($B[1] - $O[1]) - ($A[1] - $O[1]) * ($B[0] - $O[0]);
    $lower = [];
    foreach ($allPts as $p) {
        while (count($lower) >= 2 && $cross($lower[count($lower)-2], $lower[count($lower)-1], $p) <= 0)
            array_pop($lower);
        $lower[] = $p;
    }
    $upper = [];
    foreach (array_reverse($allPts) as $p) {
        while (count($upper) >= 2 && $cross($upper[count($upper)-2], $upper[count($upper)-1], $p) <= 0)
            array_pop($upper);
        $upper[] = $p;
    }
    array_pop($lower); array_pop($upper);
    $hull = array_merge($lower, $upper);
    return array_map(fn($p) => [(float)$p[0], (float)$p[1]], $hull);
}

function _autoAbbrev(string $name): string {
    $w = preg_split('/\s+/', trim($name));
    if (count($w) === 1) return strtoupper(substr($name, 0, 4));
    return strtoupper(substr(implode('', array_map(fn($x) => $x[0], $w)), 0, 5));
}
