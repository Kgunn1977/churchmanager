<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/config/database.php';
header('Content-Type: application/json');
$db = getDB();

$rooms = $db->query("
    SELECT r.id, r.name, r.abbreviation, r.room_number, f.name as floor_name, b.name as building_name
    FROM rooms r
    JOIN floors f ON r.floor_id = f.id
    JOIN buildings b ON f.building_id = b.id
    ORDER BY b.name, f.floor_order, r.name
")->fetchAll();

$tasks = $db->query("SELECT id, name FROM tasks ORDER BY name")->fetchAll();
$supplies = $db->query("SELECT id, name FROM supplies ORDER BY name")->fetchAll();
$tools = $db->query("SELECT id, name FROM tools ORDER BY name")->fetchAll();

echo json_encode(['rooms' => $rooms, 'tasks' => $tasks, 'supplies' => $supplies, 'tools' => $tools], JSON_PRETTY_PRINT);
