<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h3>Debug Info</h3>";
echo "<p>PHP version: " . phpversion() . "</p>";

echo "<p>Testing auth.php include...</p>";
try {
    require_once __DIR__ . '/../includes/auth.php';
    echo "<p style='color:green'>auth.php loaded OK</p>";
} catch (Throwable $e) {
    echo "<p style='color:red'>auth.php ERROR: " . $e->getMessage() . " in " . $e->getFile() . " line " . $e->getLine() . "</p>";
}

echo "<p>Testing app.php include...</p>";
try {
    require_once __DIR__ . '/../config/app.php';
    echo "<p style='color:green'>app.php loaded OK. BASE_PATH = " . (defined('BASE_PATH') ? BASE_PATH : 'NOT DEFINED') . "</p>";
} catch (Throwable $e) {
    echo "<p style='color:red'>app.php ERROR: " . $e->getMessage() . "</p>";
}

echo "<p>isLoggedIn(): " . (isLoggedIn() ? 'YES' : 'NO') . "</p>";

if (isLoggedIn()) {
    $user = getCurrentUser();
    echo "<p>User: " . htmlspecialchars($user['name'] ?? 'null') . "</p>";
}

echo "<p>Testing database.php include...</p>";
try {
    require_once __DIR__ . '/../config/database.php';
    echo "<p style='color:green'>database.php loaded OK</p>";
    $db = getDB();
    echo "<p style='color:green'>DB connection OK</p>";
} catch (Throwable $e) {
    echo "<p style='color:red'>database.php ERROR: " . $e->getMessage() . "</p>";
}

echo "<p>Cookie cfm_pwa_seen: " . (isset($_COOKIE['cfm_pwa_seen']) ? $_COOKIE['cfm_pwa_seen'] : 'NOT SET') . "</p>";

echo "<h3>Now trying to include index.php code...</h3>";
echo "<p>If the next line is blank, the error happens during HTML rendering.</p>";

// Simulate what index.php does after the PHP block
if (isLoggedIn()) {
    $user = getCurrentUser();
    echo "<p style='color:green'>Would render index.php HTML for user: " . htmlspecialchars($user['name']) . "</p>";
} else {
    echo "<p>Not logged in - would redirect to login.php</p>";
}

echo "<h3>All checks passed!</h3>";
