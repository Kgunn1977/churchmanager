<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
if (!isAdmin()) { http_response_code(403); exit('Admins only.'); }
require_once __DIR__ . '/config/database.php';
$db = getDB();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
<title>Migration — Room Links</title>
<style>
body { font-family: ui-sans-serif, system-ui, sans-serif; max-width: 680px; margin: 40px auto; padding: 0 20px; background: #f8fafc; color: #1e293b; }
h1 { font-size: 20px; font-weight: 700; margin-bottom: 6px; }
.desc { font-size: 13px; color: #64748b; margin-bottom: 24px; }
.plan { background: white; border: 1px solid #e2e8f0; border-radius: 10px; padding: 18px; margin-bottom: 20px; }
.plan h2 { font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; margin: 0 0 10px; }
.plan ul { margin: 0; padding-left: 18px; font-size: 13px; line-height: 1.9; }
.btn { display: inline-block; padding: 10px 22px; background: #2563eb; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; }
.btn:hover { background: #1d4ed8; }
.result { background: white; border: 1px solid #e2e8f0; border-radius: 10px; padding: 18px; }
.ok  { color: #16a34a; font-weight: 600; }
.err { color: #dc2626; font-weight: 600; }
</style>
</head><body>
<h1>Migration — Room Links + Recurrence Exceptions</h1>
<p class="desc">Adds room linking support and recurrence exception tracking for recurring reservations.</p>

<?php if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['confirm'] ?? '') !== 'yes'): ?>

<div class="plan">
    <h2>Changes</h2>
    <ul>
        <li>Create table <strong>room_links</strong> — one record per linked group (name, building_id)</li>
        <li>Create table <strong>room_link_members</strong> — maps rooms to link groups, stores original room name for restore on unlink</li>
        <li>Create table <strong>recurrence_exceptions</strong> — tracks deleted individual occurrences of recurring reservations</li>
    </ul>
</div>

<form method="post">
    <input type="hidden" name="confirm" value="yes">
    <button class="btn" type="submit">Run Migration</button>
</form>

<?php else:
    $statements = [
        'Create room_links' => "
            CREATE TABLE IF NOT EXISTS room_links (
                id          INT AUTO_INCREMENT PRIMARY KEY,
                name        VARCHAR(255) NOT NULL,
                building_id INT NOT NULL,
                created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_building (building_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",
        'Create room_link_members' => "
            CREATE TABLE IF NOT EXISTS room_link_members (
                link_id       INT NOT NULL,
                room_id       INT NOT NULL,
                original_name VARCHAR(255) NOT NULL DEFAULT '',
                PRIMARY KEY (link_id, room_id),
                INDEX idx_room (room_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",
        'Create recurrence_exceptions' => "
            CREATE TABLE IF NOT EXISTS recurrence_exceptions (
                id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                reservation_id  INT UNSIGNED NOT NULL,
                exception_date  DATE NOT NULL,
                created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_res_date (reservation_id, exception_date),
                KEY idx_reservation_id (reservation_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",
    ];

    echo '<div class="result"><h2 style="font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin:0 0 12px;">Results</h2>';
    $allOk = true;
    foreach ($statements as $label => $sql) {
        try {
            $db->exec($sql);
            echo "<p class='ok'>&#10003; {$label}</p>";
        } catch (PDOException $e) {
            echo "<p class='err'>&#10007; {$label}: " . htmlspecialchars($e->getMessage()) . "</p>";
            $allOk = false;
        }
    }
    if ($allOk) {
        echo '<p style="margin-top:14px;font-size:13px;">Migration complete. <a href="/pages/facilities.php" style="color:#2563eb;">Go to Facilities &rarr;</a></p>';
    }
    echo '</div>';
endif; ?>
</body></html>
