<?php
require_once __DIR__ . '/config/database.php';
$db = getDB();
try {
    $db->exec("ALTER TABLE materials_catalog CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    echo '<p style="color:green;font-family:monospace;padding:2rem">✓ materials_catalog collation fixed. <a href="/pages/materials_catalog.php">Go to Supplies page →</a></p>';
} catch (PDOException $e) {
    echo '<p style="color:red;font-family:monospace;padding:2rem">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
}
