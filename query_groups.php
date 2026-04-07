<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/config/database.php';
header('Content-Type: application/json');
$db = getDB();

// Task groups (hierarchical)
$groups = $db->query("
    SELECT tg.id, tg.name, tg.parent_id, tg.task_type_id, tg.estimated_minutes
    FROM task_groups tg
    ORDER BY tg.parent_id, tg.name
")->fetchAll();

// Task group tasks (which tasks belong to which groups)
$groupTasks = $db->query("
    SELECT tgt.task_group_id, tgt.task_id, t.name as task_name
    FROM task_group_tasks tgt
    JOIN tasks t ON tgt.task_id = t.id
    ORDER BY tgt.task_group_id, t.name
")->fetchAll();

// Room groups or any room-related grouping
$roomGroups = $db->query("
    SELECT table_name FROM information_schema.tables
    WHERE table_schema = DATABASE()
    AND table_name LIKE '%group%' OR table_name LIKE '%zone%'
    ORDER BY table_name
")->fetchAll();

// Check for room_groups table
$roomGroupCheck = $db->query("
    SELECT table_name FROM information_schema.tables
    WHERE table_schema = DATABASE()
    AND (table_name LIKE '%room_group%' OR table_name LIKE '%room_zone%' OR table_name LIKE '%area%')
")->fetchAll();

echo json_encode([
    'task_groups' => $groups,
    'task_group_tasks' => $groupTasks,
    'tables_with_group' => $roomGroups,
    'room_group_tables' => $roomGroupCheck
], JSON_PRETTY_PRINT);
