<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');
$db = getDB();
$stmt = $db->query("
    SELECT rm.id, rm.name, fl.name AS floor_name, b.name AS building_name
    FROM rooms rm
    JOIN floors fl ON fl.id = rm.floor_id
    JOIN buildings b ON b.id = fl.building_id
    ORDER BY b.name, fl.name, rm.name
");
echo json_encode($stmt->fetchAll());
