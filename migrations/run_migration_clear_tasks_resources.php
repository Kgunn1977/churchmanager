<?php
$pageTitle = 'Clear Tasks & Resources — Migration';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if (!isAdmin()) { echo 'Admin only.'; exit; }
require_once __DIR__ . '/../config/database.php';
$db = getDB();

$tables = [
    // Scheduling / assignment tables (depend on tasks & groups)
    'janitor_task_checklist',
    'janitor_task_assignments',
    'cleaning_schedule_tasks',
    'cleaning_schedule_task_groups',
    'cleaning_schedule_rooms',
    'cleaning_schedules',

    // Task-resource junction tables
    'task_tools',
    'task_supplies',
    'task_materials',
    'task_equipment',

    // Task link tables
    'task_preferred_workers',
    'task_rooms',
    'task_group_preferred_workers',
    'room_default_task_groups',

    // Task core tables
    'task_group_tasks',
    'task_groups',
    'tasks',
    'task_types',

    // Resource catalog tables
    'room_equipment',
    'equipment_catalog',
    'supplies',
    'tools',
    'materials',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'yes') {
    $results = [];
    $db->exec('SET FOREIGN_KEY_CHECKS = 0');
    foreach ($tables as $t) {
        try {
            // Check if table exists first
            $check = $db->query("SHOW TABLES LIKE '$t'");
            if ($check->rowCount() === 0) {
                $results[] = ['table' => $t, 'status' => 'skipped', 'msg' => 'Table does not exist'];
                continue;
            }
            $count = $db->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
            $db->exec("DELETE FROM `$t`");
            // Reset auto-increment
            $db->exec("ALTER TABLE `$t` AUTO_INCREMENT = 1");
            $results[] = ['table' => $t, 'status' => 'ok', 'msg' => "Cleared $count rows"];
        } catch (Exception $e) {
            $results[] = ['table' => $t, 'status' => 'error', 'msg' => $e->getMessage()];
        }
    }
    $db->exec('SET FOREIGN_KEY_CHECKS = 1');
    ?>
    <!DOCTYPE html><html><head><title>Migration Results</title>
    <script src="https://cdn.tailwindcss.com"></script></head>
    <body class="bg-gray-50 p-8">
    <div class="max-w-2xl mx-auto bg-white rounded-2xl shadow-sm p-6 border">
        <h1 class="text-xl font-bold mb-4">Clear Tasks & Resources — Results</h1>
        <table class="w-full text-sm">
            <tr class="border-b"><th class="text-left py-2">Table</th><th class="text-left py-2">Status</th><th class="text-left py-2">Details</th></tr>
            <?php foreach ($results as $r): ?>
            <tr class="border-b">
                <td class="py-1 font-mono text-xs"><?= htmlspecialchars($r['table']) ?></td>
                <td class="py-1">
                    <?php if ($r['status'] === 'ok'): ?>
                        <span class="text-green-600 font-bold">✓</span>
                    <?php elseif ($r['status'] === 'skipped'): ?>
                        <span class="text-gray-400">—</span>
                    <?php else: ?>
                        <span class="text-red-600 font-bold">✗</span>
                    <?php endif; ?>
                </td>
                <td class="py-1 text-gray-500 text-xs"><?= htmlspecialchars($r['msg']) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <div class="mt-6">
            <a href="/pages/tasks.php" class="text-blue-600 hover:underline text-sm">→ Go to Tasks</a> &nbsp;|&nbsp;
            <a href="/pages/equipment_catalog.php" class="text-blue-600 hover:underline text-sm">→ Equipment Catalog</a> &nbsp;|&nbsp;
            <a href="/pages/catalog.php" class="text-blue-600 hover:underline text-sm">→ Supplies/Tools/Materials</a>
        </div>
    </div>
    </body></html>
    <?php
    exit;
}
?>
<!DOCTYPE html><html><head><title>Clear Tasks &amp; Resources</title>
<script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-gray-50 p-8">
<div class="max-w-2xl mx-auto bg-white rounded-2xl shadow-sm p-6 border">
    <h1 class="text-xl font-bold mb-2">Clear All Tasks &amp; Resource Data</h1>
    <p class="text-sm text-gray-500 mb-4">This will delete <strong>all rows</strong> from the following tables and reset auto-increment IDs. Table structures are preserved.</p>

    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
        <p class="text-red-700 font-bold text-sm mb-2">⚠ This action cannot be undone!</p>
        <ul class="text-xs text-red-600 space-y-0.5 font-mono">
            <?php foreach ($tables as $t): ?>
            <li>• <?= htmlspecialchars($t) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <form method="POST">
        <input type="hidden" name="confirm" value="yes">
        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold rounded-lg px-6 py-2 text-sm transition">
            Clear All Data
        </button>
        <a href="/dashboard.php" class="ml-4 text-gray-500 hover:text-gray-700 text-sm">Cancel</a>
    </form>
</div>
</body></html>
