<?php
$pageTitle = 'Create Supplies & Tools Catalogs — Church Facility Manager';
require_once __DIR__ . '/../includes/nav.php';
require_once __DIR__ . '/../config/database.php';
requireLogin();
if (!isAdmin()) { echo '<p>Admin only.</p></body></html>'; exit; }
$db = getDB();

$supplies = [
    ['2-in-1 Cleaner/Disinfectant', 1],
    ['Paper Towels', 1],
    ['Trash Bags (Black)', 1],
    ['Trash Bags (Clear)', 1],
    ['Carpet Cleaning Solution', 1],
    ['Glass Cleaner', 1],
    ['Toilet Bowl Cleaner', 1],
    ['Floor Cleaner', 1],
    ['Barkeepers Friend', 1],
    ['Hand Soap', 1],
    ['Seat Covers', 1],
];

$tools = [
    ['Broom', 1],
    ['Mop & Bucket', 1],
    ['Toilet Bowl Brush', 1],
    ['Sink Brush', 1],
    ['Shower Brush', 1],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'yes') {
    echo '<div style="max-width:600px;margin:40px auto;font-family:ui-sans-serif,system-ui,sans-serif;">';
    echo '<h2 style="font-size:18px;font-weight:700;margin-bottom:16px;">Migration Results</h2>';

    // Create supplies_catalog table
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS supplies_catalog (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL UNIQUE,
            quantity INT NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        echo '<p>✅ <strong>supplies_catalog</strong> table created</p>';
    } catch (PDOException $e) {
        echo '<p>❌ supplies_catalog: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }

    // Create tools_catalog table
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS tools_catalog (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL UNIQUE,
            quantity INT NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        echo '<p>✅ <strong>tools_catalog</strong> table created</p>';
    } catch (PDOException $e) {
        echo '<p>❌ tools_catalog: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }

    // Seed supplies
    echo '<h3 style="font-size:15px;font-weight:700;margin:16px 0 8px;">Supplies (' . count($supplies) . ' items)</h3>';
    $stmt = $db->prepare("INSERT IGNORE INTO supplies_catalog (name, quantity) VALUES (?, ?)");
    foreach ($supplies as [$name, $qty]) {
        try {
            $stmt->execute([$name, $qty]);
            $icon = $stmt->rowCount() > 0 ? '✅' : '⚠️ exists';
            echo "<p style='margin:2px 0;font-size:13px;'>{$icon} {$name}</p>";
        } catch (PDOException $e) {
            echo "<p style='margin:2px 0;font-size:13px;'>❌ {$name}: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }

    // Seed tools
    echo '<h3 style="font-size:15px;font-weight:700;margin:16px 0 8px;">Tools (' . count($tools) . ' items)</h3>';
    $stmt = $db->prepare("INSERT IGNORE INTO tools_catalog (name, quantity) VALUES (?, ?)");
    foreach ($tools as [$name, $qty]) {
        try {
            $stmt->execute([$name, $qty]);
            $icon = $stmt->rowCount() > 0 ? '✅' : '⚠️ exists';
            echo "<p style='margin:2px 0;font-size:13px;'>{$icon} {$name}</p>";
        } catch (PDOException $e) {
            echo "<p style='margin:2px 0;font-size:13px;'>❌ {$name}: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }

    echo '<div style="margin-top:20px;display:flex;gap:12px;">';
    echo '<a href="/pages/supplies.php" style="color:#2563eb;font-weight:600;">→ Supplies</a>';
    echo '<a href="/pages/tools.php" style="color:#2563eb;font-weight:600;">→ Tools</a>';
    echo '</div></div>';
} else {
    echo '<div style="max-width:600px;margin:40px auto;font-family:ui-sans-serif,system-ui,sans-serif;">';
    echo '<h2 style="font-size:18px;font-weight:700;margin-bottom:8px;">Create Supplies & Tools Catalogs</h2>';
    echo '<p style="color:#6b7280;margin-bottom:16px;">This will create two new tables and seed them with data from the janitorial task schedule.</p>';

    echo '<h3 style="font-size:14px;font-weight:700;margin:12px 0 6px;">Supplies (' . count($supplies) . ' items)</h3>';
    echo '<ul style="font-size:13px;color:#374151;margin-bottom:12px;">';
    foreach ($supplies as [$name, $qty]) { echo "<li>{$name}</li>"; }
    echo '</ul>';

    echo '<h3 style="font-size:14px;font-weight:700;margin:12px 0 6px;">Tools (' . count($tools) . ' items)</h3>';
    echo '<ul style="font-size:13px;color:#374151;margin-bottom:16px;">';
    foreach ($tools as [$name, $qty]) { echo "<li>{$name}</li>"; }
    echo '</ul>';

    echo '<form method="POST"><input type="hidden" name="confirm" value="yes">';
    echo '<button type="submit" style="background:#2563eb;color:white;border:none;border-radius:8px;padding:10px 20px;font-size:14px;font-weight:700;cursor:pointer;">Run Migration</button>';
    echo '</form></div>';
}
?>
</body>
</html>
