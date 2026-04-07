<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/config/database.php';
header('Content-Type: application/json');
$db = getDB();

// Show the actual duplicate rows with their schedule_id
$stmt = $db->query("
    SELECT ja.id, ja.schedule_id, ja.task_group_id, ja.task_id, ja.room_id
    FROM janitor_task_assignments ja
    WHERE ja.assigned_to = 18 AND ja.assigned_date = '2026-04-07'
      AND ja.task_id = 6 AND ja.room_id = 180
    ORDER BY ja.schedule_id
");
$rows = $stmt->fetchAll();

// Check how many distinct schedules map to the same task+room
$stmt2 = $db->query("
    SELECT cs.id as schedule_id, cs.name, csr.room_id, cst.task_id
    FROM cleaning_schedules cs
    JOIN cleaning_schedule_rooms csr ON csr.schedule_id = cs.id
    JOIN cleaning_schedule_tasks cst ON cst.schedule_id = cs.id
    WHERE csr.room_id = 180 AND cst.task_id = 6
");
$schedules = $stmt2->fetchAll();

echo json_encode(['duplicate_rows' => $rows, 'overlapping_schedules' => $schedules], JSON_PRETTY_PRINT);
