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

    // ═══════════════════════════════════════════════════════════
    // TASK TYPES
    // ═══════════════════════════════════════════════════════════

    case 'get_task_types':
        $stmt = $db->query("SELECT id, name, priority_order FROM task_types ORDER BY priority_order, name");
        echo json_encode($stmt->fetchAll());
        break;

    case 'save_task_type':
        $id   = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        if (!$name) { echo json_encode(['error' => 'Name required']); exit; }
        if ($id) {
            $stmt = $db->prepare("UPDATE task_types SET name=? WHERE id=?");
            $stmt->execute([$name, $id]);
        } else {
            $max = $db->query("SELECT COALESCE(MAX(priority_order),0)+1 FROM task_types")->fetchColumn();
            $stmt = $db->prepare("INSERT INTO task_types (name, priority_order) VALUES (?, ?)");
            $stmt->execute([$name, $max]);
            $id = (int)$db->lastInsertId();
        }
        echo json_encode(['success' => true, 'id' => $id]);
        break;

    // ═══════════════════════════════════════════════════════════
    // WORKERS (users) & ALL ROOMS
    // ═══════════════════════════════════════════════════════════

    case 'get_workers':
        $q = trim($_GET['q'] ?? '');
        if ($q !== '') {
            $stmt = $db->prepare("SELECT id, name FROM users WHERE is_active = 1 AND name LIKE ? ORDER BY name LIMIT 30");
            $stmt->execute(['%' . $q . '%']);
        } else {
            $stmt = $db->query("SELECT id, name FROM users WHERE is_active = 1 ORDER BY name LIMIT 30");
        }
        echo json_encode($stmt->fetchAll());
        break;

    case 'get_all_rooms':
        $q = trim($_GET['q'] ?? '');
        if ($q !== '') {
            $stmt = $db->prepare("
                SELECT rm.id, rm.name, fl.name AS floor_name, b.name AS building_name
                FROM rooms rm
                JOIN floors fl ON fl.id = rm.floor_id
                JOIN buildings b ON b.id = fl.building_id
                WHERE rm.name LIKE ?
                ORDER BY b.name, fl.name, rm.name
                LIMIT 50
            ");
            $stmt->execute(['%' . $q . '%']);
        } else {
            $stmt = $db->query("
                SELECT rm.id, rm.name, fl.name AS floor_name, b.name AS building_name
                FROM rooms rm
                JOIN floors fl ON fl.id = rm.floor_id
                JOIN buildings b ON b.id = fl.building_id
                ORDER BY b.name, fl.name, rm.name
                LIMIT 50
            ");
        }
        echo json_encode($stmt->fetchAll());
        break;

    // ═══════════════════════════════════════════════════════════
    // RESOURCE LIBRARIES (Tools, Supplies, Materials, Equipment)
    // ═══════════════════════════════════════════════════════════

    case 'get_tools':
    case 'get_supplies':
    case 'get_materials':
    case 'get_equipment':
        $table = str_replace('get_', '', $action);
        $q = trim($_GET['q'] ?? '');
        if ($q !== '') {
            $stmt = $db->prepare("SELECT id, name FROM {$table} WHERE name LIKE ? ORDER BY name LIMIT 30");
            $stmt->execute(['%' . $q . '%']);
        } else {
            $stmt = $db->query("SELECT id, name FROM {$table} ORDER BY name LIMIT 30");
        }
        echo json_encode($stmt->fetchAll());
        break;

    case 'create_tool':
    case 'create_supply':
    case 'create_material':
    case 'create_equipment':
        $tableMap = [
            'create_tool'      => 'tools',
            'create_supply'    => 'supplies',
            'create_material'  => 'materials',
            'create_equipment' => 'equipment',
        ];
        $table = $tableMap[$action];
        $name  = trim($_POST['name'] ?? '');
        if (!$name) { echo json_encode(['error' => 'Name required']); exit; }
        try {
            $stmt = $db->prepare("INSERT INTO {$table} (name) VALUES (?)");
            $stmt->execute([$name]);
            echo json_encode(['id' => (int)$db->lastInsertId(), 'name' => $name]);
        } catch (PDOException $e) {
            $stmt = $db->prepare("SELECT id, name FROM {$table} WHERE name = ?");
            $stmt->execute([$name]);
            echo json_encode($stmt->fetch());
        }
        break;

    // ═══════════════════════════════════════════════════════════
    // TASKS
    // ═══════════════════════════════════════════════════════════

    case 'get_tasks':
        $typeId   = (int)($_GET['task_type_id'] ?? 0);
        $workerId = (int)($_GET['worker_id'] ?? 0);
        $q        = trim($_GET['q'] ?? '');
        $reusableOnly = isset($_GET['reusable']) ? (int)$_GET['reusable'] : -1; // -1 = no filter
        $where  = [];
        $params = [];
        if ($typeId)   { $where[] = 't.task_type_id = ?'; $params[] = $typeId; }
        if ($q !== '') { $where[] = 't.name LIKE ?'; $params[] = '%' . $q . '%'; }
        if ($workerId) { $where[] = 't.id IN (SELECT tpw.task_id FROM task_preferred_workers tpw WHERE tpw.user_id = ?)'; $params[] = $workerId; }
        if ($reusableOnly >= 0) { $where[] = 't.reusable = ?'; $params[] = $reusableOnly; }
        $whereStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $stmt = $db->prepare("
            SELECT t.*, tt.name AS type_name
            FROM tasks t
            JOIN task_types tt ON tt.id = t.task_type_id
            {$whereStr}
            ORDER BY t.name
        ");
        $stmt->execute($params);
        $tasks = $stmt->fetchAll();

        // Load resources, rooms, workers for each task
        $stTools   = $db->prepare("SELECT r.id, r.name FROM task_tools tr JOIN tools r ON r.id = tr.tool_id WHERE tr.task_id = ?");
        $stSupp    = $db->prepare("SELECT r.id, r.name FROM task_supplies tr JOIN supplies r ON r.id = tr.supply_id WHERE tr.task_id = ?");
        $stMat     = $db->prepare("SELECT r.id, r.name FROM task_materials tr JOIN materials r ON r.id = tr.material_id WHERE tr.task_id = ?");
        $stEquip   = $db->prepare("SELECT r.id, r.name FROM task_equipment tr JOIN equipment r ON r.id = tr.equipment_id WHERE tr.task_id = ?");
        $stRooms   = $db->prepare("SELECT rm.id, rm.name FROM task_rooms tr JOIN rooms rm ON rm.id = tr.room_id WHERE tr.task_id = ?");
        $stWorkers = $db->prepare("SELECT u.id, u.name FROM task_preferred_workers tpw JOIN users u ON u.id = tpw.user_id WHERE tpw.task_id = ?");
        foreach ($tasks as &$task) {
            $tid = $task['id'];
            $stTools->execute([$tid]);    $task['tools']      = $stTools->fetchAll();
            $stSupp->execute([$tid]);     $task['supplies']   = $stSupp->fetchAll();
            $stMat->execute([$tid]);      $task['materials']  = $stMat->fetchAll();
            $stEquip->execute([$tid]);    $task['equipment']  = $stEquip->fetchAll();
            $stRooms->execute([$tid]);    $task['rooms']      = $stRooms->fetchAll();
            $stWorkers->execute([$tid]);  $task['workers']    = $stWorkers->fetchAll();
        }
        echo json_encode(array_values($tasks));
        break;

    case 'get_task':
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { echo json_encode(['error' => 'ID required']); exit; }
        $stmt = $db->prepare("SELECT t.*, tt.name AS type_name FROM tasks t JOIN task_types tt ON tt.id = t.task_type_id WHERE t.id = ?");
        $stmt->execute([$id]);
        $task = $stmt->fetch();
        if (!$task) { echo json_encode(['error' => 'Not found']); exit; }

        $s = $db->prepare("SELECT r.id, r.name FROM task_tools tr JOIN tools r ON r.id = tr.tool_id WHERE tr.task_id = ?");
        $s->execute([$id]); $task['tools'] = $s->fetchAll();

        $s = $db->prepare("SELECT r.id, r.name FROM task_supplies tr JOIN supplies r ON r.id = tr.supply_id WHERE tr.task_id = ?");
        $s->execute([$id]); $task['supplies'] = $s->fetchAll();

        $s = $db->prepare("SELECT r.id, r.name FROM task_materials tr JOIN materials r ON r.id = tr.material_id WHERE tr.task_id = ?");
        $s->execute([$id]); $task['materials'] = $s->fetchAll();

        $s = $db->prepare("SELECT r.id, r.name FROM task_equipment tr JOIN equipment r ON r.id = tr.equipment_id WHERE tr.task_id = ?");
        $s->execute([$id]); $task['equipment'] = $s->fetchAll();

        // Preferred workers
        $s = $db->prepare("SELECT u.id, u.name FROM task_preferred_workers tpw JOIN users u ON u.id = tpw.user_id WHERE tpw.task_id = ?");
        $s->execute([$id]); $task['preferred_workers'] = $s->fetchAll();

        // Assigned rooms
        $s = $db->prepare("
            SELECT rm.id, rm.name, fl.name AS floor_name, b.name AS building_name
            FROM task_rooms tr
            JOIN rooms rm ON rm.id = tr.room_id
            JOIN floors fl ON fl.id = rm.floor_id
            JOIN buildings b ON b.id = fl.building_id
            WHERE tr.task_id = ?
            ORDER BY b.name, fl.name, rm.name
        ");
        $s->execute([$id]); $task['rooms'] = $s->fetchAll();

        echo json_encode($task);
        break;

    case 'save_task':
        $id       = (int)($_POST['id'] ?? 0);
        $name     = trim($_POST['name'] ?? '');
        $desc     = trim($_POST['description'] ?? '') ?: null;
        $typeId   = (int)($_POST['task_type_id'] ?? 0);
        $estMins  = (int)($_POST['estimated_minutes'] ?? 5);
        $reusable = isset($_POST['reusable']) ? (int)$_POST['reusable'] : 1;
        if (!$name || !$typeId) { echo json_encode(['error' => 'Name and type are required']); exit; }

        if ($id) {
            $stmt = $db->prepare("UPDATE tasks SET name=?, description=?, task_type_id=?, estimated_minutes=?, reusable=? WHERE id=?");
            $stmt->execute([$name, $desc, $typeId, $estMins, $reusable, $id]);
        } else {
            $stmt = $db->prepare("INSERT INTO tasks (name, description, task_type_id, estimated_minutes, reusable) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $desc, $typeId, $estMins, $reusable]);
            $id = (int)$db->lastInsertId();
        }

        // Sync resource links
        $resourceMap = [
            'tool_ids'      => ['task_tools',      'tool_id'],
            'supply_ids'    => ['task_supplies',    'supply_id'],
            'material_ids'  => ['task_materials',   'material_id'],
            'equipment_ids' => ['task_equipment',   'equipment_id'],
        ];
        foreach ($resourceMap as $postKey => [$table, $col]) {
            $db->prepare("DELETE FROM {$table} WHERE task_id = ?")->execute([$id]);
            $rawIds = trim($_POST[$postKey] ?? '');
            if ($rawIds !== '') {
                $resIds = array_values(array_filter(array_map('intval', explode(',', $rawIds))));
                $ins = $db->prepare("INSERT IGNORE INTO {$table} (task_id, {$col}) VALUES (?, ?)");
                foreach ($resIds as $rid) { $ins->execute([$id, $rid]); }
            }
        }

        // Sync preferred workers
        $db->prepare("DELETE FROM task_preferred_workers WHERE task_id = ?")->execute([$id]);
        $rawWorkerIds = trim($_POST['worker_ids'] ?? '');
        if ($rawWorkerIds !== '') {
            $wIds = array_values(array_filter(array_map('intval', explode(',', $rawWorkerIds))));
            $ins = $db->prepare("INSERT IGNORE INTO task_preferred_workers (task_id, user_id) VALUES (?, ?)");
            foreach ($wIds as $wid) { $ins->execute([$id, $wid]); }
        }

        // Sync room assignments
        $db->prepare("DELETE FROM task_rooms WHERE task_id = ?")->execute([$id]);
        $rawRoomIds = trim($_POST['room_ids'] ?? '');
        if ($rawRoomIds !== '') {
            $rIds = array_values(array_filter(array_map('intval', explode(',', $rawRoomIds))));
            $ins = $db->prepare("INSERT IGNORE INTO task_rooms (task_id, room_id) VALUES (?, ?)");
            foreach ($rIds as $rid) { $ins->execute([$id, $rid]); }
        }

        echo json_encode(['success' => true, 'id' => $id]);
        break;

    case 'batch_save_tasks':
        // Update multiple tasks at once — only fields explicitly sent are changed
        $rawIds = trim($_POST['ids'] ?? '');
        if ($rawIds === '') { echo json_encode(['error' => 'No task IDs']); exit; }
        $ids = array_values(array_filter(array_map('intval', explode(',', $rawIds))));
        if (!$ids) { echo json_encode(['error' => 'No valid task IDs']); exit; }

        // Build dynamic UPDATE for scalar fields (only if they were sent and not "_MULTI_")
        $setClauses = [];
        $setParams  = [];
        if (isset($_POST['task_type_id']) && $_POST['task_type_id'] !== '_MULTI_') {
            $setClauses[] = 'task_type_id = ?';
            $setParams[]  = (int)$_POST['task_type_id'];
        }
        if (isset($_POST['estimated_minutes']) && $_POST['estimated_minutes'] !== '_MULTI_') {
            $setClauses[] = 'estimated_minutes = ?';
            $setParams[]  = (int)$_POST['estimated_minutes'];
        }
        if (isset($_POST['name']) && $_POST['name'] !== '_MULTI_') {
            $setClauses[] = 'name = ?';
            $setParams[]  = trim($_POST['name']);
        }
        if (isset($_POST['description']) && $_POST['description'] !== '_MULTI_') {
            $setClauses[] = 'description = ?';
            $setParams[]  = trim($_POST['description']) ?: null;
        }

        if ($setClauses) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sql = "UPDATE tasks SET " . implode(', ', $setClauses) . " WHERE id IN ({$placeholders})";
            $stmt = $db->prepare($sql);
            $stmt->execute(array_merge($setParams, $ids));
        }

        // Sync list fields per task (only if explicitly sent)
        $syncWorkers = isset($_POST['worker_ids']) && $_POST['worker_ids'] !== '_MULTI_';
        $syncRooms   = isset($_POST['room_ids'])   && $_POST['room_ids']   !== '_MULTI_';
        $syncRes     = [];
        $resourceMap = [
            'tool_ids'      => ['task_tools',      'tool_id'],
            'supply_ids'    => ['task_supplies',    'supply_id'],
            'material_ids'  => ['task_materials',   'material_id'],
            'equipment_ids' => ['task_equipment',   'equipment_id'],
        ];
        foreach ($resourceMap as $postKey => [$table, $col]) {
            if (isset($_POST[$postKey]) && $_POST[$postKey] !== '_MULTI_') {
                $syncRes[$postKey] = [$table, $col];
            }
        }

        foreach ($ids as $tid) {
            if ($syncWorkers) {
                $db->prepare("DELETE FROM task_preferred_workers WHERE task_id = ?")->execute([$tid]);
                $rawW = trim($_POST['worker_ids']);
                if ($rawW !== '') {
                    $wIds = array_values(array_filter(array_map('intval', explode(',', $rawW))));
                    $ins = $db->prepare("INSERT IGNORE INTO task_preferred_workers (task_id, user_id) VALUES (?, ?)");
                    foreach ($wIds as $wid) { $ins->execute([$tid, $wid]); }
                }
            }
            if ($syncRooms) {
                $db->prepare("DELETE FROM task_rooms WHERE task_id = ?")->execute([$tid]);
                $rawR = trim($_POST['room_ids']);
                if ($rawR !== '') {
                    $rIds = array_values(array_filter(array_map('intval', explode(',', $rawR))));
                    $ins = $db->prepare("INSERT IGNORE INTO task_rooms (task_id, room_id) VALUES (?, ?)");
                    foreach ($rIds as $rid) { $ins->execute([$tid, $rid]); }
                }
            }
            foreach ($syncRes as $postKey => [$table, $col]) {
                $db->prepare("DELETE FROM {$table} WHERE task_id = ?")->execute([$tid]);
                $rawR = trim($_POST[$postKey]);
                if ($rawR !== '') {
                    $resIds = array_values(array_filter(array_map('intval', explode(',', $rawR))));
                    $ins = $db->prepare("INSERT IGNORE INTO {$table} (task_id, {$col}) VALUES (?, ?)");
                    foreach ($resIds as $rid) { $ins->execute([$tid, $rid]); }
                }
            }
        }

        echo json_encode(['success' => true]);
        break;

    case 'delete_task':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['error' => 'ID required']); exit; }
        $db->prepare("DELETE FROM tasks WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true]);
        break;

    // ═══════════════════════════════════════════════════════════
    // TASK GROUPS
    // ═══════════════════════════════════════════════════════════

    case 'get_task_groups':
        $typeId    = (int)($_GET['task_type_id'] ?? 0);
        $workerId  = (int)($_GET['worker_id'] ?? 0);
        $q         = trim($_GET['q'] ?? '');
        $flat      = (int)($_GET['flat'] ?? 0);       // 1 = return flat list (no hierarchy)
        $parentId  = isset($_GET['parent_id']) ? ($_GET['parent_id'] === '' ? 'top' : (int)$_GET['parent_id']) : null;
        $where  = [];
        $params = [];
        if ($typeId)   { $where[] = 'tg.task_type_id = ?'; $params[] = $typeId; }
        if ($q !== '') { $where[] = 'tg.name LIKE ?'; $params[] = '%' . $q . '%'; }
        if ($workerId) { $where[] = 'tg.id IN (SELECT tgpw.task_group_id FROM task_group_preferred_workers tgpw WHERE tgpw.user_id = ?)'; $params[] = $workerId; }
        if ($parentId === 'top') { $where[] = 'tg.parent_id IS NULL'; }
        elseif ($parentId !== null) { $where[] = 'tg.parent_id = ?'; $params[] = $parentId; }
        $whereStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $stmt = $db->prepare("
            SELECT tg.*, tt.name AS type_name,
                   (SELECT COUNT(*) FROM task_group_tasks tgt WHERE tgt.task_group_id = tg.id) AS task_count,
                   (SELECT COUNT(*) FROM task_groups tg2 WHERE tg2.parent_id = tg.id) AS child_group_count
            FROM task_groups tg
            JOIN task_types tt ON tt.id = tg.task_type_id
            {$whereStr}
            ORDER BY tg.sort_order, tg.name
        ");
        $stmt->execute($params);
        $groups = $stmt->fetchAll();

        // Load rooms, workers, and tasks for each group
        $stRooms   = $db->prepare("SELECT rm.id, rm.name FROM room_default_task_groups rdtg JOIN rooms rm ON rm.id = rdtg.room_id WHERE rdtg.task_group_id = ?");
        $stWorkers = $db->prepare("SELECT u.id, u.name FROM task_group_preferred_workers tgpw JOIN users u ON u.id = tgpw.user_id WHERE tgpw.task_group_id = ?");
        $stTasks   = $db->prepare("SELECT t.id, t.name, t.reusable FROM task_group_tasks tgt JOIN tasks t ON t.id = tgt.task_id WHERE tgt.task_group_id = ? ORDER BY tgt.sort_order, t.name");
        $stTaskRooms = $db->prepare("SELECT rm.id, rm.name FROM task_rooms tr JOIN rooms rm ON rm.id = tr.room_id WHERE tr.task_id = ?");
        $stChildren  = $db->prepare("
            SELECT tg2.id, tg2.name, tg2.parent_id, tg2.sort_order, tt2.name AS type_name,
                   (SELECT COUNT(*) FROM task_group_tasks tgt2 WHERE tgt2.task_group_id = tg2.id) AS task_count,
                   (SELECT COUNT(*) FROM task_groups tg3 WHERE tg3.parent_id = tg2.id) AS child_group_count
            FROM task_groups tg2
            JOIN task_types tt2 ON tt2.id = tg2.task_type_id
            WHERE tg2.parent_id = ?
            ORDER BY tg2.sort_order, tg2.name
        ");

        foreach ($groups as &$group) {
            $gid = $group['id'];
            $stRooms->execute([$gid]);    $group['rooms']   = $stRooms->fetchAll();
            $stWorkers->execute([$gid]);  $group['workers'] = $stWorkers->fetchAll();
            $stTasks->execute([$gid]);    $group['tasks']   = $stTasks->fetchAll();
            foreach ($group['tasks'] as &$task) {
                $stTaskRooms->execute([$task['id']]);
                $task['rooms'] = $stTaskRooms->fetchAll();
            }
            // Include child groups (one level deep) unless flat mode
            if (!$flat) {
                $stChildren->execute([$gid]);
                $group['children'] = $stChildren->fetchAll();
            }
        }
        echo json_encode(array_values($groups));
        break;

    case 'get_task_group':
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { echo json_encode(['error' => 'ID required']); exit; }
        $stmt = $db->prepare("SELECT tg.*, tt.name AS type_name FROM task_groups tg JOIN task_types tt ON tt.id = tg.task_type_id WHERE tg.id = ?");
        $stmt->execute([$id]);
        $group = $stmt->fetch();
        if (!$group) { echo json_encode(['error' => 'Not found']); exit; }

        $s = $db->prepare("
            SELECT t.id, t.name, t.estimated_minutes, t.reusable, tgt.sort_order
            FROM task_group_tasks tgt
            JOIN tasks t ON t.id = tgt.task_id
            WHERE tgt.task_group_id = ?
            ORDER BY tgt.sort_order, t.name
        ");
        $s->execute([$id]);
        $group['tasks'] = $s->fetchAll();

        // Child groups
        $s = $db->prepare("
            SELECT tg2.id, tg2.name, tg2.description, tg2.parent_id, tg2.sort_order,
                   tg2.task_type_id, tg2.estimated_minutes, tt2.name AS type_name,
                   (SELECT COUNT(*) FROM task_group_tasks tgt2 WHERE tgt2.task_group_id = tg2.id) AS task_count,
                   (SELECT COUNT(*) FROM task_groups tg3 WHERE tg3.parent_id = tg2.id) AS child_group_count
            FROM task_groups tg2
            JOIN task_types tt2 ON tt2.id = tg2.task_type_id
            WHERE tg2.parent_id = ?
            ORDER BY tg2.sort_order, tg2.name
        ");
        $s->execute([$id]);
        $group['children'] = $s->fetchAll();

        // Preferred workers
        $s = $db->prepare("SELECT u.id, u.name FROM task_group_preferred_workers tgpw JOIN users u ON u.id = tgpw.user_id WHERE tgpw.task_group_id = ?");
        $s->execute([$id]); $group['preferred_workers'] = $s->fetchAll();

        // Assigned rooms (rooms that have this group as a default)
        $s = $db->prepare("
            SELECT rm.id, rm.name, fl.name AS floor_name, b.name AS building_name
            FROM room_default_task_groups rdtg
            JOIN rooms rm ON rm.id = rdtg.room_id
            JOIN floors fl ON fl.id = rm.floor_id
            JOIN buildings b ON b.id = fl.building_id
            WHERE rdtg.task_group_id = ?
            ORDER BY b.name, fl.name, rm.name
        ");
        $s->execute([$id]); $group['rooms'] = $s->fetchAll();

        echo json_encode($group);
        break;

    case 'save_task_group':
        $id        = (int)($_POST['id'] ?? 0);
        $name      = trim($_POST['name'] ?? '');
        $desc      = trim($_POST['description'] ?? '') ?: null;
        $typeId    = (int)($_POST['task_type_id'] ?? 0);
        $estMins   = (int)($_POST['estimated_minutes'] ?? 15);
        $parentId  = isset($_POST['parent_id']) && $_POST['parent_id'] !== '' ? (int)$_POST['parent_id'] : null;
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        if (!$name || !$typeId) { echo json_encode(['error' => 'Name and type are required']); exit; }

        // Prevent circular parent reference
        if ($parentId && $id && $parentId == $id) {
            echo json_encode(['error' => 'A group cannot be its own parent']); exit;
        }

        if ($id) {
            $stmt = $db->prepare("UPDATE task_groups SET name=?, description=?, task_type_id=?, estimated_minutes=?, parent_id=?, sort_order=? WHERE id=?");
            $stmt->execute([$name, $desc, $typeId, $estMins, $parentId, $sortOrder, $id]);
        } else {
            $stmt = $db->prepare("INSERT INTO task_groups (name, description, task_type_id, estimated_minutes, parent_id, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $desc, $typeId, $estMins, $parentId, $sortOrder]);
            $id = (int)$db->lastInsertId();
        }

        // Sync task list
        $db->prepare("DELETE FROM task_group_tasks WHERE task_group_id = ?")->execute([$id]);
        $rawTaskIds = trim($_POST['task_ids'] ?? '');
        if ($rawTaskIds !== '') {
            $taskIds = array_values(array_filter(array_map('intval', explode(',', $rawTaskIds))));
            $ins = $db->prepare("INSERT IGNORE INTO task_group_tasks (task_group_id, task_id, sort_order) VALUES (?, ?, ?)");
            foreach ($taskIds as $i => $tid) { $ins->execute([$id, $tid, $i]); }
        }

        // Sync preferred workers
        $db->prepare("DELETE FROM task_group_preferred_workers WHERE task_group_id = ?")->execute([$id]);
        $rawWorkerIds = trim($_POST['worker_ids'] ?? '');
        if ($rawWorkerIds !== '') {
            $wIds = array_values(array_filter(array_map('intval', explode(',', $rawWorkerIds))));
            $ins = $db->prepare("INSERT IGNORE INTO task_group_preferred_workers (task_group_id, user_id) VALUES (?, ?)");
            foreach ($wIds as $wid) { $ins->execute([$id, $wid]); }
        }

        // Sync room assignments (room_default_task_groups)
        $db->prepare("DELETE FROM room_default_task_groups WHERE task_group_id = ?")->execute([$id]);
        $rawRoomIds = trim($_POST['room_ids'] ?? '');
        if ($rawRoomIds !== '') {
            $rIds = array_values(array_filter(array_map('intval', explode(',', $rawRoomIds))));
            $ins = $db->prepare("INSERT IGNORE INTO room_default_task_groups (room_id, task_group_id) VALUES (?, ?)");
            foreach ($rIds as $rid) { $ins->execute([$rid, $id]); }
        }

        echo json_encode(['success' => true, 'id' => $id]);
        break;

    case 'set_group_parent':
        $id       = (int)($_POST['id'] ?? 0);
        $parentId = isset($_POST['parent_id']) && $_POST['parent_id'] !== '' ? (int)$_POST['parent_id'] : null;
        if (!$id) { echo json_encode(['error' => 'ID required']); exit; }
        if ($parentId && $parentId == $id) { echo json_encode(['error' => 'Cannot be its own parent']); exit; }
        $stmt = $db->prepare("UPDATE task_groups SET parent_id = ? WHERE id = ?");
        $stmt->execute([$parentId, $id]);
        echo json_encode(['success' => true]);
        break;

    case 'delete_task_group':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['error' => 'ID required']); exit; }
        $db->prepare("DELETE FROM task_groups WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true]);
        break;

    // ═══════════════════════════════════════════════════════════
    // ROOM DEFAULT TASK GROUPS
    // ═══════════════════════════════════════════════════════════

    case 'get_room_defaults':
        $roomId = (int)($_GET['room_id'] ?? 0);
        if (!$roomId) { echo json_encode([]); exit; }
        $stmt = $db->prepare("
            SELECT tg.id, tg.name, tg.estimated_minutes, tt.name AS type_name
            FROM room_default_task_groups rdtg
            JOIN task_groups tg ON tg.id = rdtg.task_group_id
            JOIN task_types tt ON tt.id = tg.task_type_id
            WHERE rdtg.room_id = ?
            ORDER BY tg.name
        ");
        $stmt->execute([$roomId]);
        echo json_encode($stmt->fetchAll());
        break;

    case 'save_room_defaults':
        $roomId = (int)($_POST['room_id'] ?? 0);
        if (!$roomId) { echo json_encode(['error' => 'Room ID required']); exit; }
        $db->prepare("DELETE FROM room_default_task_groups WHERE room_id = ?")->execute([$roomId]);
        $rawIds = trim($_POST['task_group_ids'] ?? '');
        if ($rawIds !== '') {
            $gIds = array_values(array_filter(array_map('intval', explode(',', $rawIds))));
            $ins = $db->prepare("INSERT IGNORE INTO room_default_task_groups (room_id, task_group_id) VALUES (?, ?)");
            foreach ($gIds as $gid) { $ins->execute([$roomId, $gid]); }
        }
        echo json_encode(['success' => true]);
        break;

    // ═══════════════════════════════════════════════════════════
    // JANITOR ASSIGNMENTS
    // ═══════════════════════════════════════════════════════════

    case 'get_janitor_assignments':
        $date   = $_GET['date'] ?? date('Y-m-d');
        $userId = (int)($_GET['user_id'] ?? 0);
        $where  = ['ja.assigned_date = ?'];
        $params = [$date];
        if ($userId) { $where[] = 'ja.assigned_to = ?'; $params[] = $userId; }
        $whereStr = implode(' AND ', $where);
        $stmt = $db->prepare("
            SELECT ja.*,
                   COALESCE(tg.name, t_single.name) AS group_name,
                   COALESCE(tg.estimated_minutes, 0) AS estimated_minutes,
                   COALESCE(tt.name, tt2.name, '') AS type_name,
                   rm.name AS room_name,
                   fl.name AS floor_name,
                   b.name  AS building_name
            FROM janitor_task_assignments ja
            LEFT JOIN task_groups tg ON tg.id = ja.task_group_id
            LEFT JOIN task_types tt  ON tt.id = tg.task_type_id
            LEFT JOIN tasks t_single ON t_single.id = ja.task_id AND ja.task_group_id IS NULL
            LEFT JOIN task_types tt2 ON tt2.id = t_single.task_type_id
            JOIN rooms rm       ON rm.id = ja.room_id
            JOIN floors fl      ON fl.id = rm.floor_id
            JOIN buildings b    ON b.id  = fl.building_id
            WHERE {$whereStr}
            ORDER BY COALESCE(tt.priority_order, tt2.priority_order, 999), ja.deadline, COALESCE(tg.name, t_single.name)
        ");
        $stmt->execute($params);
        $assignments = $stmt->fetchAll();

        // Load checklist for each, with sub-group name for nested groups
        $stChecklist = $db->prepare("
            SELECT jtc.task_id, t.name AS task_name, jtc.completed, jtc.completed_at,
                   tgt_sub.task_group_id AS sub_group_id
            FROM janitor_task_checklist jtc
            JOIN tasks t ON t.id = jtc.task_id
            LEFT JOIN task_group_tasks tgt_sub ON tgt_sub.task_id = t.id
            WHERE jtc.assignment_id = ?
            ORDER BY tgt_sub.task_group_id, tgt_sub.sort_order, t.name
        ");
        $stSubGroupName = $db->prepare("SELECT name FROM task_groups WHERE id = ?");

        foreach ($assignments as &$a) {
            $stChecklist->execute([$a['id']]);
            $items = $stChecklist->fetchAll();

            // Resolve sub-group names (only for child groups of this assignment's group)
            $mainGroupId = $a['task_group_id'];
            $sgNameCache = [];
            foreach ($items as &$item) {
                $sgId = $item['sub_group_id'];
                $item['sub_group_name'] = null;
                // Only show sub-group name if the task's group is a child of the main assignment group
                if ($sgId && $mainGroupId && $sgId != $mainGroupId) {
                    if (!isset($sgNameCache[$sgId])) {
                        $stSubGroupName->execute([$sgId]);
                        $row = $stSubGroupName->fetch();
                        $sgNameCache[$sgId] = $row ? $row['name'] : null;
                    }
                    $item['sub_group_name'] = $sgNameCache[$sgId];
                }
                unset($item['sub_group_id']); // don't leak internal id
            }
            $a['checklist'] = $items;
        }

        echo json_encode(array_values($assignments));
        break;

    case 'toggle_checklist_item':
        $assignmentId = (int)($_POST['assignment_id'] ?? 0);
        $taskId       = (int)($_POST['task_id'] ?? 0);
        $completed    = (int)($_POST['completed'] ?? 0);
        if (!$assignmentId || !$taskId) { echo json_encode(['error' => 'Assignment and task IDs required']); exit; }

        $currentUser = getCurrentUser();
        $completedBy = $completed ? (int)$currentUser['id'] : null;

        $stmt = $db->prepare("
            UPDATE janitor_task_checklist
            SET completed = ?, completed_at = IF(? = 1, NOW(), NULL), completed_by = IF(? = 1, ?, NULL)
            WHERE assignment_id = ? AND task_id = ?
        ");
        $stmt->execute([$completed, $completed, $completed, $completedBy, $assignmentId, $taskId]);

        // Check if all items are complete → update assignment status
        $s = $db->prepare("SELECT COUNT(*) AS total, SUM(completed) AS done FROM janitor_task_checklist WHERE assignment_id = ?");
        $s->execute([$assignmentId]);
        $counts = $s->fetch();
        $newStatus = ($counts['total'] > 0 && $counts['total'] == $counts['done']) ? 'completed' : 'in_progress';
        $db->prepare("UPDATE janitor_task_assignments SET status = ?, completed_at = IF(? = 'completed', NOW(), NULL) WHERE id = ?")
           ->execute([$newStatus, $newStatus, $assignmentId]);

        echo json_encode(['success' => true, 'assignment_status' => $newStatus]);
        break;

    case 'update_assignment_status':
        $id     = (int)($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        if (!$id || !in_array($status, ['pending','in_progress','completed'])) {
            echo json_encode(['error' => 'Valid ID and status required']); exit;
        }
        $db->prepare("UPDATE janitor_task_assignments SET status = ?, completed_at = IF(? = 'completed', NOW(), NULL) WHERE id = ?")
           ->execute([$status, $status, $id]);
        echo json_encode(['success' => true]);
        break;

    // ═══════════════════════════════════════════════════════════
    // ROOM-FILTERED QUERIES
    // ═══════════════════════════════════════════════════════════

    case 'get_groups_by_rooms':
        // Returns task groups assigned to any of the given room IDs
        // via room_default_task_groups OR containing tasks linked via task_rooms
        $rawIds   = trim($_GET['room_ids'] ?? '');
        $workerId = (int)($_GET['worker_id'] ?? 0);
        if ($rawIds === '') { echo json_encode([]); exit; }
        $roomIds = array_values(array_filter(array_map('intval', explode(',', $rawIds))));
        if (!$roomIds) { echo json_encode([]); exit; }
        $placeholders = implode(',', array_fill(0, count($roomIds), '?'));
        $workerWhere  = '';
        $execParams   = array_merge($roomIds, $roomIds);
        if ($workerId) {
            $workerWhere = 'AND tg.id IN (SELECT tgpw.task_group_id FROM task_group_preferred_workers tgpw WHERE tgpw.user_id = ?)';
            $execParams[] = $workerId;
        }
        $stmt = $db->prepare("
            SELECT DISTINCT tg.*, tt.name AS type_name,
                   (SELECT COUNT(*) FROM task_group_tasks tgt WHERE tgt.task_group_id = tg.id) AS task_count
            FROM task_groups tg
            JOIN task_types tt ON tt.id = tg.task_type_id
            WHERE tg.id IN (
                SELECT rdtg.task_group_id FROM room_default_task_groups rdtg WHERE rdtg.room_id IN ({$placeholders})
                UNION
                SELECT tgt2.task_group_id FROM task_group_tasks tgt2
                    JOIN task_rooms tr2 ON tr2.task_id = tgt2.task_id
                    WHERE tr2.room_id IN ({$placeholders})
            )
            {$workerWhere}
            ORDER BY tg.name
        ");
        $stmt->execute($execParams);
        $groups = $stmt->fetchAll();
        // Load rooms, workers, and tasks for each group
        $stRooms   = $db->prepare("SELECT rm.id, rm.name FROM room_default_task_groups rdtg JOIN rooms rm ON rm.id = rdtg.room_id WHERE rdtg.task_group_id = ?");
        $stWorkers = $db->prepare("SELECT u.id, u.name FROM task_group_preferred_workers tgpw JOIN users u ON u.id = tgpw.user_id WHERE tgpw.task_group_id = ?");
        $stTasks   = $db->prepare("SELECT t.id, t.name FROM task_group_tasks tgt JOIN tasks t ON t.id = tgt.task_id WHERE tgt.task_group_id = ? ORDER BY tgt.sort_order, t.name");
        $stTaskRooms = $db->prepare("SELECT rm.id, rm.name FROM task_rooms tr JOIN rooms rm ON rm.id = tr.room_id WHERE tr.task_id = ?");
        foreach ($groups as &$group) {
            $gid = $group['id'];
            $stRooms->execute([$gid]);    $group['rooms']   = $stRooms->fetchAll();
            $stWorkers->execute([$gid]);  $group['workers'] = $stWorkers->fetchAll();
            $stTasks->execute([$gid]);    $group['tasks']   = $stTasks->fetchAll();
            foreach ($group['tasks'] as &$task) {
                $stTaskRooms->execute([$task['id']]);
                $task['rooms'] = $stTaskRooms->fetchAll();
            }
        }
        echo json_encode(array_values($groups));
        break;

    case 'get_tasks_by_rooms':
        // Returns tasks linked to the given rooms via task_rooms directly,
        // OR indirectly through task_group_tasks → room_default_task_groups
        $rawIds   = trim($_GET['room_ids'] ?? '');
        $workerId = (int)($_GET['worker_id'] ?? 0);
        if ($rawIds === '') { echo json_encode([]); exit; }
        $roomIds = array_values(array_filter(array_map('intval', explode(',', $rawIds))));
        if (!$roomIds) { echo json_encode([]); exit; }
        $placeholders = implode(',', array_fill(0, count($roomIds), '?'));
        $workerWhere  = '';
        $execParams   = array_merge($roomIds, $roomIds);
        if ($workerId) {
            $workerWhere = 'AND t.id IN (SELECT tpw.task_id FROM task_preferred_workers tpw WHERE tpw.user_id = ?)';
            $execParams[] = $workerId;
        }
        $stmt = $db->prepare("
            SELECT DISTINCT t.*, tt.name AS type_name
            FROM tasks t
            JOIN task_types tt ON tt.id = t.task_type_id
            WHERE t.id IN (
                SELECT tr.task_id FROM task_rooms tr WHERE tr.room_id IN ({$placeholders})
                UNION
                SELECT tgt.task_id FROM task_group_tasks tgt
                    JOIN room_default_task_groups rdtg ON rdtg.task_group_id = tgt.task_group_id
                    WHERE rdtg.room_id IN ({$placeholders})
            )
            {$workerWhere}
            ORDER BY t.name
        ");
        $stmt->execute($execParams);
        $tasks = $stmt->fetchAll();

        // Load resources, rooms, workers for each task
        $stTools   = $db->prepare("SELECT r.id, r.name FROM task_tools tr JOIN tools r ON r.id = tr.tool_id WHERE tr.task_id = ?");
        $stSupp    = $db->prepare("SELECT r.id, r.name FROM task_supplies tr JOIN supplies r ON r.id = tr.supply_id WHERE tr.task_id = ?");
        $stMat     = $db->prepare("SELECT r.id, r.name FROM task_materials tr JOIN materials r ON r.id = tr.material_id WHERE tr.task_id = ?");
        $stEquip   = $db->prepare("SELECT r.id, r.name FROM task_equipment tr JOIN equipment r ON r.id = tr.equipment_id WHERE tr.task_id = ?");
        $stRooms   = $db->prepare("SELECT rm.id, rm.name FROM task_rooms tr JOIN rooms rm ON rm.id = tr.room_id WHERE tr.task_id = ?");
        $stWorkers = $db->prepare("SELECT u.id, u.name FROM task_preferred_workers tpw JOIN users u ON u.id = tpw.user_id WHERE tpw.task_id = ?");
        foreach ($tasks as &$task) {
            $tid = $task['id'];
            $stTools->execute([$tid]);    $task['tools']      = $stTools->fetchAll();
            $stSupp->execute([$tid]);     $task['supplies']   = $stSupp->fetchAll();
            $stMat->execute([$tid]);      $task['materials']  = $stMat->fetchAll();
            $stEquip->execute([$tid]);    $task['equipment']  = $stEquip->fetchAll();
            $stRooms->execute([$tid]);    $task['rooms']      = $stRooms->fetchAll();
            $stWorkers->execute([$tid]);  $task['workers']    = $stWorkers->fetchAll();
        }
        echo json_encode(array_values($tasks));
        break;

    // ═══════════════════════════════════════════════════════════
    // RECURSIVE TASK COLLECTION FROM GROUP TREE
    // ═══════════════════════════════════════════════════════════

    case 'get_group_leaf_tasks':
        // Given a group id, recursively collect ALL leaf tasks from it and all descendant groups
        $groupId = (int)($_GET['group_id'] ?? 0);
        if (!$groupId) { echo json_encode(['error' => 'group_id required']); exit; }

        $allTasks = [];
        $visited = [];
        $stGroupTasks = $db->prepare("SELECT t.id, t.name, t.estimated_minutes, t.reusable FROM task_group_tasks tgt JOIN tasks t ON t.id = tgt.task_id WHERE tgt.task_group_id = ? ORDER BY tgt.sort_order, t.name");
        $stChildGroups = $db->prepare("SELECT id FROM task_groups WHERE parent_id = ? ORDER BY sort_order, name");

        function collectLeafTasks($gid, $db, $stGroupTasks, $stChildGroups, &$allTasks, &$visited) {
            if (in_array($gid, $visited)) return; // prevent infinite loops
            $visited[] = $gid;

            // Collect direct tasks
            $stGroupTasks->execute([$gid]);
            foreach ($stGroupTasks->fetchAll() as $task) {
                $allTasks[$task['id']] = $task; // dedup by id
            }

            // Recurse into children
            $stChildGroups->execute([$gid]);
            foreach ($stChildGroups->fetchAll() as $child) {
                collectLeafTasks($child['id'], $db, $stGroupTasks, $stChildGroups, $allTasks, $visited);
            }
        }

        collectLeafTasks($groupId, $db, $stGroupTasks, $stChildGroups, $allTasks, $visited);
        echo json_encode(array_values($allTasks));
        break;

    // ═══════════════════════════════════════════════════════════
    // GET GROUP HIERARCHY TREE (full recursive tree for a group)
    // ═══════════════════════════════════════════════════════════

    case 'get_group_tree':
        $groupId = (int)($_GET['group_id'] ?? 0);
        if (!$groupId) { echo json_encode(['error' => 'group_id required']); exit; }

        function buildGroupTree($gid, $db) {
            $stGroup = $db->prepare("
                SELECT tg.*, tt.name AS type_name
                FROM task_groups tg
                JOIN task_types tt ON tt.id = tg.task_type_id
                WHERE tg.id = ?
            ");
            $stGroup->execute([$gid]);
            $group = $stGroup->fetch();
            if (!$group) return null;

            // Tasks in this group
            $stTasks = $db->prepare("SELECT t.id, t.name, t.estimated_minutes, t.reusable, tgt.sort_order FROM task_group_tasks tgt JOIN tasks t ON t.id = tgt.task_id WHERE tgt.task_group_id = ? ORDER BY tgt.sort_order, t.name");
            $stTasks->execute([$gid]);
            $group['tasks'] = $stTasks->fetchAll();

            // Child groups (recursive)
            $stChildren = $db->prepare("SELECT id FROM task_groups WHERE parent_id = ? ORDER BY sort_order, name");
            $stChildren->execute([$gid]);
            $group['children'] = [];
            foreach ($stChildren->fetchAll() as $child) {
                $childTree = buildGroupTree($child['id'], $db);
                if ($childTree) $group['children'][] = $childTree;
            }

            return $group;
        }

        $tree = buildGroupTree($groupId, $db);
        echo json_encode($tree ?: ['error' => 'Not found']);
        break;

    // ═══════════════════════════════════════════════════════════
    // TASK COMPLETION LOG
    // ═══════════════════════════════════════════════════════════

    case 'get_task_log':
        // Check role access
        $user = getCurrentUser();
        if ($user['role'] !== 'admin' && $user['role'] !== 'scheduler') {
            echo json_encode(['error' => 'Access denied']);
            exit;
        }

        $startDate = trim($_GET['start_date'] ?? '');
        $endDate = trim($_GET['end_date'] ?? '');
        $userId = (int)($_GET['user_id'] ?? 0);
        $roomId = (int)($_GET['room_id'] ?? 0);
        $buildingId = (int)($_GET['building_id'] ?? 0);
        $taskSearch = trim($_GET['task_search'] ?? '');

        $where = [];
        $params = [];

        // Date range filter
        if ($startDate) {
            $where[] = "DATE(jtc.completed_at) >= ?";
            $params[] = $startDate;
        }
        if ($endDate) {
            $where[] = "DATE(jtc.completed_at) <= ?";
            $params[] = $endDate;
        }

        // Worker filter
        if ($userId) {
            $where[] = "jtc.completed_by = ?";
            $params[] = $userId;
        }

        // Room filter
        if ($roomId) {
            $where[] = "jta.room_id = ?";
            $params[] = $roomId;
        }

        // Building filter (via rooms → floors → buildings)
        if ($buildingId) {
            $where[] = "b.id = ?";
            $params[] = $buildingId;
        }

        // Task name search
        if ($taskSearch) {
            $where[] = "t.name LIKE ?";
            $params[] = '%' . $taskSearch . '%';
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $db->prepare("
            SELECT
                jtc.completed_at,
                u.name AS worker_name,
                t.name AS task_name,
                tg.name AS group_name,
                rm.name AS room_name,
                b.name AS building_name,
                jtc.completed
            FROM janitor_task_checklist jtc
            LEFT JOIN janitor_task_assignments jta ON jta.id = jtc.assignment_id
            LEFT JOIN users u ON u.id = jtc.completed_by
            LEFT JOIN tasks t ON t.id = jtc.task_id
            LEFT JOIN task_groups tg ON tg.id = jta.task_group_id
            LEFT JOIN rooms rm ON rm.id = jta.room_id
            LEFT JOIN floors fl ON fl.id = rm.floor_id
            LEFT JOIN buildings b ON b.id = fl.building_id
            {$whereClause}
            ORDER BY jtc.completed_at DESC
            LIMIT 500
        ");

        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        echo json_encode($rows);
        break;

    default:
        echo json_encode(['error' => 'Unknown action: ' . htmlspecialchars($action)]);
}
