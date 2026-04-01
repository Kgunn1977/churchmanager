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

    case 'get_all':
        $rows = $db->query("SELECT * FROM supplies_catalog ORDER BY name")->fetchAll();
        foreach ($rows as &$r) {
            $r['id']       = (int)$r['id'];
            $r['quantity'] = (int)$r['quantity'];
        }
        echo json_encode($rows);
        break;

    case 'add':
        $name = trim($input['name'] ?? '');
        $qty  = max(1, (int)($input['quantity'] ?? 1));
        if (!$name) { echo json_encode(['error' => 'Name required']); break; }
        try {
            $db->prepare("INSERT INTO supplies_catalog (name, quantity) VALUES (?,?)")->execute([$name, $qty]);
            echo json_encode(['success' => true, 'id' => (int)$db->lastInsertId()]);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'update':
        $id   = (int)($input['id'] ?? 0);
        $name = trim($input['name'] ?? '');
        $qty  = max(1, (int)($input['quantity'] ?? 1));
        if (!$id || !$name) { echo json_encode(['error' => 'id and name required']); break; }
        try {
            $db->prepare("UPDATE supplies_catalog SET name=?, quantity=? WHERE id=?")->execute([$name, $qty, $id]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'delete':
        $id = (int)($input['id'] ?? 0);
        if (!$id) { echo json_encode(['error' => 'id required']); break; }
        try {
            $db->prepare("DELETE FROM supplies_catalog WHERE id=?")->execute([$id]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['error' => 'Unknown action']);
}
