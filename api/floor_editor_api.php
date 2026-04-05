<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

$db     = getDB();
// Normalise: if the request body is JSON, populate $_POST so all APIs work uniformly
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST)) {
    $_POST = json_decode(file_get_contents('php://input'), true) ?: [];
}
$input  = $_POST;   // alias kept — this file reads $input throughout
$action = $_GET['action'] ?? $input['action'] ?? '';

switch ($action) {

    // ── GET buildings ────────────────────────────────────────────────────────
    case 'get_buildings':
        $rows = $db->query("SELECT id, name FROM buildings ORDER BY name")->fetchAll();
        echo json_encode($rows);
        break;

    // ── GET floors for a building ────────────────────────────────────────────
    case 'get_floors':
        $bid = intval($_GET['building_id'] ?? 0);
        $stmt = $db->prepare("SELECT id, name, floor_order FROM floors WHERE building_id = ? ORDER BY floor_order, name");
        $stmt->execute([$bid]);
        echo json_encode($stmt->fetchAll());
        break;

    // ── GET rooms for a floor (with map_points) ──────────────────────────────
    case 'get_rooms':
        $fid = intval($_GET['floor_id'] ?? 0);
        $stmt = $db->prepare("SELECT id, name, abbreviation, capacity, map_points, is_reservable, is_storage FROM rooms WHERE floor_id = ? ORDER BY name");
        $stmt->execute([$fid]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) {
            $r['map_points']    = $r['map_points'] ? json_decode($r['map_points'], true) : null;
            $r['capacity']      = $r['capacity'] !== null ? (int)$r['capacity'] : null;
            $r['is_reservable'] = (int)($r['is_reservable'] ?? 1);
            $r['is_storage']    = (int)($r['is_storage'] ?? 0);
        }
        echo json_encode($rows);
        break;

    // ── CREATE building ──────────────────────────────────────────────────────
    case 'create_building':
        $name = trim($input['name'] ?? '');
        if ($name === '') { echo json_encode(['error' => 'Name required']); break; }
        try {
            $stmt = $db->prepare("INSERT INTO buildings (name) VALUES (?)");
            $stmt->execute([$name]);
            $id = (int)$db->lastInsertId();
            echo json_encode(['id' => $id, 'name' => $name]);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    // ── CREATE floor (auto-assigns floor_order as max+1) ─────────────────────
    case 'create_floor':
        $bid  = intval($input['building_id'] ?? 0);
        $name = trim($input['name'] ?? '');
        if (!$bid || $name === '') { echo json_encode(['error' => 'building_id and name required']); break; }
        try {
            $maxStmt = $db->prepare("SELECT COALESCE(MAX(floor_order), 0) + 1 AS next_order FROM floors WHERE building_id = ?");
            $maxStmt->execute([$bid]);
            $order = (int)$maxStmt->fetchColumn();
            $stmt = $db->prepare("INSERT INTO floors (building_id, name, floor_order) VALUES (?, ?, ?)");
            $stmt->execute([$bid, $name, $order]);
            $id = (int)$db->lastInsertId();
            echo json_encode(['id' => $id, 'name' => $name, 'floor_order' => $order, 'building_id' => $bid]);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    // ── CREATE room ───────────────────────────────────────────────────────────
    case 'create_room':
        $fid  = intval($input['floor_id'] ?? 0);
        $name = trim($input['name'] ?? '');
        $abbr = trim($input['abbreviation'] ?? '');
        if (!$fid || $name === '') { echo json_encode(['error' => 'floor_id and name required']); break; }
        try {
            $stmt = $db->prepare("INSERT INTO rooms (floor_id, name, abbreviation) VALUES (?, ?, ?)");
            $stmt->execute([$fid, $name, $abbr ?: null]);
            $id = (int)$db->lastInsertId();
            echo json_encode(['id' => $id, 'name' => $name, 'abbreviation' => $abbr ?: null, 'capacity' => null, 'floor_id' => $fid, 'map_points' => null, 'is_reservable' => 1, 'is_storage' => 0]);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    // ── SAVE room (name, abbreviation, capacity, map_points — any subset) ───────
    case 'save_room':
        $id     = intval($input['id'] ?? 0);
        if (!$id) { echo json_encode(['error' => 'id required']); break; }
        try {
            $sets = []; $params = [];
            if (isset($input['name']))         { $sets[] = 'name = ?';         $params[] = trim($input['name']); }
            if (array_key_exists('abbreviation', $input)) {
                $sets[] = 'abbreviation = ?';
                $params[] = trim($input['abbreviation']) ?: null;
            }
            if (array_key_exists('capacity', $input)) {
                $capRaw = trim((string)($input['capacity'] ?? ''));
                $sets[] = 'capacity = ?';
                $params[] = strlen($capRaw) > 0 ? intval($capRaw) : null;
            }
            if (isset($input['map_points']))   { $sets[] = 'map_points = ?';   $params[] = json_encode($input['map_points']); }
            if (array_key_exists('is_reservable', $input)) {
                $sets[] = 'is_reservable = ?';
                $params[] = $input['is_reservable'] ? 1 : 0;
            }
            if (array_key_exists('is_storage', $input)) {
                $sets[] = 'is_storage = ?';
                $params[] = $input['is_storage'] ? 1 : 0;
            }
            if (empty($sets)) { echo json_encode(['success' => true]); break; }
            $params[] = $id;
            $stmt = $db->prepare("UPDATE rooms SET " . implode(', ', $sets) . " WHERE id = ?");
            $stmt->execute($params);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    // ── DELETE room ───────────────────────────────────────────────────────────
    case 'delete_room':
        $id = intval($input['id'] ?? 0);
        if (!$id) { echo json_encode(['error' => 'id required']); break; }
        try {
            $db->beginTransaction();

            // Check if this room is part of a link group
            $stmt = $db->prepare("SELECT link_id, original_name FROM room_link_members WHERE room_id = ?");
            $stmt->execute([$id]);
            $member = $stmt->fetch();

            if ($member) {
                $linkId = (int)$member['link_id'];

                // How many members remain after removing this room?
                $countStmt = $db->prepare("SELECT COUNT(*) FROM room_link_members WHERE link_id = ?");
                $countStmt->execute([$linkId]);
                $remainingCount = (int)$countStmt->fetchColumn() - 1;

                // Remove this room from the link group
                $db->prepare("DELETE FROM room_link_members WHERE link_id = ? AND room_id = ?")->execute([$linkId, $id]);

                if ($remainingCount < 2) {
                    // Not enough rooms to remain linked — dissolve the group, restore names
                    $restoreStmt = $db->prepare("SELECT room_id, original_name FROM room_link_members WHERE link_id = ?");
                    $restoreStmt->execute([$linkId]);
                    $survivors = $restoreStmt->fetchAll();
                    $renameStmt = $db->prepare("UPDATE rooms SET name = ? WHERE id = ?");
                    foreach ($survivors as $s) {
                        $renameStmt->execute([$s['original_name'], $s['room_id']]);
                    }
                    $db->prepare("DELETE FROM room_link_members WHERE link_id = ?")->execute([$linkId]);
                    $db->prepare("DELETE FROM room_links WHERE id = ?")->execute([$linkId]);
                }
            }

            $db->prepare("DELETE FROM rooms WHERE id = ?")->execute([$id]);
            $db->commit();
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            $db->rollBack();
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    // ── GET floor order ───────────────────────────────────────────────────────
    case 'get_floor_order':
        $fid = intval($_GET['floor_id'] ?? 0);
        if (!$fid) { echo json_encode(['error' => 'floor_id required']); break; }
        $stmt = $db->prepare("SELECT floor_order FROM floors WHERE id = ?");
        $stmt->execute([$fid]);
        $row = $stmt->fetch();
        echo json_encode(['floor_order' => $row ? (int)$row['floor_order'] : 1]);
        break;

    // ── UPDATE floor (name + display order) ──────────────────────────────────
    case 'update_floor':
        $id    = intval($input['id'] ?? 0);
        $name  = trim($input['name'] ?? '');
        $order = intval($input['floor_order'] ?? 1);
        if (!$id || $name === '') { echo json_encode(['error' => 'id and name required']); break; }
        try {
            $stmt = $db->prepare("UPDATE floors SET name = ?, floor_order = ? WHERE id = ?");
            $stmt->execute([$name, $order, $id]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    // ── GET all floors + rooms across all buildings (for floor pane "All" view) ─
    case 'get_all_floors_rooms':
        $stmt = $db->query(
            "SELECT f.id, f.name, f.floor_order, f.building_id, b.name AS building_name
               FROM floors f
               JOIN buildings b ON b.id = f.building_id
              ORDER BY f.building_id, f.floor_order, f.name"
        );
        $floors = $stmt->fetchAll();
        foreach ($floors as &$floor) {
            $roomStmt = $db->prepare("SELECT id, name, abbreviation, map_points, is_reservable, is_storage FROM rooms WHERE floor_id = ? ORDER BY name");
            $roomStmt->execute([$floor['id']]);
            $rooms = $roomStmt->fetchAll();
            foreach ($rooms as &$r) {
                $r['map_points']    = $r['map_points'] ? json_decode($r['map_points'], true) : null;
                $r['is_reservable'] = (int)($r['is_reservable'] ?? 1);
                $r['is_storage']    = (int)($r['is_storage'] ?? 0);
            }
            $floor['rooms'] = $rooms;
        }
        echo json_encode($floors);
        break;

    // ── GET all floors + rooms for a building (for floor pane) ───────────────
    case 'get_building_floors_rooms':
        $bid = intval($_GET['building_id'] ?? 0);
        if (!$bid) { echo json_encode(['error' => 'building_id required']); break; }
        $floorStmt = $db->prepare(
            "SELECT f.id, f.name, f.floor_order, f.building_id, b.name AS building_name
               FROM floors f JOIN buildings b ON b.id = f.building_id
              WHERE f.building_id = ? ORDER BY f.floor_order, f.name"
        );
        $floorStmt->execute([$bid]);
        $floors = $floorStmt->fetchAll();
        foreach ($floors as &$floor) {
            $roomStmt = $db->prepare("SELECT id, name, abbreviation, map_points, is_reservable, is_storage FROM rooms WHERE floor_id = ? ORDER BY name");
            $roomStmt->execute([$floor['id']]);
            $rooms = $roomStmt->fetchAll();
            foreach ($rooms as &$r) {
                $r['map_points']    = $r['map_points'] ? json_decode($r['map_points'], true) : null;
                $r['is_reservable'] = (int)($r['is_reservable'] ?? 1);
                $r['is_storage']    = (int)($r['is_storage'] ?? 0);
            }
            $floor['rooms'] = $rooms;
        }
        echo json_encode($floors);
        break;

    // ── REORDER floors (batch update floor_order) ─────────────────────────────
    case 'reorder_floors':
        // input: { orders: [{id, floor_order}, ...] }
        $orders = $input['orders'] ?? [];
        if (!is_array($orders) || count($orders) === 0) { echo json_encode(['error' => 'orders required']); break; }
        try {
            $stmt = $db->prepare("UPDATE floors SET floor_order = ? WHERE id = ?");
            foreach ($orders as $item) {
                $stmt->execute([intval($item['floor_order']), intval($item['id'])]);
            }
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['error' => 'Unknown action']);
}
