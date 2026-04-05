<?php
/**
 * Migration: Clear ALL room links (V-Link and H-Link)
 * Restores original room names, deletes virtual rooms, removes all link records.
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if (!isAdmin()) { echo 'Admin only'; exit; }
require_once __DIR__ . '/../config/database.php';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'yes') {
    $results = [];
    $db->beginTransaction();
    try {
        // 1. Restore V-Link original room names
        $stmt = $db->query("SELECT room_id, original_name FROM room_link_members");
        $members = $stmt->fetchAll();
        $restore = $db->prepare("UPDATE rooms SET name = ? WHERE id = ?");
        foreach ($members as $m) {
            $restore->execute([$m['original_name'], $m['room_id']]);
        }
        $results[] = ['Restored ' . count($members) . ' V-Link room names', 'OK'];

        // 2. Collect all virtual room IDs (V-Link + H-Link)
        $virtualIds = [];

        // V-Link virtual rooms
        $stmt = $db->query("SELECT virtual_room_id FROM room_links WHERE virtual_room_id IS NOT NULL");
        foreach ($stmt->fetchAll() as $r) $virtualIds[] = (int)$r['virtual_room_id'];

        // H-Link virtual rooms
        $stmt = $db->query("SELECT virtual_room_id FROM h_link_combinations WHERE virtual_room_id IS NOT NULL");
        foreach ($stmt->fetchAll() as $r) $virtualIds[] = (int)$r['virtual_room_id'];

        $results[] = ['Found ' . count($virtualIds) . ' virtual rooms to delete', 'OK'];

        // 3. Clear V-Link tables
        $db->exec("DELETE FROM room_link_members");
        $db->exec("DELETE FROM room_links");
        $results[] = ['Cleared room_link_members + room_links', 'OK'];

        // 4. Clear H-Link tables
        $db->exec("DELETE FROM h_link_combination_rooms");
        $db->exec("DELETE FROM h_link_combinations");
        $db->exec("DELETE FROM h_link_group_members");
        $db->exec("DELETE FROM h_link_groups");
        $results[] = ['Cleared all H-Link tables', 'OK'];

        // 5. Delete virtual rooms (and their reservation_rooms entries)
        if ($virtualIds) {
            $ph = implode(',', $virtualIds);
            $db->exec("DELETE FROM reservation_rooms WHERE room_id IN ($ph)");
            $db->exec("DELETE FROM rooms WHERE id IN ($ph) AND is_virtual = 1");
            $results[] = ['Deleted ' . count($virtualIds) . ' virtual room records', 'OK'];
        }

        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        $results[] = ['ERROR', $e->getMessage()];
    }

    echo '<!DOCTYPE html><html><head><title>Clear Links</title>';
    echo '<script src="https://cdn.tailwindcss.com"></script></head>';
    echo '<body class="bg-gray-50 p-8 font-sans">';
    echo '<div class="max-w-2xl mx-auto bg-white rounded-2xl shadow p-6">';
    echo '<h1 class="text-xl font-bold mb-4">Results</h1>';
    echo '<table class="w-full text-sm">';
    foreach ($results as [$desc, $status]) {
        $color = str_contains($status, 'ERROR') ? 'text-red-600' : 'text-green-600';
        echo "<tr><td class='py-1'>$desc</td><td class='py-1 $color font-medium'>$status</td></tr>";
    }
    echo '</table>';
    echo '<a href="/pages/facilities.php" class="inline-block mt-4 text-blue-600 hover:underline">← Back to Facilities</a>';
    echo '</div></body></html>';
    exit;
}
?>
<!DOCTYPE html>
<html><head><title>Clear All Room Links</title>
<script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-gray-50 p-8 font-sans">
<div class="max-w-2xl mx-auto bg-white rounded-2xl shadow p-6">
    <h1 class="text-xl font-bold mb-2 text-red-600">Clear ALL Room Links</h1>
    <p class="text-gray-600 mb-4">This will:</p>
    <ul class="list-disc ml-6 text-sm text-gray-700 space-y-1 mb-4">
        <li>Restore all V-Link rooms to their original names</li>
        <li>Delete all V-Link groups and members</li>
        <li>Delete all H-Link groups, combinations, and members</li>
        <li>Delete all virtual rooms created by links</li>
    </ul>
    <form method="POST">
        <input type="hidden" name="confirm" value="yes">
        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold rounded-lg px-4 py-2 text-sm transition">
            Clear All Links
        </button>
        <a href="/pages/facilities.php" class="ml-3 text-gray-500 hover:text-gray-700 text-sm">Cancel</a>
    </form>
</div>
</body></html>
