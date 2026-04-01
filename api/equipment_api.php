<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

$db     = getDB();
$input  = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST)) {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
} else {
    $input = $_POST;
}
$action = $_GET['action'] ?? $input['action'] ?? '';

switch ($action) {

    // ── GET full catalog with Total / Stored / Assigned counts ─
    case 'get_catalog':
        $rows = $db->query("
            SELECT e.*,
                   COALESCE(SUM(re.quantity), 0) AS placed_qty,
                   COALESCE(SUM(CASE WHEN r.is_storage = 1 THEN re.quantity ELSE 0 END), 0) AS stored_qty,
                   COALESCE(SUM(CASE WHEN r.is_storage = 0 THEN re.quantity ELSE 0 END), 0) AS assigned_qty
            FROM equipment_catalog e
            LEFT JOIN room_equipment re ON re.equipment_id = e.id
            LEFT JOIN rooms r ON r.id = re.room_id
            GROUP BY e.id
            ORDER BY e.category, e.name
        ")->fetchAll();
        foreach ($rows as &$r) {
            $r['id']             = (int)$r['id'];
            $r['total_quantity'] = (int)$r['total_quantity'];
            $r['placed_qty']     = (int)$r['placed_qty'];
            $r['stored_qty']     = (int)$r['stored_qty'];
            $r['assigned_qty']   = (int)$r['assigned_qty'];
        }
        echo json_encode($rows);
        break;

    // ── GET equipment for a specific room ─────────────────────
    case 'get_room_equipment':
        $roomId = (int)($_GET['room_id'] ?? 0);
        if (!$roomId) { echo json_encode([]); break; }
        $stmt = $db->prepare("
            SELECT re.id, re.equipment_id, re.quantity, re.is_movable, re.notes,
                   e.name, e.category, e.description AS eq_desc
            FROM room_equipment re
            JOIN equipment_catalog e ON e.id = re.equipment_id
            WHERE re.room_id = ?
            ORDER BY e.category, e.name
        ");
        $stmt->execute([$roomId]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) {
            $r['id']           = (int)$r['id'];
            $r['equipment_id'] = (int)$r['equipment_id'];
            $r['quantity']     = (int)$r['quantity'];
            $r['is_movable']   = (int)($r['is_movable'] ?? 1);
        }
        echo json_encode($rows);
        break;

    // ── GET equipment distribution across all rooms for one catalog item ──
    case 'get_equipment_locations':
        $eqId = (int)($_GET['equipment_id'] ?? 0);
        if (!$eqId) { echo json_encode([]); break; }
        $stmt = $db->prepare("
            SELECT re.id, re.room_id, re.quantity, re.is_movable, re.notes,
                   r.name AS room_name, f.name AS floor_name, b.name AS building_name
            FROM room_equipment re
            JOIN rooms r ON r.id = re.room_id
            JOIN floors f ON f.id = r.floor_id
            JOIN buildings b ON b.id = f.building_id
            WHERE re.equipment_id = ?
            ORDER BY b.name, f.floor_order, r.name
        ");
        $stmt->execute([$eqId]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) {
            $r['id']         = (int)$r['id'];
            $r['room_id']    = (int)$r['room_id'];
            $r['quantity']   = (int)$r['quantity'];
            $r['is_movable'] = (int)($r['is_movable'] ?? 1);
        }
        echo json_encode($rows);
        break;

    // ── ADD / CREATE catalog item ────────────────────────────
    case 'add_catalog_item':
        $name  = trim($input['name'] ?? '');
        $desc  = trim($input['description'] ?? '');
        $cat   = trim($input['category'] ?? 'other');
        $total = max(0, (int)($input['total_quantity'] ?? 0));
        if (!$name) { echo json_encode(['error' => 'Name required']); break; }
        try {
            $stmt = $db->prepare("INSERT INTO equipment_catalog (name, description, category, total_quantity) VALUES (?,?,?,?)");
            $stmt->execute([$name, $desc, $cat, $total]);
            $id = (int)$db->lastInsertId();
            echo json_encode(['success' => true, 'id' => $id]);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    // ── UPDATE catalog item ──────────────────────────────────
    case 'update_catalog_item':
        $id    = (int)($input['id'] ?? 0);
        $name  = trim($input['name'] ?? '');
        $desc  = trim($input['description'] ?? '');
        $cat   = trim($input['category'] ?? 'other');
        $total = max(0, (int)($input['total_quantity'] ?? 0));
        if (!$id || !$name) { echo json_encode(['error' => 'id and name required']); break; }
        try {
            $db->prepare("UPDATE equipment_catalog SET name=?, description=?, category=?, total_quantity=? WHERE id=?")
               ->execute([$name, $desc, $cat, $total, $id]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    // ── DELETE catalog item ──────────────────────────────────
    case 'delete_catalog_item':
        $id = (int)($input['id'] ?? 0);
        if (!$id) { echo json_encode(['error' => 'id required']); break; }
        try {
            $db->prepare("DELETE FROM room_equipment WHERE equipment_id=?")->execute([$id]);
            $db->prepare("DELETE FROM equipment_catalog WHERE id=?")->execute([$id]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    // ── ASSIGN equipment to a room ───────────────────────────
    case 'assign_to_room':
        $roomId = (int)($input['room_id'] ?? 0);
        $eqId   = (int)($input['equipment_id'] ?? 0);
        $qty    = max(0, (int)($input['quantity'] ?? 0));
        $movable = isset($input['is_movable']) ? ($input['is_movable'] ? 1 : 0) : 1;
        $notes  = trim($input['notes'] ?? '');
        if (!$roomId || !$eqId) { echo json_encode(['error' => 'room_id and equipment_id required']); break; }
        try {
            $stmt = $db->prepare("
                INSERT INTO room_equipment (room_id, equipment_id, quantity, is_movable, notes)
                VALUES (?,?,?,?,?)
                ON DUPLICATE KEY UPDATE quantity=VALUES(quantity), is_movable=VALUES(is_movable), notes=VALUES(notes)
            ");
            $stmt->execute([$roomId, $eqId, $qty, $movable, $notes]);
            $id = (int)$db->lastInsertId();
            echo json_encode(['success' => true, 'id' => $id]);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    // ── UPDATE room equipment assignment ──────────────────────
    case 'update_assignment':
        $reId    = (int)($input['id'] ?? 0);
        $qty     = max(0, (int)($input['quantity'] ?? 0));
        $movable = isset($input['is_movable']) ? ($input['is_movable'] ? 1 : 0) : 1;
        $notes   = trim($input['notes'] ?? '');
        if (!$reId) { echo json_encode(['error' => 'id required']); break; }
        try {
            $db->prepare("UPDATE room_equipment SET quantity=?, is_movable=?, notes=? WHERE id=?")
               ->execute([$qty, $movable, $notes, $reId]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    // ── REMOVE equipment from a room ─────────────────────────
    case 'remove_from_room':
        $reId = (int)($input['id'] ?? 0);
        if (!$reId) { echo json_encode(['error' => 'id required']); break; }
        try {
            $db->prepare("DELETE FROM room_equipment WHERE id=?")->execute([$reId]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['error' => 'Unknown action']);
}
