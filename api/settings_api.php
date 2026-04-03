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

    // ── Export database to SQL snapshot ────────────────────
    case 'db_export': {
        if (!isAdmin()) { echo json_encode(['error' => 'Admin access required']); break; }

        $root = realpath(__DIR__ . '/..');
        $file = $root . '/data/db_snapshot.sql';

        // Ensure data/ directory exists
        if (!is_dir($root . '/data')) { mkdir($root . '/data', 0755, true); }

        // Get all tables
        $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $sql = "-- Church Facility Manager DB Snapshot\n";
        $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- Source: " . gethostname() . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

        foreach ($tables as $table) {
            // Skip sessions table if it exists
            if ($table === 'sessions') continue;

            // Get CREATE TABLE statement
            $create = $db->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM);
            $sql .= "DROP TABLE IF EXISTS `$table`;\n";
            $sql .= $create[1] . ";\n\n";

            // Get all rows
            $rows = $db->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
            if (count($rows) > 0) {
                foreach ($rows as $row) {
                    $vals = [];
                    foreach ($row as $v) {
                        if ($v === null) { $vals[] = 'NULL'; }
                        else { $vals[] = $db->quote($v); }
                    }
                    $cols = '`' . implode('`, `', array_keys($row)) . '`';
                    $sql .= "INSERT INTO `$table` ($cols) VALUES (" . implode(', ', $vals) . ");\n";
                }
                $sql .= "\n";
            }
        }

        $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";

        $bytes = file_put_contents($file, $sql);
        if ($bytes === false) {
            echo json_encode(['error' => 'Failed to write snapshot file']);
        } else {
            $kb = round($bytes / 1024, 1);
            echo json_encode([
                'success' => true,
                'tables' => count($tables),
                'size' => "{$kb} KB",
                'file' => 'data/db_snapshot.sql'
            ]);
        }
        break;
    }

    // ── Import database from SQL snapshot ────────────────
    case 'db_import': {
        if (!isAdmin()) { echo json_encode(['error' => 'Admin access required']); break; }

        $root = realpath(__DIR__ . '/..');
        $file = $root . '/data/db_snapshot.sql';

        if (!file_exists($file)) {
            echo json_encode(['error' => 'No snapshot file found. Run push.bat + Git Pull first.']);
            break;
        }

        $sql = file_get_contents($file);
        if (!$sql) {
            echo json_encode(['error' => 'Snapshot file is empty']);
            break;
        }

        $fileTime = date('Y-m-d H:i:s', filemtime($file));
        $errors = [];
        $stmtCount = 0;

        // Split SQL into statements
        // We need to handle multi-line CREATE TABLE statements properly
        $statements = [];
        $current = '';
        foreach (explode("\n", $sql) as $line) {
            // Skip comments and empty lines
            if (preg_match('/^--/', $line) || trim($line) === '') {
                $current .= $line . "\n";
                continue;
            }
            $current .= $line . "\n";
            // Statement ends with semicolon (not inside a string, simplified check)
            if (preg_match('/;\s*$/', trim($line))) {
                $stmt = trim($current);
                if ($stmt && !preg_match('/^--/', $stmt)) {
                    $statements[] = $stmt;
                }
                $current = '';
            }
        }

        foreach ($statements as $stmt) {
            try {
                $db->exec($stmt);
                $stmtCount++;
            } catch (PDOException $e) {
                $errors[] = substr($e->getMessage(), 0, 200);
            }
        }

        echo json_encode([
            'success' => count($errors) === 0,
            'statements' => $stmtCount,
            'errors' => $errors,
            'snapshot_date' => $fileTime
        ]);
        break;
    }

    // ── Git push (commit & push to GitHub) ─────────────────
    case 'git_push': {
        if (!isAdmin()) { echo json_encode(['error' => 'Admin access required']); break; }
        $root = realpath(__DIR__ . '/..');
        $allOutput = [];
        $hasError = false;

        // Stage all changes
        exec("cd " . escapeshellarg($root) . " && git add -A 2>&1", $out1, $c1);
        $allOutput[] = '$ git add -A';
        $allOutput = array_merge($allOutput, $out1);
        if ($c1 !== 0) $hasError = true;

        // Commit with timestamp
        $msg = 'Update ' . date('Y-m-d H:i');
        $out2 = []; $c2 = 0;
        exec("cd " . escapeshellarg($root) . " && git commit -m " . escapeshellarg($msg) . " 2>&1", $out2, $c2);
        $allOutput[] = '';
        $allOutput[] = '$ git commit -m "' . $msg . '"';
        $allOutput = array_merge($allOutput, $out2);
        // c2=1 means "nothing to commit" — that's ok
        if ($c2 > 1) $hasError = true;

        // Push
        $out3 = []; $c3 = 0;
        exec("cd " . escapeshellarg($root) . " && git push 2>&1", $out3, $c3);
        $allOutput[] = '';
        $allOutput[] = '$ git push';
        $allOutput = array_merge($allOutput, $out3);
        if ($c3 !== 0) $hasError = true;

        echo json_encode([
            'success' => !$hasError,
            'output'  => implode("\n", $allOutput)
        ]);
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
