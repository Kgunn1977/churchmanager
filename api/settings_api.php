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

        // Disable FK checks up front (safety net — also in snapshot SQL)
        $db->exec("SET FOREIGN_KEY_CHECKS = 0");

        // Split SQL into statements
        // We need to handle multi-line CREATE TABLE statements properly
        $statements = [];
        $current = '';
        foreach (explode("\n", $sql) as $line) {
            $trimmed = trim($line);
            // Skip comment-only and empty lines (don't accumulate into buffer)
            if ($trimmed === '' || strpos($trimmed, '--') === 0) {
                continue;
            }
            $current .= $line . "\n";
            // Statement ends with semicolon
            if (preg_match('/;\s*$/', $trimmed)) {
                $stmt = trim($current);
                if ($stmt) {
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

        // Re-enable FK checks
        $db->exec("SET FOREIGN_KEY_CHECKS = 1");

        echo json_encode([
            'success' => count($errors) === 0,
            'statements' => $stmtCount,
            'errors' => $errors,
            'snapshot_date' => $fileTime
        ]);
        break;
    }

    // ── Merge database from SQL snapshot (add new records, skip existing) ──
    case 'db_merge': {
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
        $inserted = 0;
        $skipped = 0;
        $tablesCreated = 0;

        $db->exec("SET FOREIGN_KEY_CHECKS = 0");

        // Parse SQL into statements
        $statements = [];
        $current = '';
        foreach (explode("\n", $sql) as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || strpos($trimmed, '--') === 0) continue;
            $current .= $line . "\n";
            if (preg_match('/;\s*$/', $trimmed)) {
                $stmt = trim($current);
                if ($stmt) $statements[] = $stmt;
                $current = '';
            }
        }

        foreach ($statements as $stmt) {
            try {
                // DROP TABLE — skip entirely in merge mode
                if (preg_match('/^DROP\s+TABLE/i', $stmt)) {
                    continue;
                }

                // CREATE TABLE — convert to IF NOT EXISTS
                if (preg_match('/^CREATE\s+TABLE/i', $stmt)) {
                    $stmt = preg_replace('/^CREATE\s+TABLE\s+(?!IF\s+NOT\s+EXISTS)/i', 'CREATE TABLE IF NOT EXISTS ', $stmt);
                    $db->exec($stmt);
                    $tablesCreated++;
                    continue;
                }

                // INSERT — convert to INSERT IGNORE (skips on duplicate primary key)
                if (preg_match('/^INSERT\s+INTO/i', $stmt)) {
                    $mergeStmt = preg_replace('/^INSERT\s+INTO/i', 'INSERT IGNORE INTO', $stmt);
                    $affected = $db->exec($mergeStmt);
                    if ($affected > 0) {
                        $inserted += $affected;
                    } else {
                        $skipped++;
                    }
                    continue;
                }

                // SET statements etc — execute as-is
                $db->exec($stmt);
            } catch (PDOException $e) {
                $errors[] = substr($e->getMessage(), 0, 200);
            }
        }

        $db->exec("SET FOREIGN_KEY_CHECKS = 1");

        echo json_encode([
            'success' => count($errors) === 0,
            'inserted' => $inserted,
            'skipped' => $skipped,
            'tables_checked' => $tablesCreated,
            'errors' => $errors,
            'snapshot_date' => $fileTime
        ]);
        break;
    }

    // ── Import from uploaded SQL file (replace or merge) ────
    case 'db_upload': {
        if (!isAdmin()) { echo json_encode(['error' => 'Admin access required']); break; }

        if (!isset($_FILES['sqlfile']) || $_FILES['sqlfile']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['error' => 'No file uploaded or upload error']);
            break;
        }

        $mode = $_POST['mode'] ?? 'replace';
        $sql = file_get_contents($_FILES['sqlfile']['tmp_name']);
        if (!$sql) {
            echo json_encode(['error' => 'Uploaded file is empty']);
            break;
        }

        // Also save to data/db_snapshot.sql for future use
        $root = realpath(__DIR__ . '/..');
        if (!is_dir($root . '/data')) mkdir($root . '/data', 0755, true);
        file_put_contents($root . '/data/db_snapshot.sql', $sql);

        $errors = [];
        $stmtCount = 0;
        $inserted = 0;
        $skipped = 0;

        $db->exec("SET FOREIGN_KEY_CHECKS = 0");

        // Parse SQL
        $statements = [];
        $current = '';
        foreach (explode("\n", $sql) as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || strpos($trimmed, '--') === 0) continue;
            $current .= $line . "\n";
            if (preg_match('/;\s*$/', $trimmed)) {
                $stmt = trim($current);
                if ($stmt) $statements[] = $stmt;
                $current = '';
            }
        }

        foreach ($statements as $stmt) {
            try {
                if ($mode === 'merge') {
                    // Merge mode: skip DROP, CREATE IF NOT EXISTS, INSERT IGNORE
                    if (preg_match('/^DROP\s+TABLE/i', $stmt)) continue;
                    if (preg_match('/^CREATE\s+TABLE/i', $stmt)) {
                        $stmt = preg_replace('/^CREATE\s+TABLE\s+(?!IF\s+NOT\s+EXISTS)/i', 'CREATE TABLE IF NOT EXISTS ', $stmt);
                        $db->exec($stmt);
                        $stmtCount++;
                        continue;
                    }
                    if (preg_match('/^INSERT\s+INTO/i', $stmt)) {
                        $mergeStmt = preg_replace('/^INSERT\s+INTO/i', 'INSERT IGNORE INTO', $stmt);
                        $affected = $db->exec($mergeStmt);
                        $inserted += max(0, $affected);
                        if ($affected == 0) $skipped++;
                        $stmtCount++;
                        continue;
                    }
                }
                // Replace mode or non-INSERT/CREATE statements: execute as-is
                $db->exec($stmt);
                $stmtCount++;
            } catch (PDOException $e) {
                $errors[] = substr($e->getMessage(), 0, 200);
            }
        }

        $db->exec("SET FOREIGN_KEY_CHECKS = 1");

        $result = [
            'success' => count($errors) === 0,
            'mode' => $mode,
            'statements' => $stmtCount,
            'errors' => $errors,
            'filename' => $_FILES['sqlfile']['name']
        ];
        if ($mode === 'merge') {
            $result['inserted'] = $inserted;
            $result['skipped'] = $skipped;
        }
        echo json_encode($result);
        break;
    }

    // ── Remote DB export (token-authenticated, no session needed) ──
    case 'db_export_api': {
        $token = trim($_POST['sync_token'] ?? '');
        if (!$token) {
            echo json_encode(['error' => 'sync_token required']);
            break;
        }

        // Validate token against the stored setting
        $stk = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'sync_token'");
        $stk->execute();
        $storedToken = $stk->fetchColumn();

        if (!$storedToken || $token !== $storedToken) {
            echo json_encode(['error' => 'Invalid sync token']);
            break;
        }

        // Build the SQL dump (same logic as db_export but return in response)
        $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $sql = "-- Church Facility Manager DB Snapshot\n";
        $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- Source: " . gethostname() . " (remote export)\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

        foreach ($tables as $table) {
            if ($table === 'sessions') continue;
            $create = $db->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM);
            $sql .= "DROP TABLE IF EXISTS `$table`;\n";
            $sql .= $create[1] . ";\n\n";

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

        echo json_encode([
            'success' => true,
            'tables' => count($tables),
            'sql' => base64_encode($sql)
        ]);
        break;
    }

    // ── Pull live DB into local (fetches from remote site) ──
    case 'db_pull_live': {
        if (!isAdmin()) { echo json_encode(['error' => 'Admin access required']); break; }

        $remoteUrl = trim($_POST['remote_url'] ?? '');
        $syncToken = trim($_POST['sync_token'] ?? '');

        if (!$remoteUrl || !$syncToken) {
            echo json_encode(['error' => 'Remote URL and sync token are required']);
            break;
        }

        // Normalize URL
        $remoteUrl = rtrim($remoteUrl, '/');
        $apiUrl = $remoteUrl . '/api/settings_api.php';

        // Fetch from remote
        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'action' => 'db_export_api',
                'sync_token' => $syncToken
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            echo json_encode(['error' => 'Connection failed: ' . $curlError]);
            break;
        }
        if ($httpCode !== 200) {
            echo json_encode(['error' => "Remote returned HTTP {$httpCode}"]);
            break;
        }

        $data = json_decode($response, true);
        if (!$data || !isset($data['success']) || !$data['success']) {
            $err = $data['error'] ?? 'Unknown error from remote';
            echo json_encode(['error' => 'Remote: ' . $err]);
            break;
        }

        $sql = base64_decode($data['sql']);
        if (!$sql) {
            echo json_encode(['error' => 'Empty or corrupt SQL from remote']);
            break;
        }

        // Also save a backup to data/db_snapshot.sql
        $root = realpath(__DIR__ . '/..');
        if (!is_dir($root . '/data')) mkdir($root . '/data', 0755, true);
        file_put_contents($root . '/data/db_snapshot.sql', $sql);

        // Import the SQL (same logic as db_import)
        $errors = [];
        $stmtCount = 0;

        $db->exec("SET FOREIGN_KEY_CHECKS = 0");

        $statements = [];
        $current = '';
        foreach (explode("\n", $sql) as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || strpos($trimmed, '--') === 0) continue;
            $current .= $line . "\n";
            if (preg_match('/;\s*$/', $trimmed)) {
                $stmt = trim($current);
                if ($stmt) $statements[] = $stmt;
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

        $db->exec("SET FOREIGN_KEY_CHECKS = 1");

        echo json_encode([
            'success' => count($errors) === 0,
            'statements' => $stmtCount,
            'tables' => $data['tables'],
            'errors' => $errors,
            'source' => $remoteUrl
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
