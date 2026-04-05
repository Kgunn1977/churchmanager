<?php
/**
 * Room Links API
 *
 * Actions:
 *   GET  get_links           — returns all link groups with member room_ids
 *   GET  get_links_for_rooms — returns link groups for specific room IDs (?room_ids=1,2,3)
 *   POST create_link         — name, room_ids (JSON array)
 *   POST delete_link         — link_id
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

$db     = getDB();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    // ── Get all link groups with member room IDs ─────────────────────────────
    case 'get_links':
        $links = $db->query("
            SELECT rl.id, rl.name, rl.created_at
            FROM room_links rl
            ORDER BY rl.name
        ")->fetchAll();

        foreach ($links as &$link) {
            $stmt = $db->prepare("
                SELECT rlm.room_id, r.name AS room_name, r.capacity,
                       f.id AS floor_id, f.name AS floor_name,
                       b.id AS building_id, b.name AS building_name
                FROM room_link_members rlm
                JOIN rooms r ON r.id = rlm.room_id
                JOIN floors f ON f.id = r.floor_id
                JOIN buildings b ON b.id = f.building_id
                WHERE rlm.link_id = ?
                ORDER BY b.name, f.floor_order, r.name
            ");
            $stmt->execute([$link['id']]);
            $members = $stmt->fetchAll();
            $link['room_ids'] = array_map('intval', array_column($members, 'room_id'));
            $link['members']  = $members;
            $link['total_capacity'] = array_sum(array_map(function($m) {
                return (int)($m['capacity'] ?? 0);
            }, $members));
        }
        echo json_encode($links);
        break;

    // ── Get link groups that include any of the given room IDs ───────────────
    case 'get_links_for_rooms':
        $roomIds = array_filter(array_map('intval', explode(',', $_GET['room_ids'] ?? '')));
        if (empty($roomIds)) { echo json_encode([]); break; }

        $ph = implode(',', array_fill(0, count($roomIds), '?'));
        $stmt = $db->prepare("
            SELECT DISTINCT rl.id, rl.name
            FROM room_links rl
            JOIN room_link_members rlm ON rlm.link_id = rl.id
            WHERE rlm.room_id IN ($ph)
            ORDER BY rl.name
        ");
        $stmt->execute($roomIds);
        $links = $stmt->fetchAll();

        foreach ($links as &$link) {
            $mStmt = $db->prepare("SELECT room_id FROM room_link_members WHERE link_id = ?");
            $mStmt->execute([$link['id']]);
            $link['room_ids'] = array_map('intval', array_column($mStmt->fetchAll(), 'room_id'));
        }
        echo json_encode($links);
        break;

    // ── Create a new link group ──────────────────────────────────────────────
    case 'create_link':
        if (!isAdmin()) { echo json_encode(['error' => 'Admin access required']); break; }

        $name    = trim($_POST['name'] ?? '');
        $roomIds = json_decode($_POST['room_ids'] ?? '[]', true);

        if ($name === '')          { echo json_encode(['error' => 'Name is required']); break; }
        if (count($roomIds) < 2)   { echo json_encode(['error' => 'At least 2 rooms required']); break; }

        try {
            $db->beginTransaction();

            $stmt = $db->prepare("INSERT INTO room_links (name) VALUES (?)");
            $stmt->execute([$name]);
            $linkId = (int)$db->lastInsertId();

            $ins = $db->prepare("INSERT INTO room_link_members (link_id, room_id) VALUES (?, ?)");
            foreach ($roomIds as $rid) {
                $ins->execute([$linkId, (int)$rid]);
            }

            $db->commit();
            echo json_encode(['success' => true, 'link_id' => $linkId]);
        } catch (PDOException $e) {
            $db->rollBack();
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    // ── Delete a link group ──────────────────────────────────────────────────
    case 'delete_link':
        if (!isAdmin()) { echo json_encode(['error' => 'Admin access required']); break; }

        $linkId = intval($_POST['link_id'] ?? 0);
        if (!$linkId) { echo json_encode(['error' => 'link_id required']); break; }

        try {
            // Members deleted automatically via ON DELETE CASCADE
            $db->prepare("DELETE FROM room_links WHERE id = ?")->execute([$linkId]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['error' => 'Unknown action: ' . $action]);
}
