<?php
require_once __DIR__ . "/config/database.php";
$db = getDB();
$rows = $db->query("
    SELECT r.id, r.name, r.room_number, r.abbreviation, f.name as floor_name, b.name as building_name
    FROM rooms r
    JOIN floors f ON r.floor_id = f.id
    JOIN buildings b ON f.building_id = b.id
    WHERE r.is_reservable = 1
    ORDER BY b.name, f.floor_order, r.name
")->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($rows);
