<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if (!isAdmin()) { die('Admin only'); }
require_once __DIR__ . '/../config/database.php';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'yes') {
    $results = [];

    // Delete future pending assignments (preserve completed/in-progress)
    $stmt = $db->prepare("DELETE FROM janitor_task_assignments WHERE status = 'pending' AND assigned_date >= CURDATE()");
    $stmt->execute();
    $results[] = "Pending future assignments removed: " . $stmt->rowCount();

    // Delete schedule-task-group links
    $n = $db->exec('DELETE FROM cleaning_schedule_task_groups');
    $results[] = "Schedule-task-group links removed: $n";

    // Delete schedule-task links
    $n = $db->exec('DELETE FROM cleaning_schedule_tasks');
    $results[] = "Schedule-task links removed: $n";

    // Delete schedule-room links
    $n = $db->exec('DELETE FROM cleaning_schedule_rooms');
    $results[] = "Schedule-room links removed: $n";

    // Delete schedules
    $n = $db->exec('DELETE FROM cleaning_schedules');
    $results[] = "Schedules deleted: $n";

    echo '<!DOCTYPE html><html><head><title>Migration Complete</title><link href="https://cdn.tailwindcss.com" rel="stylesheet"></head>';
    echo '<body class="bg-gray-50 p-8"><div class="max-w-lg mx-auto bg-white rounded-2xl shadow p-6">';
    echo '<h1 class="text-lg font-bold text-green-700 mb-4">All Cleaning Schedules Cleared</h1>';
    echo '<ul class="space-y-1 text-sm text-gray-700">';
    foreach ($results as $r) echo "<li>✅ " . htmlspecialchars($r) . "</li>";
    echo '</ul>';
    echo '<a href="/pages/scheduling.php" class="inline-block mt-4 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg px-4 py-2 text-sm">Go to Scheduling</a>';
    echo '</div></body></html>';
    exit;
}

// Count
$count = $db->query('SELECT COUNT(*) FROM cleaning_schedules')->fetchColumn();
?>
<!DOCTYPE html>
<html><head><title>Clear All Schedules</title></head>
<body style="font-family:sans-serif; padding:40px; max-width:500px; margin:auto;">
<h1 style="font-size:20px; font-weight:bold;">Clear All Cleaning Schedules</h1>
<p style="margin:16px 0;">This will delete <strong><?= $count ?></strong> schedule rule(s) and all related data (task links, room links, future pending assignments).</p>
<p style="color:#991b1b;">Completed and in-progress assignments are preserved.</p>
<form method="POST">
    <input type="hidden" name="confirm" value="yes">
    <button type="submit" style="background:#dc2626; color:#fff; font-weight:bold; padding:8px 20px; border:none; border-radius:8px; cursor:pointer; font-size:14px;">Delete All Schedules</button>
</form>
</body></html>
