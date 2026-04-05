<?php
/**
 * Migration: Clear all link data (H-Link + V-Link)
 * Drops all link tables, removes virtual rooms, and cleans up the rooms table
 * in preparation for the new unified Link system.
 */
$pageTitle = 'Migration: Clear All Links — Church Facility Manager';
require_once __DIR__ . '/../includes/nav.php';
require_once __DIR__ . '/../config/database.php';
requireLogin();
if (!isAdmin()) { echo '<p style="color:red;padding:40px;">Admin access required.</p></body></html>'; exit; }
$db = getDB();

$statements = [
    // 1. Remove reservation_rooms entries that reference virtual rooms
    "DELETE rr FROM reservation_rooms rr
     JOIN rooms r ON r.id = rr.room_id
     WHERE r.is_virtual = 1"
    ,
    // 2. Drop H-Link tables (child tables first due to FK constraints)
    "DROP TABLE IF EXISTS h_link_combination_rooms",
    "DROP TABLE IF EXISTS h_link_combinations",
    "DROP TABLE IF EXISTS h_link_group_members",
    "DROP TABLE IF EXISTS h_link_groups",

    // 3. Drop V-Link tables
    "DROP TABLE IF EXISTS room_link_members",
    "DROP TABLE IF EXISTS room_links",

    // 4. Delete all virtual rooms
    "DELETE FROM rooms WHERE is_virtual = 1",

    // 5. Clean up link columns on rooms table
    "ALTER TABLE rooms DROP COLUMN IF EXISTS h_link_combination_id",
    "ALTER TABLE rooms DROP COLUMN IF EXISTS is_virtual",
];

$descriptions = [
    "Remove reservation-room links for virtual rooms",
    "Drop <code>h_link_combination_rooms</code> table",
    "Drop <code>h_link_combinations</code> table",
    "Drop <code>h_link_group_members</code> table",
    "Drop <code>h_link_groups</code> table",
    "Drop <code>room_link_members</code> table (V-Link)",
    "Drop <code>room_links</code> table (V-Link)",
    "Delete all virtual rooms from <code>rooms</code> table",
    "Drop <code>h_link_combination_id</code> column from rooms",
    "Drop <code>is_virtual</code> column from rooms",
];

$confirmed = ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'yes');
?>
<div class="max-w-2xl mx-auto px-4 py-10">
    <h1 class="text-2xl font-bold text-gray-800 mb-2">Migration: Clear All Link Data</h1>
    <p class="text-gray-500 text-sm mb-6">Removes all H-Link and V-Link tables, virtual rooms, and related columns. This prepares the database for the new unified Link system.</p>

    <?php if (!$confirmed): ?>
        <?php
        // Count what will be affected
        $virtualCount = 0;
        $hlinkGroups = 0;
        $vlinkGroups = 0;
        try { $virtualCount = (int)$db->query("SELECT COUNT(*) FROM rooms WHERE is_virtual = 1")->fetchColumn(); } catch (Exception $e) {}
        try { $hlinkGroups = (int)$db->query("SELECT COUNT(*) FROM h_link_groups")->fetchColumn(); } catch (Exception $e) {}
        try { $vlinkGroups = (int)$db->query("SELECT COUNT(*) FROM room_links")->fetchColumn(); } catch (Exception $e) {}
        ?>
        <div class="bg-white rounded-2xl shadow-sm border p-6 mb-4">
            <h2 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-3">What will be removed</h2>
            <ul class="text-sm text-gray-700 space-y-1">
                <li><strong><?= $hlinkGroups ?></strong> H-Link groups and all their combinations</li>
                <li><strong><?= $vlinkGroups ?></strong> V-Link groups</li>
                <li><strong><?= $virtualCount ?></strong> virtual rooms</li>
                <li>6 link-related tables</li>
                <li>2 columns from the rooms table (<code>is_virtual</code>, <code>h_link_combination_id</code>)</li>
            </ul>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border p-6 mb-4">
            <h2 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-3">Steps</h2>
            <ol class="text-sm text-gray-700 space-y-1 list-decimal list-inside">
                <?php foreach ($descriptions as $desc): ?>
                    <li><?= $desc ?></li>
                <?php endforeach; ?>
            </ol>
        </div>
        <form method="POST">
            <input type="hidden" name="confirm" value="yes">
            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold rounded-lg px-6 py-2 text-sm transition">
                Clear All Link Data
            </button>
        </form>
    <?php else: ?>
        <div class="bg-white rounded-2xl shadow-sm border p-6">
            <h2 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-3">Results</h2>
            <?php foreach ($statements as $i => $sql): ?>
                <?php
                    try {
                        $db->exec($sql);
                        echo '<p class="text-sm text-green-700 mb-2">&#10003; ' . $descriptions[$i] . '</p>';
                    } catch (PDOException $e) {
                        $msg = $e->getMessage();
                        if (stripos($msg, 'Unknown table') !== false || stripos($msg, 'check that column') !== false || stripos($msg, "doesn't exist") !== false) {
                            echo '<p class="text-sm text-yellow-700 mb-2">&#9888; ' . $descriptions[$i] . ' — already gone, skipped.</p>';
                        } else {
                            echo '<p class="text-sm text-red-700 mb-2">&#10007; ' . $descriptions[$i] . ' — Error: ' . htmlspecialchars($msg) . '</p>';
                        }
                    }
                ?>
            <?php endforeach; ?>
            <div class="mt-4">
                <a href="/pages/facilities.php" class="text-blue-600 hover:underline text-sm font-medium">&larr; Back to Facilities</a>
            </div>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
