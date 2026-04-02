<?php
$pageTitle = 'Migration: Task Group Hierarchy & Reusable Flag — Church Facility Manager';
require_once __DIR__ . '/../includes/nav.php';
require_once __DIR__ . '/../config/database.php';
if (!isAdmin()) { header('Location: /dashboard.php'); exit; }
$db = getDB();
$ran = false;
$results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'yes') {
    $ran = true;
    $statements = [
        // Add parent_id to task_groups for nesting (NULL = top-level)
        "ALTER TABLE task_groups ADD COLUMN parent_id INT NULL AFTER id",
        "ALTER TABLE task_groups ADD CONSTRAINT fk_tg_parent FOREIGN KEY (parent_id) REFERENCES task_groups(id) ON DELETE CASCADE",

        // Add reusable flag to tasks (default TRUE for existing tasks)
        "ALTER TABLE tasks ADD COLUMN reusable TINYINT(1) NOT NULL DEFAULT 1 AFTER estimated_minutes",

        // Add sort_order to task_groups for ordering children within a parent
        "ALTER TABLE task_groups ADD COLUMN sort_order INT NOT NULL DEFAULT 0 AFTER parent_id",
    ];
    foreach ($statements as $sql) {
        try {
            $db->exec($sql);
            $results[] = ['sql' => $sql, 'status' => 'OK'];
        } catch (Exception $e) {
            $results[] = ['sql' => $sql, 'status' => 'ERROR: ' . $e->getMessage()];
        }
    }
}
?>

<div class="max-w-3xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold text-gray-800 mb-2">Migration: Task Group Hierarchy & Reusable Flag</h1>
    <p class="text-gray-500 text-sm mb-6">Adds parent_id for nested task groups and reusable flag on tasks.</p>

    <?php if (!$ran): ?>
        <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-200 mb-4">
            <h2 class="text-sm font-bold text-gray-700 mb-3">This migration will:</h2>
            <ul class="text-sm text-gray-600 space-y-1 ml-4 list-disc">
                <li>Add <code>parent_id</code> column to <code>task_groups</code> (self-referencing FK, NULL = top-level)</li>
                <li>Add <code>sort_order</code> column to <code>task_groups</code> for ordering within a parent</li>
                <li>Add <code>reusable</code> boolean column to <code>tasks</code> (defaults to TRUE for all existing tasks)</li>
            </ul>
        </div>
        <form method="POST">
            <input type="hidden" name="confirm" value="yes">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg px-4 py-2 text-sm transition">
                Run Migration
            </button>
            <a href="/dashboard.php" class="ml-3 text-gray-500 hover:text-gray-700 text-sm">Cancel</a>
        </form>
    <?php else: ?>
        <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-200 space-y-2">
            <?php foreach ($results as $r): ?>
                <div class="flex items-start gap-2 text-sm">
                    <?php if (str_starts_with($r['status'], 'OK')): ?>
                        <span class="text-green-600 font-bold">✓</span>
                    <?php else: ?>
                        <span class="text-red-600 font-bold">✗</span>
                    <?php endif; ?>
                    <div>
                        <code class="text-xs text-gray-500 break-all"><?= htmlspecialchars(substr($r['sql'], 0, 120)) ?>…</code>
                        <div class="font-semibold <?= str_starts_with($r['status'], 'OK') ? 'text-green-600' : 'text-red-600' ?>"><?= htmlspecialchars($r['status']) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="mt-4">
            <a href="/pages/tasks.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg px-4 py-2 text-sm transition inline-block">Go to Tasks</a>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
