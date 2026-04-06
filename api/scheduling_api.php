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

// Helper: recursively collect all leaf task IDs from a task group and its children
function collectLeafTaskIds($db, $groupId, &$visited = []) {
    if (in_array($groupId, $visited)) return []; // prevent infinite loops
    $visited[] = $groupId;

    $taskIds = [];

    // Direct tasks in this group
    $stmt = $db->prepare("SELECT task_id FROM task_group_tasks WHERE task_group_id = ?");
    $stmt->execute([$groupId]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $tid) {
        $taskIds[$tid] = $tid; // dedup by key
    }

    // Child groups (recursive)
    $childStmt = $db->prepare("SELECT id FROM task_groups WHERE parent_id = ?");
    $childStmt->execute([$groupId]);
    foreach ($childStmt->fetchAll(PDO::FETCH_COLUMN) as $childId) {
        foreach (collectLeafTaskIds($db, $childId, $visited) as $tid) {
            $taskIds[$tid] = $tid;
        }
    }

    return array_values($taskIds);
}

switch ($action) {

    // ═══════════════════════════════════════════════════════════
    // GET ALL SCHEDULES (with rooms + task groups)
    // ═══════════════════════════════════════════════════════════
    case 'get_schedules': {
        $schedules = $db->query("
            SELECT cs.*,
                   u.name AS assigned_user_name
            FROM cleaning_schedules cs
            LEFT JOIN users u ON cs.assign_to_user_id = u.id
            ORDER BY cs.name
        ")->fetchAll();

        foreach ($schedules as &$s) {
            $s['rooms'] = $db->prepare("
                SELECT r.id, r.name, r.room_number, f.id AS floor_id, b.id AS building_id, b.name AS building_name
                FROM cleaning_schedule_rooms csr
                JOIN rooms r ON csr.room_id = r.id
                JOIN floors f ON r.floor_id = f.id
                JOIN buildings b ON f.building_id = b.id
                WHERE csr.schedule_id = ?
                ORDER BY b.name, r.name
            ")->execute([$s['id']]) ? $db->prepare("
                SELECT r.id, r.name, r.room_number, f.id AS floor_id, b.id AS building_id, b.name AS building_name
                FROM cleaning_schedule_rooms csr
                JOIN rooms r ON csr.room_id = r.id
                JOIN floors f ON r.floor_id = f.id
                JOIN buildings b ON f.building_id = b.id
                WHERE csr.schedule_id = ?
                ORDER BY b.name, r.name
            ") : null;
            // re-run cleaner
            $stmt = $db->prepare("
                SELECT r.id, r.name, r.room_number, b.name AS building_name
                FROM cleaning_schedule_rooms csr
                JOIN rooms r ON csr.room_id = r.id
                JOIN floors f ON r.floor_id = f.id
                JOIN buildings b ON f.building_id = b.id
                WHERE csr.schedule_id = ?
                ORDER BY b.name, r.name
            ");
            $stmt->execute([$s['id']]);
            $s['rooms'] = $stmt->fetchAll();

            $stmt2 = $db->prepare("
                SELECT tg.id, tg.name, tg.estimated_minutes
                FROM cleaning_schedule_task_groups cstg
                JOIN task_groups tg ON cstg.task_group_id = tg.id
                WHERE cstg.schedule_id = ?
                ORDER BY tg.name
            ");
            $stmt2->execute([$s['id']]);
            $s['task_groups'] = $stmt2->fetchAll();

            $stmt3 = $db->prepare("
                SELECT t.id, t.name
                FROM cleaning_schedule_tasks cst
                JOIN tasks t ON cst.task_id = t.id
                WHERE cst.schedule_id = ?
                ORDER BY t.name
            ");
            $stmt3->execute([$s['id']]);
            $s['tasks'] = $stmt3->fetchAll();

            $s['frequency_config'] = $s['frequency_config'] ? json_decode($s['frequency_config'], true) : null;
        }
        unset($s);
        echo json_encode($schedules);
        break;
    }

    // ═══════════════════════════════════════════════════════════
    // GET SCHEDULES FOR SPECIFIC ROOMS
    // ═══════════════════════════════════════════════════════════
    case 'get_schedules_for_rooms': {
        $roomIds = $_GET['room_ids'] ?? '';
        if (!$roomIds) { echo json_encode([]); break; }
        $ids = array_map('intval', explode(',', $roomIds));
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $stmt = $db->prepare("
            SELECT DISTINCT cs.*, u.name AS assigned_user_name
            FROM cleaning_schedules cs
            JOIN cleaning_schedule_rooms csr ON cs.id = csr.schedule_id
            LEFT JOIN users u ON cs.assign_to_user_id = u.id
            WHERE csr.room_id IN ($placeholders)
            ORDER BY cs.name
        ");
        $stmt->execute($ids);
        $schedules = $stmt->fetchAll();

        foreach ($schedules as &$s) {
            $stmt2 = $db->prepare("SELECT r.id, r.name, r.room_number, b.name AS building_name
                FROM cleaning_schedule_rooms csr JOIN rooms r ON csr.room_id = r.id
                JOIN floors f ON r.floor_id = f.id JOIN buildings b ON f.building_id = b.id
                WHERE csr.schedule_id = ? ORDER BY b.name, r.name");
            $stmt2->execute([$s['id']]);
            $s['rooms'] = $stmt2->fetchAll();

            $stmt3 = $db->prepare("SELECT tg.id, tg.name, tg.estimated_minutes
                FROM cleaning_schedule_task_groups cstg JOIN task_groups tg ON cstg.task_group_id = tg.id
                WHERE cstg.schedule_id = ? ORDER BY tg.name");
            $stmt3->execute([$s['id']]);
            $s['task_groups'] = $stmt3->fetchAll();

            $stmt4 = $db->prepare("SELECT t.id, t.name FROM cleaning_schedule_tasks cst JOIN tasks t ON cst.task_id = t.id WHERE cst.schedule_id = ? ORDER BY t.name");
            $stmt4->execute([$s['id']]);
            $s['tasks'] = $stmt4->fetchAll();

            $s['frequency_config'] = $s['frequency_config'] ? json_decode($s['frequency_config'], true) : null;
        }
        unset($s);
        echo json_encode($schedules);
        break;
    }

    // ═══════════════════════════════════════════════════════════
    // SAVE SCHEDULE (create or update)
    // ═══════════════════════════════════════════════════════════
    case 'save_schedule': {
        $id             = (int)($_POST['id'] ?? 0);
        $frequency      = trim($_POST['frequency'] ?? 'weekly');
        $frequencyConfig = $_POST['frequency_config'] ?? null;
        $assignToType   = trim($_POST['assign_to_type'] ?? 'user');
        $assignToUserId = ($_POST['assign_to_user_id'] ?? '') !== '' ? (int)$_POST['assign_to_user_id'] : null;
        $assignToRole   = trim($_POST['assign_to_role'] ?? '') ?: null;
        $deadlineTime   = trim($_POST['deadline_time'] ?? '') ?: null;
        $isActive       = (int)($_POST['is_active'] ?? 1);
        $roomIds        = array_map('intval', (array)($_POST['room_ids'] ?? []));
        $taskGroupIds   = array_map('intval', (array)($_POST['task_group_ids'] ?? []));
        $taskIds        = array_map('intval', (array)($_POST['task_ids'] ?? []));

        if (empty($roomIds)) { echo json_encode(['error' => 'At least one room is required']); break; }
        if (empty($taskGroupIds) && empty($taskIds)) { echo json_encode(['error' => 'At least one task group or individual task is required']); break; }

        $validFreqs = ['daily','weekdays','specific_days','weekly','biweekly','monthly','yearly'];
        if (!in_array($frequency, $validFreqs)) { echo json_encode(['error' => 'Invalid frequency']); break; }

        $freqJson = $frequencyConfig ? (is_string($frequencyConfig) ? $frequencyConfig : json_encode($frequencyConfig)) : null;
        $user = getCurrentUser();

        // ── EDITING an existing rule: 1 rule = 1 room + 1 task/group ──
        if ($id) {
            // Get current task info to build name
            $curTg = $db->prepare("SELECT task_group_id FROM cleaning_schedule_task_groups WHERE schedule_id = ?");
            $curTg->execute([$id]);
            $curTgId = $curTg->fetchColumn();
            $curT = $db->prepare("SELECT task_id FROM cleaning_schedule_tasks WHERE schedule_id = ?");
            $curT->execute([$id]);
            $curTId = $curT->fetchColumn();

            // Use the first selected task/group for the name (editing = single rule)
            $editTgIds = !empty($taskGroupIds) ? $taskGroupIds : [];
            $editTIds  = !empty($taskIds) ? $taskIds : [];
            $name = 'Schedule';
            if (!empty($editTgIds)) {
                $n = $db->prepare("SELECT name FROM task_groups WHERE id = ?");
                $n->execute([$editTgIds[0]]);
                $name = $n->fetchColumn() ?: 'Schedule';
            } elseif (!empty($editTIds)) {
                $n = $db->prepare("SELECT name FROM tasks WHERE id = ?");
                $n->execute([$editTIds[0]]);
                $name = $n->fetchColumn() ?: 'Schedule';
            }

            $db->beginTransaction();
            try {
                $stmt = $db->prepare("UPDATE cleaning_schedules SET name=?, frequency=?, frequency_config=?,
                    assign_to_type=?, assign_to_user_id=?, assign_to_role=?, deadline_time=?, is_active=?
                    WHERE id=?");
                $stmt->execute([$name, $frequency, $freqJson, $assignToType, $assignToUserId, $assignToRole, $deadlineTime, $isActive, $id]);

                // Sync room (single)
                $db->prepare("DELETE FROM cleaning_schedule_rooms WHERE schedule_id = ?")->execute([$id]);
                $db->prepare("INSERT INTO cleaning_schedule_rooms (schedule_id, room_id) VALUES (?, ?)")->execute([$id, $roomIds[0]]);

                // Sync task group or task (single)
                $db->prepare("DELETE FROM cleaning_schedule_task_groups WHERE schedule_id = ?")->execute([$id]);
                $db->prepare("DELETE FROM cleaning_schedule_tasks WHERE schedule_id = ?")->execute([$id]);
                if (!empty($editTgIds)) {
                    $db->prepare("INSERT INTO cleaning_schedule_task_groups (schedule_id, task_group_id) VALUES (?, ?)")->execute([$id, $editTgIds[0]]);
                } elseif (!empty($editTIds)) {
                    $db->prepare("INSERT INTO cleaning_schedule_tasks (schedule_id, task_id) VALUES (?, ?)")->execute([$id, $editTIds[0]]);
                }

                $db->commit();
                echo json_encode(['success' => true, 'id' => $id]);
            } catch (Exception $e) {
                $db->rollBack();
                echo json_encode(['error' => $e->getMessage()]);
            }
            break;
        }

        // ── CREATING new rules: one per task/group × room combination ──
        // Build list of items: each is [type, id, name]
        $items = [];
        if (!empty($taskGroupIds)) {
            $ph = implode(',', array_fill(0, count($taskGroupIds), '?'));
            $ns = $db->prepare("SELECT id, name FROM task_groups WHERE id IN ($ph)");
            $ns->execute($taskGroupIds);
            foreach ($ns->fetchAll() as $row) {
                $items[] = ['type' => 'group', 'id' => (int)$row['id'], 'name' => $row['name']];
            }
        }
        if (!empty($taskIds)) {
            $ph = implode(',', array_fill(0, count($taskIds), '?'));
            $ns = $db->prepare("SELECT id, name FROM tasks WHERE id IN ($ph)");
            $ns->execute($taskIds);
            foreach ($ns->fetchAll() as $row) {
                $items[] = ['type' => 'task', 'id' => (int)$row['id'], 'name' => $row['name']];
            }
        }

        $createdIds = [];
        $db->beginTransaction();
        try {
            foreach ($items as $item) {
                foreach ($roomIds as $roomId) {
                    $stmt = $db->prepare("INSERT INTO cleaning_schedules (name, frequency, frequency_config,
                        assign_to_type, assign_to_user_id, assign_to_role, deadline_time, is_active, created_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$item['name'], $frequency, $freqJson, $assignToType, $assignToUserId, $assignToRole, $deadlineTime, $isActive, $user['id']]);
                    $newId = (int)$db->lastInsertId();
                    $createdIds[] = $newId;

                    // Link single room
                    $db->prepare("INSERT INTO cleaning_schedule_rooms (schedule_id, room_id) VALUES (?, ?)")->execute([$newId, $roomId]);

                    // Link single task group or task
                    if ($item['type'] === 'group') {
                        $db->prepare("INSERT INTO cleaning_schedule_task_groups (schedule_id, task_group_id) VALUES (?, ?)")->execute([$newId, $item['id']]);
                    } else {
                        $db->prepare("INSERT INTO cleaning_schedule_tasks (schedule_id, task_id) VALUES (?, ?)")->execute([$newId, $item['id']]);
                    }
                }
            }
            $db->commit();
            echo json_encode(['success' => true, 'created' => count($createdIds), 'ids' => $createdIds]);
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
    }

    // ═══════════════════════════════════════════════════════════
    // DELETE SCHEDULE
    // ═══════════════════════════════════════════════════════════
    case 'delete_schedule': {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['error' => 'Missing id']); break; }

        // Delete pending/future assignments tied to this schedule
        // (preserve completed or in-progress work)
        $today = date('Y-m-d');
        $db->prepare("
            DELETE jtc FROM janitor_task_checklist jtc
            JOIN janitor_task_assignments ja ON jtc.assignment_id = ja.id
            WHERE ja.schedule_id = ? AND ja.status = 'pending' AND ja.assigned_date >= ?
        ")->execute([$id, $today]);
        $db->prepare("
            DELETE FROM janitor_task_assignments
            WHERE schedule_id = ? AND status = 'pending' AND assigned_date >= ?
        ")->execute([$id, $today]);

        $db->prepare("DELETE FROM cleaning_schedules WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true]);
        break;
    }

    case 'delete_assignment': {
        if (!isAdmin()) { echo json_encode(['error' => 'Admin only']); break; }
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['error' => 'Missing id']); break; }

        // Delete any checklist items tied to this assignment
        $db->prepare("DELETE FROM janitor_task_checklist WHERE assignment_id = ?")->execute([$id]);
        // Delete the assignment itself
        $db->prepare("DELETE FROM janitor_task_assignments WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true]);
        break;
    }

    // ═══════════════════════════════════════════════════════════
    // TOGGLE SCHEDULE ACTIVE
    // ═══════════════════════════════════════════════════════════
    case 'toggle_schedule': {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['error' => 'Missing id']); break; }
        $db->prepare("UPDATE cleaning_schedules SET is_active = NOT is_active WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true]);
        break;
    }

    // ═══════════════════════════════════════════════════════════
    // GENERATE ASSIGNMENTS from schedules
    // ═══════════════════════════════════════════════════════════
    case 'generate_assignments': {
        // Get generation window from settings
        $daysAhead = 14;
        $s = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'schedule_generation_days'")->fetch();
        if ($s) $daysAhead = (int)$s['setting_value'];

        $defaultDeadline = '08:00';
        $s2 = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'default_deadline_time'")->fetch();
        if ($s2) $defaultDeadline = $s2['setting_value'];

        $today = new DateTime();
        $endDate = (clone $today)->modify("+{$daysAhead} days");

        // Get all active schedules
        $schedules = $db->query("SELECT * FROM cleaning_schedules WHERE is_active = 1")->fetchAll();

        $created = 0;
        $skipped = 0;

        foreach ($schedules as $sched) {
            $freqConfig = $sched['frequency_config'] ? json_decode($sched['frequency_config'], true) : [];

            // Get rooms for this schedule
            $rStmt = $db->prepare("SELECT room_id FROM cleaning_schedule_rooms WHERE schedule_id = ?");
            $rStmt->execute([$sched['id']]);
            $roomIds = $rStmt->fetchAll(PDO::FETCH_COLUMN);

            // Get task groups for this schedule
            $tgStmt = $db->prepare("SELECT task_group_id FROM cleaning_schedule_task_groups WHERE schedule_id = ?");
            $tgStmt->execute([$sched['id']]);
            $taskGroupIds = $tgStmt->fetchAll(PDO::FETCH_COLUMN);

            // Get individual tasks for this schedule
            $tStmt = $db->prepare("SELECT task_id FROM cleaning_schedule_tasks WHERE schedule_id = ?");
            $tStmt->execute([$sched['id']]);
            $individualTaskIds = $tStmt->fetchAll(PDO::FETCH_COLUMN);

            // Determine assigned user(s)
            $workerIds = [];
            if ($sched['assign_to_type'] === 'user' && $sched['assign_to_user_id']) {
                $workerIds = [$sched['assign_to_user_id']];
            } elseif ($sched['assign_to_type'] === 'role' && $sched['assign_to_role']) {
                $wStmt = $db->prepare("SELECT id FROM users WHERE role = ? AND is_active = 1");
                $wStmt->execute([$sched['assign_to_role']]);
                $workerIds = $wStmt->fetchAll(PDO::FETCH_COLUMN);
            }
            if (empty($workerIds)) continue;

            $deadline = $sched['deadline_time'] ?: $defaultDeadline;

            // Check each date in the window
            $current = clone $today;
            while ($current <= $endDate) {
                $dateStr = $current->format('Y-m-d');
                $dow = (int)$current->format('N'); // 1=Mon, 7=Sun

                $shouldRun = false;
                switch ($sched['frequency']) {
                    case 'daily':
                        $shouldRun = true;
                        break;
                    case 'weekdays':
                        $shouldRun = ($dow >= 1 && $dow <= 5);
                        break;
                    case 'specific_days':
                        $days = $freqConfig['days'] ?? [];
                        $shouldRun = in_array($dow, $days);
                        break;
                    case 'weekly':
                        $dayOfWeek = $freqConfig['day_of_week'] ?? 1;
                        $shouldRun = ($dow == $dayOfWeek);
                        break;
                    case 'biweekly':
                        $dayOfWeek = $freqConfig['day_of_week'] ?? 1;
                        $startDate = $sched['created_at'] ? new DateTime($sched['created_at']) : $today;
                        $weeksDiff = (int)floor($startDate->diff($current)->days / 7);
                        $shouldRun = ($dow == $dayOfWeek && $weeksDiff % 2 === 0);
                        break;
                    case 'monthly':
                        $dayOfMonth = $freqConfig['day_of_month'] ?? 1;
                        $shouldRun = ((int)$current->format('j') === $dayOfMonth);
                        break;
                    case 'yearly':
                        $month = $freqConfig['month'] ?? 1;
                        $day   = $freqConfig['day'] ?? 1;
                        $shouldRun = ((int)$current->format('n') === $month && (int)$current->format('j') === $day);
                        break;
                }

                if ($shouldRun) {
                    foreach ($roomIds as $roomId) {
                        // Generate assignments for task groups
                        foreach ($taskGroupIds as $tgId) {
                            foreach ($workerIds as $wId) {
                                $check = $db->prepare("SELECT id FROM janitor_task_assignments
                                    WHERE schedule_id = ? AND room_id = ? AND task_group_id = ? AND assigned_to = ? AND assigned_date = ?");
                                $check->execute([$sched['id'], $roomId, $tgId, $wId, $dateStr]);
                                if ($check->fetch()) {
                                    $skipped++;
                                    continue;
                                }

                                $ins = $db->prepare("INSERT INTO janitor_task_assignments
                                    (schedule_id, assigned_date, assigned_to, task_group_id, room_id, deadline, status)
                                    VALUES (?, ?, ?, ?, ?, ?, 'pending')");
                                $ins->execute([$sched['id'], $dateStr, $wId, $tgId, $roomId, $dateStr . ' ' . $deadline]);

                                $assignId = (int)$db->lastInsertId();

                                // Create checklist items from task group (recursively collecting leaf tasks)
                                $taskIdsInGroup = collectLeafTaskIds($db, $tgId);

                                $insCheck = $db->prepare("INSERT INTO janitor_task_checklist (assignment_id, task_id, completed) VALUES (?, ?, 0)");
                                foreach ($taskIdsInGroup as $tid) {
                                    $insCheck->execute([$assignId, $tid]);
                                }

                                $created++;
                            }
                        }

                        // Generate assignments for individual tasks
                        foreach ($individualTaskIds as $taskId) {
                            foreach ($workerIds as $wId) {
                                $check = $db->prepare("SELECT id FROM janitor_task_assignments
                                    WHERE schedule_id = ? AND room_id = ? AND task_id = ? AND assigned_to = ? AND assigned_date = ? AND task_group_id IS NULL");
                                $check->execute([$sched['id'], $roomId, $taskId, $wId, $dateStr]);
                                if ($check->fetch()) {
                                    $skipped++;
                                    continue;
                                }

                                $ins = $db->prepare("INSERT INTO janitor_task_assignments
                                    (schedule_id, assigned_date, assigned_to, task_id, task_group_id, room_id, deadline, status)
                                    VALUES (?, ?, ?, ?, NULL, ?, ?, 'pending')");
                                $ins->execute([$sched['id'], $dateStr, $wId, $taskId, $roomId, $dateStr . ' ' . $deadline]);

                                $assignId = (int)$db->lastInsertId();

                                // Create a single checklist item for the individual task
                                $insCheck = $db->prepare("INSERT INTO janitor_task_checklist (assignment_id, task_id, completed) VALUES (?, ?, 0)");
                                $insCheck->execute([$assignId, $taskId]);

                                $created++;
                            }
                        }
                    }
                }

                $current->modify('+1 day');
            }
        }

        echo json_encode(['success' => true, 'created' => $created, 'skipped' => $skipped, 'days_ahead' => $daysAhead]);
        break;
    }

    // ═══════════════════════════════════════════════════════════
    // GET ASSIGNMENTS FOR CALENDAR VIEW
    // ═══════════════════════════════════════════════════════════
    case 'get_calendar': {
        $roomIds = $_GET['room_ids'] ?? '';
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');

        $query = "SELECT ja.id AS assignment_id, ja.assigned_date, ja.status, ja.sort_order, ja.room_id, ja.task_group_id, ja.task_id,
                         ja.assigned_to AS worker_id,
                         COALESCE(tg.name, t.name) AS task_group_name,
                         r.name AS room_name, r.room_number,
                         u.name AS worker_name, cs.name AS schedule_name
                  FROM janitor_task_assignments ja
                  LEFT JOIN task_groups tg ON ja.task_group_id = tg.id
                  LEFT JOIN tasks t ON ja.task_id = t.id
                  JOIN rooms r ON ja.room_id = r.id
                  LEFT JOIN users u ON ja.assigned_to = u.id
                  LEFT JOIN cleaning_schedules cs ON ja.schedule_id = cs.id
                  WHERE ja.assigned_date BETWEEN ? AND ?";
        $params = [$startDate, $endDate];

        if ($roomIds) {
            $ids = array_map('intval', explode(',', $roomIds));
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $query .= " AND ja.room_id IN ($placeholders)";
            $params = array_merge($params, $ids);
        }

        $query .= " ORDER BY ja.assigned_date, ja.sort_order, tg.name";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        echo json_encode($stmt->fetchAll());
        break;
    }

    // ═══════════════════════════════════════════════════════════
    // GET LOOKUPS (task groups, users with custodial role)
    // ═══════════════════════════════════════════════════════════
    case 'get_lookups': {
        $taskGroups = $db->query("
            SELECT tg.id, tg.name, tg.estimated_minutes,
                   (SELECT COUNT(*) FROM task_group_tasks WHERE task_group_id = tg.id) AS task_count
            FROM task_groups tg ORDER BY tg.name
        ")->fetchAll();

        // Attach room_ids to each task group
        $stGroupRooms = $db->prepare("SELECT room_id FROM room_default_task_groups WHERE task_group_id = ?");
        foreach ($taskGroups as &$tg) {
            $stGroupRooms->execute([$tg['id']]);
            $tg['room_ids'] = array_map('intval', array_column($stGroupRooms->fetchAll(), 'room_id'));
        }
        unset($tg);

        $tasks = $db->query("SELECT t.id, t.name, t.reusable, tt.name AS type_name FROM tasks t JOIN task_types tt ON tt.id = t.task_type_id ORDER BY t.name")->fetchAll();

        // Attach room_ids to each task
        $stTaskRooms = $db->prepare("SELECT room_id FROM task_rooms WHERE task_id = ?");
        foreach ($tasks as &$t) {
            $stTaskRooms->execute([$t['id']]);
            $t['room_ids'] = array_map('intval', array_column($stTaskRooms->fetchAll(), 'room_id'));
        }
        unset($t);

        $workers = $db->query("SELECT id, name, role FROM users WHERE is_active = 1 ORDER BY name")->fetchAll();
        $roles = [
            ['key' => 'custodial', 'label' => 'Custodial'],
        ];
        echo json_encode(['task_groups' => $taskGroups, 'tasks' => $tasks, 'workers' => $workers, 'roles' => $roles]);
        break;
    }

    // ═══════════════════════════════════════════════════════════
    // CLEANUP orphaned assignments (schedule was deleted)
    // ═══════════════════════════════════════════════════════════
    case 'cleanup_orphans': {
        if (!isAdmin()) { echo json_encode(['error' => 'Admin only']); break; }
        $today = date('Y-m-d');

        // Delete checklist items for orphaned pending assignments
        // (schedule_id IS NULL means the schedule was deleted — ON DELETE SET NULL)
        $db->exec("
            DELETE jtc FROM janitor_task_checklist jtc
            JOIN janitor_task_assignments ja ON jtc.assignment_id = ja.id
            WHERE ja.schedule_id IS NULL
              AND ja.status IN ('pending')
              AND ja.assigned_date >= '{$today}'
        ");

        // Delete orphaned pending assignments
        $stmt = $db->prepare("
            DELETE FROM janitor_task_assignments
            WHERE schedule_id IS NULL
              AND status IN ('pending')
              AND assigned_date >= ?
        ");
        $stmt->execute([$today]);
        $deleted = $stmt->rowCount();

        echo json_encode(['success' => true, 'deleted' => $deleted]);
        break;
    }

    // ═══════════════════════════════════════════════════════════
    // BULK UPDATE — apply partial updates to multiple schedules
    // ═══════════════════════════════════════════════════════════
    case 'bulk_update': {
        $ids = array_map('intval', (array)($_POST['ids'] ?? []));
        $updates = (array)($_POST['updates'] ?? []);
        if (empty($ids)) { echo json_encode(['error' => 'No schedule IDs provided']); break; }
        if (empty($updates)) { echo json_encode(['error' => 'No fields to update']); break; }

        $validFreqs = ['daily','weekdays','specific_days','weekly','biweekly','monthly','yearly'];
        $db->beginTransaction();
        try {
            foreach ($ids as $id) {
                // Build SET clause dynamically based on which fields are present
                $setClauses = [];
                $params = [];

                if (isset($updates['frequency'])) {
                    if (!in_array($updates['frequency'], $validFreqs)) throw new Exception('Invalid frequency');
                    $setClauses[] = 'frequency = ?';
                    $params[] = $updates['frequency'];

                    $freqConfig = $updates['frequency_config'] ?? null;
                    $setClauses[] = 'frequency_config = ?';
                    $params[] = $freqConfig ? (is_string($freqConfig) ? $freqConfig : json_encode($freqConfig)) : null;
                }

                if (isset($updates['assign_to_type'])) {
                    $setClauses[] = 'assign_to_type = ?';
                    $params[] = $updates['assign_to_type'];
                    $setClauses[] = 'assign_to_user_id = ?';
                    $params[] = ($updates['assign_to_type'] === 'user' && !empty($updates['assign_to_user_id'])) ? (int)$updates['assign_to_user_id'] : null;
                    $setClauses[] = 'assign_to_role = ?';
                    $params[] = $updates['assign_to_type'] === 'role' ? ($updates['assign_to_role'] ?? 'custodial') : null;
                }

                if (array_key_exists('deadline_time', $updates)) {
                    $setClauses[] = 'deadline_time = ?';
                    $params[] = !empty($updates['deadline_time']) ? trim($updates['deadline_time']) : null;
                }

                if (array_key_exists('is_active', $updates)) {
                    $setClauses[] = 'is_active = ?';
                    $params[] = (int)$updates['is_active'];
                }

                // Update the schedule row
                if (!empty($setClauses)) {
                    $params[] = $id;
                    $sql = "UPDATE cleaning_schedules SET " . implode(', ', $setClauses) . " WHERE id = ?";
                    $db->prepare($sql)->execute($params);
                }

                // Update name based on tasks (if tasks changed)
                if (isset($updates['task_group_ids']) || isset($updates['task_ids'])) {
                    $tgIds = array_map('intval', (array)($updates['task_group_ids'] ?? []));
                    $tIds  = array_map('intval', (array)($updates['task_ids'] ?? []));

                    $db->prepare("DELETE FROM cleaning_schedule_task_groups WHERE schedule_id = ?")->execute([$id]);
                    $db->prepare("DELETE FROM cleaning_schedule_tasks WHERE schedule_id = ?")->execute([$id]);

                    $name = 'Schedule';
                    if (!empty($tgIds)) {
                        foreach ($tgIds as $tgId) {
                            $db->prepare("INSERT INTO cleaning_schedule_task_groups (schedule_id, task_group_id) VALUES (?, ?)")->execute([$id, $tgId]);
                        }
                        $n = $db->prepare("SELECT name FROM task_groups WHERE id = ?");
                        $n->execute([$tgIds[0]]);
                        $name = $n->fetchColumn() ?: 'Schedule';
                    }
                    if (!empty($tIds)) {
                        foreach ($tIds as $tId) {
                            $db->prepare("INSERT INTO cleaning_schedule_tasks (schedule_id, task_id) VALUES (?, ?)")->execute([$id, $tId]);
                        }
                        if ($name === 'Schedule') {
                            $n = $db->prepare("SELECT name FROM tasks WHERE id = ?");
                            $n->execute([$tIds[0]]);
                            $name = $n->fetchColumn() ?: 'Schedule';
                        }
                    }
                    $db->prepare("UPDATE cleaning_schedules SET name = ? WHERE id = ?")->execute([$name, $id]);
                }

                // Update rooms if provided
                if (isset($updates['room_ids'])) {
                    $roomIds = array_map('intval', (array)$updates['room_ids']);
                    $db->prepare("DELETE FROM cleaning_schedule_rooms WHERE schedule_id = ?")->execute([$id]);
                    foreach ($roomIds as $roomId) {
                        $db->prepare("INSERT INTO cleaning_schedule_rooms (schedule_id, room_id) VALUES (?, ?)")->execute([$id, $roomId]);
                    }
                }
            }

            $db->commit();
            echo json_encode(['success' => true, 'updated' => count($ids)]);
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
    }

    // ═══════════════════════════════════════════════════════════
    // BULK DELETE — delete multiple schedules at once
    // ═══════════════════════════════════════════════════════════
    case 'bulk_delete': {
        $ids = array_map('intval', (array)($_POST['ids'] ?? []));
        if (empty($ids)) { echo json_encode(['error' => 'No schedule IDs provided']); break; }

        $today = date('Y-m-d');
        $db->beginTransaction();
        try {
            $ph = implode(',', array_fill(0, count($ids), '?'));

            // Delete pending future assignments & checklists
            $db->prepare("
                DELETE jtc FROM janitor_task_checklist jtc
                JOIN janitor_task_assignments ja ON jtc.assignment_id = ja.id
                WHERE ja.schedule_id IN ($ph) AND ja.status = 'pending' AND ja.assigned_date >= ?
            ")->execute(array_merge($ids, [$today]));
            $db->prepare("
                DELETE FROM janitor_task_assignments
                WHERE schedule_id IN ($ph) AND status = 'pending' AND assigned_date >= ?
            ")->execute(array_merge($ids, [$today]));

            // Delete schedules
            $db->prepare("DELETE FROM cleaning_schedules WHERE id IN ($ph)")->execute($ids);

            $db->commit();
            echo json_encode(['success' => true, 'deleted' => count($ids)]);
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
    }

    default:
        echo json_encode(['error' => 'Unknown action']);
}
