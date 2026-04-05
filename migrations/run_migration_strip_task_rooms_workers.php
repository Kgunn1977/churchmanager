<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if (!isAdmin()) { die('Admin access required.'); }
require_once __DIR__ . '/../config/database.php';

$confirmed = ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'yes');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Strip Room & Worker Data from Tasks</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
<div class="max-w-2xl mx-auto bg-white rounded-2xl shadow-sm p-8">
    <h1 class="text-xl font-bold text-gray-800 mb-4">Strip Room & Preferred Worker Data from Tasks</h1>

<?php if (!$confirmed): ?>
    <p class="text-gray-600 mb-4">This migration will <strong>delete all rows</strong> from the following junction tables:</p>
    <ul class="list-disc ml-6 text-gray-700 mb-6 space-y-1">
        <li><code class="bg-gray-100 px-1 rounded">task_rooms</code> — task → room links</li>
        <li><code class="bg-gray-100 px-1 rounded">task_preferred_workers</code> — task → preferred worker links</li>
        <li><code class="bg-gray-100 px-1 rounded">task_group_preferred_workers</code> — task group → preferred worker links</li>
        <li><code class="bg-gray-100 px-1 rounded">room_default_task_groups</code> — room → default task group links</li>
    </ul>
    <p class="text-amber-600 font-semibold mb-6">This does NOT delete tasks, task groups, rooms, or assignments — only the preference/linking data.</p>
    <form method="POST">
        <input type="hidden" name="confirm" value="yes">
        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold rounded-lg px-6 py-2 transition">
            Confirm &amp; Run Migration
        </button>
        <a href="/pages/scheduling.php" class="ml-4 text-gray-500 hover:text-gray-700">Cancel</a>
    </form>

<?php else:
    $db = getDB();
    $tables = [
        'task_rooms'                   => 'Task → Room links',
        'task_preferred_workers'       => 'Task → Preferred Worker links',
        'task_group_preferred_workers'  => 'Task Group → Preferred Worker links',
        'room_default_task_groups'      => 'Room → Default Task Group links',
    ];
    $errors = 0;
    foreach ($tables as $table => $label) {
        try {
            $count = $db->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            $db->exec("DELETE FROM $table");
            echo "<p class='text-green-700 mb-1'>✅ <strong>$label</strong> (<code>$table</code>) — deleted $count rows</p>";
        } catch (Exception $e) {
            echo "<p class='text-red-600 mb-1'>❌ <strong>$label</strong> (<code>$table</code>) — " . htmlspecialchars($e->getMessage()) . "</p>";
            $errors++;
        }
    }
    if ($errors === 0) {
        echo "<p class='mt-6 text-green-800 font-bold'>All room and preferred worker data stripped from tasks successfully.</p>";
    } else {
        echo "<p class='mt-6 text-red-700 font-bold'>Completed with $errors error(s).</p>";
    }
    echo '<a href="/pages/scheduling.php" class="inline-block mt-4 text-blue-600 hover:text-blue-800 font-medium">← Back to Scheduling</a>';
endif; ?>
</div>
</body>
</html>
