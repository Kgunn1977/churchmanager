<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');
$db = getDB();
$tables = ['tasks','task_types','supplies','tools','materials','task_supplies','task_tools','task_materials','task_rooms'];
$result = [];
foreach ($tables as $t) {
    try {
        $cols = $db->query("DESCRIBE `$t`")->fetchAll(PDO::FETCH_ASSOC);
        $result[$t] = $cols;
    } catch (Exception $e) {
        $result[$t] = 'ERROR: ' . $e->getMessage();
    }
}
echo json_encode($result, JSON_PRETTY_PRINT);
