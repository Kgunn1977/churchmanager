<?php
require_once __DIR__ . '/config/database.php';

$db = getDB();

echo "====================================\n";
echo "CHURCH FACILITY MANAGER DATABASE QUERY\n";
echo "====================================\n\n";

// Query A: Get ALL rooms with their building and floor info
echo "QUERY A: Rooms with Building and Floor Info\n";
echo "-------------------------------------------\n";
$stmt = $db->prepare("
    SELECT r.id, r.name, r.abbreviation, r.room_number, f.name as floor_name, b.name as building_name
    FROM rooms r
    JOIN floors f ON r.floor_id = f.id
    JOIN buildings b ON f.building_id = b.id
    ORDER BY b.name, f.floor_order, r.name
");
$stmt->execute();
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Total rooms: " . count($rooms) . "\n";
foreach ($rooms as $room) {
    echo sprintf("  [%d] %s (%s) - Room #%s | Floor: %s | Building: %s\n",
        $room['id'], $room['name'], $room['abbreviation'], $room['room_number'],
        $room['floor_name'], $room['building_name']);
}
echo "\n";

// Query B: Get all existing cleaning schedules
echo "QUERY B: Cleaning Schedules with Rooms and Tasks\n";
echo "------------------------------------------------\n";
$stmt = $db->prepare("
    SELECT cs.id, cs.name, cs.frequency, cs.frequency_config, cs.assign_to_type, cs.assign_to_role, cs.is_active,
    GROUP_CONCAT(DISTINCT r.name ORDER BY r.name SEPARATOR ', ') as rooms,
    GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR ', ') as tasks
    FROM cleaning_schedules cs
    LEFT JOIN cleaning_schedule_rooms csr ON cs.id = csr.schedule_id
    LEFT JOIN rooms r ON csr.room_id = r.id
    LEFT JOIN cleaning_schedule_tasks cst ON cs.id = cst.schedule_id
    LEFT JOIN tasks t ON cst.task_id = t.id
    GROUP BY cs.id
    ORDER BY cs.name
");
$stmt->execute();
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Total cleaning schedules: " . count($schedules) . "\n";
foreach ($schedules as $sched) {
    echo sprintf("  [%d] %s | Frequency: %s | Active: %s\n",
        $sched['id'], $sched['name'], $sched['frequency'], $sched['is_active'] ? 'Yes' : 'No');
    echo sprintf("       Assign to: %s (%s)\n", $sched['assign_to_type'], $sched['assign_to_role'] ?: 'N/A');
    echo sprintf("       Rooms: %s\n", $sched['rooms'] ?: '(none)');
    echo sprintf("       Tasks: %s\n", $sched['tasks'] ?: '(none)');
}
echo "\n";

// Query C: Get all tasks
echo "QUERY C: All Tasks\n";
echo "------------------\n";
$stmt = $db->prepare("SELECT id, name FROM tasks ORDER BY name");
$stmt->execute();
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Total tasks: " . count($tasks) . "\n";
foreach ($tasks as $task) {
    echo sprintf("  [%d] %s\n", $task['id'], $task['name']);
}
echo "\n";

// Query D: Get all supplies
echo "QUERY D: All Supplies\n";
echo "---------------------\n";
$stmt = $db->prepare("SELECT id, name FROM supplies ORDER BY name");
$stmt->execute();
$supplies = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Total supplies: " . count($supplies) . "\n";
foreach ($supplies as $supply) {
    echo sprintf("  [%d] %s\n", $supply['id'], $supply['name']);
}
echo "\n";

// Query E: Get all tools
echo "QUERY E: All Tools\n";
echo "------------------\n";
$stmt = $db->prepare("SELECT id, name FROM tools ORDER BY name");
$stmt->execute();
$tools = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Total tools: " . count($tools) . "\n";
foreach ($tools as $tool) {
    echo sprintf("  [%d] %s\n", $tool['id'], $tool['name']);
}
echo "\n";

echo "====================================\n";
echo "QUERY COMPLETE\n";
echo "====================================\n";
?>
