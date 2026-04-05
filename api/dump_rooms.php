<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');
$db = getDB();
$stmt = $db->query("
    SELECT rm.id, rm.name, rm.room_number, rm.is_virtual, fl.name AS floor_name, b.name AS building_name
    FROM rooms rm
    JOIN floors fl ON fl.id = rm.floor_id
    JOIN buildings b ON b.id = fl.building_id
    WHERE rm.is_virtual = 0
    ORDER BY b.name, rm.room_number, rm.name
");
echo json_encode($stmt->fetchAll());
