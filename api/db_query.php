<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if (!isAdmin()) { echo json_encode(['error' => 'Admin only']); exit; }
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

$sql = trim($_GET['sql'] ?? '');
if (!$sql) { echo json_encode(['error' => 'No SQL provided']); exit; }

// Read-only: only allow SELECT statements
if (!preg_match('/^\s*SELECT\b/i', $sql)) {
    echo json_encode(['error' => 'Only SELECT queries allowed']);
    exit;
}

// Block dangerous keywords
if (preg_match('/\b(INSERT|UPDATE|DELETE|DROP|ALTER|CREATE|TRUNCATE|REPLACE|GRANT|REVOKE)\b/i', $sql)) {
    echo json_encode(['error' => 'Write operations not allowed']);
    exit;
}

try {
    $db = getDB();
    $stmt = $db->query($sql);
    $rows = $stmt->fetchAll();
    echo json_encode($rows);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
