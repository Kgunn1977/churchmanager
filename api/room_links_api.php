<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

$db     = getDB();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    // ── get_links ─────────────────────────────────────────────
    // Returns all room links with their member room IDs and names.
    case 'get_links': {
        $stmt = $db->query("
            SELECT rl.id, rl.name, rl.building_id,
                   GROUP_CONCAT(rlm.room_id ORDER BY rlm.room_id) AS room_ids_csv
            FROM room_links rl
            JOIN room_link_members rlm ON rlm.link_id = rl.id
            GROUP BY rl.id
        ");
        $rows = $stmt->fetchAll();
        $links = array_map(function($r) {
            return [
                'id'          => (int)$r['id'],
                'name'        => $r['name'],
                'building_id' => (int)$r['building_id'],
                'room_ids'    => array_map('intval', explode(',', $r['room_ids_csv'])),
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
            // Create link record
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

            $db->commit();
            echo json_encode([
                'success' => true,
                'link'    => [
                    'id'          => $linkId,
                    'name'        => $name,
                    'building_id' => $buildingId,
                    'room_ids'    => array_map('intval', $roomIds),
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
