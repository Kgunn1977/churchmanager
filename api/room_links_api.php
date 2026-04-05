<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

$db     = getDB();
// Normalise: if the request body is JSON, populate $_POST so downstream code works uniformly
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST)) {
    $_POST = json_decode(file_get_contents('php://input'), true) ?: [];
}
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    // ── get_links ─────────────────────────────────────────────
    // Returns all room links with their member room IDs and names.
    case 'get_links': {
        $stmt = $db->query("
            SELECT rl.id, rl.name, rl.building_id, rl.virtual_room_id,
                   GROUP_CONCAT(rlm.room_id ORDER BY rlm.room_id) AS room_ids_csv
            FROM room_links rl
            JOIN room_link_members rlm ON rlm.link_id = rl.id
            GROUP BY rl.id
        ");
        $rows = $stmt->fetchAll();
        $links = array_map(function($r) {
            return [
                'id'              => (int)$r['id'],
                'name'            => $r['name'],
                'building_id'     => (int)$r['building_id'],
                'virtual_room_id' => $r['virtual_room_id'] ? (int)$r['virtual_room_id'] : null,
                'room_ids'        => array_map('intval', explode(',', $r['room_ids_csv'])),
            ];
        }, $rows);
        echo json_encode($links);
        break;
    }

    // ── create_link ───────────────────────────────────────────
    // POST: name, room_ids (JSON array), building_id
    // Renames each room to the link name; stores original names.
    case 'create_link': {
        if (!isAdmin()) { echo json_encode(['error' => 'Admin only']); break; }

        $name       = trim($_POST['name'] ?? '');
        $buildingId = intval($_POST['building_id'] ?? 0);
        $roomIds    = json_decode($_POST['room_ids'] ?? '[]', true);

        if (!$name)                      { echo json_encode(['error' => 'Name is required']);             break; }
        if ($buildingId < 1)             { echo json_encode(['error' => 'building_id is required']);      break; }
        if (count($roomIds) < 2)         { echo json_encode(['error' => 'At least 2 rooms required']);    break; }

        // Verify all rooms exist, belong to the same building, and are not already linked
        $placeholders = implode(',', array_fill(0, count($roomIds), '?'));
        $stmt = $db->prepare("
            SELECT r.id, r.name, f.building_id
            FROM rooms r
            JOIN floors f ON f.id = r.floor_id
            WHERE r.id IN ($placeholders)
        ");
        $stmt->execute($roomIds);
        $rooms = $stmt->fetchAll();

        if (count($rooms) !== count($roomIds)) {
            echo json_encode(['error' => 'One or more rooms not found']); break;
        }
        foreach ($rooms as $room) {
            if ((int)$room['building_id'] !== $buildingId) {
                echo json_encode(['error' => 'All rooms must be in the same building']); break 2;
            }
        }

        // Check none of these rooms are already linked
        $stmt = $db->prepare("SELECT room_id FROM room_link_members WHERE room_id IN ($placeholders)");
        $stmt->execute($roomIds);
        if ($stmt->fetch()) {
            echo json_encode(['error' => 'One or more rooms are already linked. Unlink first.']); break;
        }

        $db->beginTransaction();
        try {
            // Create link record (virtual_room_id filled below)
            $stmt = $db->prepare("INSERT INTO room_links (name, building_id) VALUES (?, ?)");
            $stmt->execute([$name, $buildingId]);
            $linkId = (int)$db->lastInsertId();

            // Insert members and rename rooms
            $insertMember = $db->prepare("INSERT INTO room_link_members (link_id, room_id, original_name) VALUES (?, ?, ?)");
            $renameRoom   = $db->prepare("UPDATE rooms SET name = ? WHERE id = ?");
            foreach ($rooms as $room) {
                $insertMember->execute([$linkId, $room['id'], $room['name']]);
                $renameRoom->execute([$name, $room['id']]);
            }

            // ── Create virtual room ──
            // Sum capacity from all member rooms
            $stmt = $db->prepare("SELECT COALESCE(SUM(capacity), 0) FROM rooms WHERE id IN ($placeholders)");
            $stmt->execute($roomIds);
            $totalCap = (int)$stmt->fetchColumn();

            // Merge polygons via convex hull
            $stmt = $db->prepare("SELECT id, map_points FROM rooms WHERE id IN ($placeholders)");
            $stmt->execute($roomIds);
            $memberRoomData = $stmt->fetchAll();
            $mergedPoints = _mergePolygons($memberRoomData);

            // Use first member's floor for virtual room placement
            $stmt = $db->prepare("SELECT floor_id FROM rooms WHERE id = ?");
            $stmt->execute([$roomIds[0]]);
            $floorId = (int)$stmt->fetchColumn();

            $abbr = _autoAbbrev($name);
            $stmt = $db->prepare("
                INSERT INTO rooms (floor_id, name, abbreviation, capacity, map_points, is_reservable, is_storage, is_virtual)
                VALUES (?, ?, ?, ?, ?, 1, 0, 1)
            ");
            $stmt->execute([$floorId, $name, $abbr, $totalCap, $mergedPoints ? json_encode($mergedPoints) : null]);
            $virtualRoomId = (int)$db->lastInsertId();

            // Link virtual room back to the V-Link group
            $db->prepare("UPDATE room_links SET virtual_room_id = ? WHERE id = ?")->execute([$virtualRoomId, $linkId]);

            $db->commit();
            echo json_encode([
                'success' => true,
                'link'    => [
                    'id'              => $linkId,
                    'name'            => $name,
                    'building_id'     => $buildingId,
                    'virtual_room_id' => $virtualRoomId,
                    'room_ids'        => array_map('intval', $roomIds),
                ],
            ]);
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
    }

    // ── delete_link ───────────────────────────────────────────
    // POST: link_id  OR  room_id (finds the link from the room)
    // Restores original room names, removes link records.
    case 'delete_link': {
        if (!isAdmin()) { echo json_encode(['error' => 'Admin only']); break; }

        $linkId = intval($_POST['link_id'] ?? 0);
        $roomId = intval($_POST['room_id'] ?? 0);

        if (!$linkId && $roomId) {
            $stmt = $db->prepare("SELECT link_id FROM room_link_members WHERE room_id = ?");
            $stmt->execute([$roomId]);
            $linkId = (int)($stmt->fetchColumn() ?: 0);
        }
        if (!$linkId) { echo json_encode(['error' => 'Link not found']); break; }

        $db->beginTransaction();
        try {
            // Get virtual_room_id before deleting
            $stmt = $db->prepare("SELECT virtual_room_id FROM room_links WHERE id = ?");
            $stmt->execute([$linkId]);
            $virtualRoomId = (int)($stmt->fetchColumn() ?: 0);

            // Restore original names
            $stmt = $db->prepare("SELECT room_id, original_name FROM room_link_members WHERE link_id = ?");
            $stmt->execute([$linkId]);
            $members = $stmt->fetchAll();

            $restore = $db->prepare("UPDATE rooms SET name = ? WHERE id = ?");
            foreach ($members as $m) {
                $restore->execute([$m['original_name'], $m['room_id']]);
            }

            // Delete members then link
            $db->prepare("DELETE FROM room_link_members WHERE link_id = ?")->execute([$linkId]);
            $db->prepare("DELETE FROM room_links WHERE id = ?")->execute([$linkId]);

            // Delete the virtual room
            if ($virtualRoomId) {
                $db->prepare("DELETE FROM reservation_rooms WHERE room_id = ?")->execute([$virtualRoomId]);
                $db->prepare("DELETE FROM rooms WHERE id = ? AND is_virtual = 1")->execute([$virtualRoomId]);
            }

            $db->commit();
            echo json_encode(['success' => true, 'restored_rooms' => array_map(fn($m) => (int)$m['room_id'], $members)]);
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
    }

    default:
        echo json_encode(['error' => 'Unknown action']);
}

// ── Helper: merge polygons via convex hull (Graham scan) ──
function _mergePolygons(array $memberRooms): ?array {
    $allPts = [];
    foreach ($memberRooms as $r) {
        $pts = $r['map_points'] ? json_decode($r['map_points'], true) : null;
        if ($pts && is_array($pts)) {
            foreach ($pts as $p) $allPts[] = $p;
        }
    }
    if (count($allPts) < 3) return null;

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
