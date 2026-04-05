<?php
/**
 * Migration: H-Link (Horizontal Link) — combinable rooms with partitions
 *
 * Creates tables for H-Link groups and combinations, plus adds
 * is_virtual / h_link_combination_id columns to rooms.
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if (!isAdmin()) { echo 'Admin only'; exit; }
require_once __DIR__ . '/../config/database.php';
$db = getDB();

$statements = [
    // 1. h_link_groups — parent group tying rooms on the same floor together
    "CREATE TABLE IF NOT EXISTS h_link_groups (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        name         VARCHAR(255) NOT NULL COMMENT 'Label for the group, e.g. Fellowship Hall',
        floor_id     INT NOT NULL COMMENT 'All member rooms must be on this floor',
        created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY (floor_id),
        FOREIGN KEY (floor_id) REFERENCES floors(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // 2. h_link_group_members — individual rooms that belong to a group
    "CREATE TABLE IF NOT EXISTS h_link_group_members (
        group_id     INT NOT NULL,
        room_id      INT NOT NULL,
        PRIMARY KEY (group_id, room_id),
        KEY (room_id),
        FOREIGN KEY (group_id) REFERENCES h_link_groups(id) ON DELETE CASCADE,
        FOREIGN KEY (room_id)  REFERENCES rooms(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // 3. h_link_combinations — admin-defined combos (e.g. "Full", "Left Side")
    "CREATE TABLE IF NOT EXISTS h_link_combinations (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        group_id     INT NOT NULL,
        name         VARCHAR(255) NOT NULL COMMENT 'Combo display name, e.g. Full Room',
        virtual_room_id INT DEFAULT NULL COMMENT 'Auto-created room representing this combo',
        created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY (group_id),
        KEY (virtual_room_id),
        FOREIGN KEY (group_id) REFERENCES h_link_groups(id) ON DELETE CASCADE,
        FOREIGN KEY (virtual_room_id) REFERENCES rooms(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // 4. h_link_combination_rooms — which member rooms make up each combination
    "CREATE TABLE IF NOT EXISTS h_link_combination_rooms (
        combination_id INT NOT NULL,
        room_id        INT NOT NULL,
        PRIMARY KEY (combination_id, room_id),
        KEY (room_id),
        FOREIGN KEY (combination_id) REFERENCES h_link_combinations(id) ON DELETE CASCADE,
        FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // 5. Add is_virtual flag + h_link_combination_id to rooms (safe ADD COLUMN)
    "ALTER TABLE rooms ADD COLUMN is_virtual TINYINT(1) NOT NULL DEFAULT 0
        COMMENT '1 = auto-created virtual room for an H-Link combination'",

    "ALTER TABLE rooms ADD COLUMN h_link_combination_id INT DEFAULT NULL
        COMMENT 'Points back to h_link_combinations.id for virtual rooms'",
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'yes') {
    $results = [];
    foreach ($statements as $sql) {
        try {
            $db->exec($sql);
            $results[] = ['sql' => substr($sql, 0, 80) . '…', 'ok' => true];
        } catch (PDOException $e) {
            $msg = $e->getMessage();
            // Treat "duplicate column" as success (already run)
            if (str_contains($msg, 'Duplicate column')) {
                $results[] = ['sql' => substr($sql, 0, 80) . '…', 'ok' => true, 'note' => 'Column already exists'];
            } else {
                $results[] = ['sql' => substr($sql, 0, 80) . '…', 'ok' => false, 'error' => $msg];
            }
        }
    }

    echo '<!DOCTYPE html><html><head><title>Migration Result</title>'
       . '<script src="https://cdn.tailwindcss.com"></script></head>'
       . '<body class="bg-gray-50 p-10 font-sans">'
       . '<div class="max-w-2xl mx-auto bg-white rounded-2xl shadow p-8">'
       . '<h1 class="text-xl font-bold mb-6">H-Link Migration Results</h1>';
    foreach ($results as $r) {
        $icon  = $r['ok'] ? '✅' : '❌';
        $extra = $r['note'] ?? $r['error'] ?? '';
        echo "<p class='mb-2 text-sm'>{$icon} <code class='text-xs bg-gray-100 px-1 rounded'>"
           . htmlspecialchars($r['sql']) . "</code>"
           . ($extra ? " <span class='text-gray-500 text-xs'>— {$extra}</span>" : '')
           . "</p>";
    }
    echo '<a href="/pages/facilities.php" class="inline-block mt-6 bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-blue-700">Go to Facilities →</a>';
    echo '</div></body></html>';
    exit;
}
?>
<!DOCTYPE html>
<html>
<head><title>Migration: H-Link</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-gray-50 p-10 font-sans">
<div class="max-w-2xl mx-auto bg-white rounded-2xl shadow p-8">
    <h1 class="text-xl font-bold mb-2">H-Link Migration</h1>
    <p class="text-sm text-gray-500 mb-6">Creates tables for horizontal room linking (combinable partitions).</p>

    <h2 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-3">What will be created / changed</h2>
    <ul class="text-sm text-gray-700 space-y-2 mb-6 list-disc list-inside">
        <li><strong>h_link_groups</strong> — parent group per floor</li>
        <li><strong>h_link_group_members</strong> — individual rooms in each group</li>
        <li><strong>h_link_combinations</strong> — admin-defined combos (e.g. "Full", "Left")</li>
        <li><strong>h_link_combination_rooms</strong> — which rooms make up each combo</li>
        <li><strong>rooms.is_virtual</strong> — flag for auto-created virtual combo rooms</li>
        <li><strong>rooms.h_link_combination_id</strong> — links virtual room back to its combo</li>
    </ul>

    <form method="POST">
        <input type="hidden" name="confirm" value="yes">
        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg text-sm font-bold hover:bg-blue-700 transition">
            Run Migration
        </button>
        <a href="/pages/facilities.php" class="ml-4 text-sm text-gray-500 hover:text-gray-700">Cancel</a>
    </form>
</div>
</body>
</html>
