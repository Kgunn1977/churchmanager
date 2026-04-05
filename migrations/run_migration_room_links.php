<?php
$pageTitle = 'Migration: Room Links — Church Facility Manager';
require_once __DIR__ . '/../includes/nav.php';
require_once __DIR__ . '/../config/database.php';
requireLogin();
if (!isAdmin()) { echo '<p style="color:red;padding:40px;">Admin access required.</p></body></html>'; exit; }
$db = getDB();

$statements = [
    "CREATE TABLE IF NOT EXISTS room_links (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS room_link_members (
        id INT AUTO_INCREMENT PRIMARY KEY,
        link_id INT NOT NULL,
        room_id INT NOT NULL,
        FOREIGN KEY (link_id) REFERENCES room_links(id) ON DELETE CASCADE,
        FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
        UNIQUE KEY uq_link_room (link_id, room_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "ALTER TABLE reservations ADD COLUMN link_id INT DEFAULT NULL",
];

$labels = [
    "Create <code>room_links</code> table (id, name, created_at)",
    "Create <code>room_link_members</code> table (link_id, room_id)",
    "Add <code>link_id</code> column to <code>reservations</code> table",
];

// ── POST: execute migration ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'yes') {
    echo '<div style="max-width:700px;margin:40px auto;font-family:ui-sans-serif,system-ui,sans-serif;">';
    echo '<h2 style="font-size:20px;font-weight:700;margin-bottom:20px;">Migration: Room Links</h2>';
    echo '<div style="background:white;border-radius:12px;border:1px solid #e5e7eb;padding:20px;">';
    echo '<p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:#6b7280;margin:0 0 12px;">RESULTS</p>';
    foreach ($statements as $i => $sql) {
        try {
            $db->exec($sql);
            echo '<p style="color:#16a34a;margin:6px 0;">✓ ' . $labels[$i] . '</p>';
        } catch (PDOException $e) {
            echo '<p style="color:#dc2626;margin:6px 0;">✗ ' . $labels[$i] . ' — ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
    }
    echo '<p style="margin-top:16px;"><a href="' . htmlspecialchars(dirname($_SERVER['PHP_SELF']) . '/../pages/facilities.php') . '" style="color:#2563eb;">← Back to Facilities</a></p>';
    echo '</div></div>';
    echo '</body></html>';
    exit;
}

// ── GET: confirmation screen ──
?>
<div style="max-width:700px;margin:40px auto;font-family:ui-sans-serif,system-ui,sans-serif;">
    <h2 style="font-size:20px;font-weight:700;margin-bottom:6px;">Migration: Room Links</h2>
    <p style="font-size:13px;color:#6b7280;margin-bottom:24px;">
        Creates the unified room linking tables. Any rooms from any floor of any building can be linked together.
        A room can belong to multiple link groups.
    </p>

    <div style="background:white;border-radius:12px;border:1px solid #e5e7eb;padding:20px;margin-bottom:16px;">
        <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:#6b7280;margin:0 0 12px;">TABLES TO CREATE</p>
        <ul style="margin:0;padding-left:20px;font-size:13px;color:#374151;line-height:1.8;">
            <li><strong>room_links</strong> — id, name, created_at</li>
            <li><strong>room_link_members</strong> — link_id, room_id (with cascade delete)</li>
            <li><strong>reservations.link_id</strong> — optional reference to the link group used when booking</li>
        </ul>
    </div>

    <form method="POST">
        <input type="hidden" name="confirm" value="yes">
        <button type="submit" style="background:#2563eb;color:white;border:none;border-radius:8px;padding:10px 24px;font-size:14px;font-weight:700;cursor:pointer;">
            Create Tables
        </button>
    </form>
</div>
</body>
</html>
