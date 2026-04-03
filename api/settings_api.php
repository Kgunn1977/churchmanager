<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

$db = getDB();
// Normalise: if the request body is JSON, populate $_POST so downstream code works uniformly
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST)) {
    $_POST = json_decode(file_get_contents('php://input'), true) ?: [];
}
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    // ── Get all settings ─────────────────────────────────────
    case 'get_all': {
        $rows = $db->query("SELECT * FROM settings ORDER BY setting_key")->fetchAll();
        $settings = [];
        foreach ($rows as $r) {
            $settings[$r['setting_key']] = $r['setting_value'];
        }
        echo json_encode($settings);
        break;
    }

    // ── Update a setting ─────────────────────────────────────
    case 'update': {
        if (!isAdmin()) { echo json_encode(['error' => 'Admin access required']); break; }
        $key   = trim($_POST['key'] ?? '');
        $value = trim($_POST['value'] ?? '');
        if (!$key) { echo json_encode(['error' => 'Key is required']); break; }

        $stmt = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->execute([$value, $key]);

        if ($stmt->rowCount() === 0) {
            // Insert if doesn't exist
            $stmt2 = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
            $stmt2->execute([$key, $value]);
        }
        echo json_encode(['success' => true]);
        break;
    }

    // ── Bulk update settings ─────────────────────────────────
    case 'bulk_update': {
        if (!isAdmin()) { echo json_encode(['error' => 'Admin access required']); break; }
        $settings = $_POST['settings'] ?? [];
        if (!is_array($settings)) { echo json_encode(['error' => 'settings must be an array']); break; }

        $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
                              ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        foreach ($settings as $key => $value) {
            $stmt->execute([trim($key), trim($value)]);
        }
        echo json_encode(['success' => true]);
        break;
    }

    // ── Get task types ───────────────────────────────────────
    case 'get_task_types': {
        $rows = $db->query("SELECT * FROM task_types ORDER BY priority_order, name")->fetchAll();
        echo json_encode($rows);
        break;
    }

    // ── Save task type ───────────────────────────────────────
    case 'save_task_type': {
        if (!isAdmin()) { echo json_encode(['error' => 'Admin access required']); break; }
        $id   = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $priority = (int)($_POST['priority_order'] ?? 0);
        if (!$name) { echo json_encode(['error' => 'Name is required']); break; }

        if ($id) {
            $db->prepare("UPDATE task_types SET name = ?, priority_order = ? WHERE id = ?")->execute([$name, $priority, $id]);
        } else {
            $db->prepare("INSERT INTO task_types (name, priority_order) VALUES (?, ?)")->execute([$name, $priority]);
            $id = (int)$db->lastInsertId();
        }
        echo json_encode(['success' => true, 'id' => $id]);
        break;
    }

    // ── Reorder task types ───────────────────────────────────
    case 'reorder_task_types': {
        if (!isAdmin()) { echo json_encode(['error' => 'Admin access required']); break; }
        $orders = $_POST['orders'] ?? [];
        $stmt = $db->prepare("UPDATE task_types SET priority_order = ? WHERE id = ?");
        foreach ($orders as $o) {
            $stmt->execute([(int)$o['priority_order'], (int)$o['id']]);
        }
        echo json_encode(['success' => true]);
        break;
    }

    // ── Delete task type ─────────────────────────────────────
    case 'delete_task_type': {
        if (!isAdmin()) { echo json_encode(['error' => 'Admin access required']); break; }
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['error' => 'Missing id']); break; }
        // Check if any tasks use this type
        $count = $db->prepare("SELECT COUNT(*) FROM tasks WHERE task_type_id = ?");
        $count->execute([$id]);
        if ($count->fetchColumn() > 0) {
            echo json_encode(['error' => 'Cannot delete: tasks are using this type. Reassign them first.']);
            break;
        }
        $db->prepare("DELETE FROM task_types WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true]);
        break;
    }

    // ── Git pull (deploy latest from GitHub) ───────────────
    case 'git_pull': {
        if (!isAdmin()) { echo json_encode(['error' => 'Admin access required']); break; }
        $root = realpath(__DIR__ . '/..');
        $output = [];
        $code   = 0;
        exec("cd " . escapeshellarg($root) . " && git pull 2>&1", $output, $code);
        echo json_encode([
            'success' => $code === 0,
            'output'  => implode("\n", $output),
            'code'    => $code
        ]);
        break;
    }

    default:
        echo json_encode(['error' => 'Unknown action']);
}
