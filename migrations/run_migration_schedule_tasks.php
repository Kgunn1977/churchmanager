<?php
$pageTitle = 'Migration: Schedule Individual Tasks — Church Facility Manager';
require_once __DIR__ . '/../includes/nav.php';
require_once __DIR__ . '/../config/database.php';
if (!isAdmin()) { header('Location: ' . url('/dashboard.php')); exit; }
$db = getDB();
$ran = false;
$results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'yes') {
    $ran = true;
    $statements = [
        "CREATE TABLE IF NOT EXISTS cleaning_schedule_tasks (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            schedule_id INT NOT NULL,
            task_id     INT NOT NULL,
            FOREIGN KEY (schedule_id) REFERENCES cleaning_schedules(id) ON DELETE CASCADE,
            FOREIGN KEY (task_id)     REFERENCES tasks(id)              ON DELETE CASCADE,
            UNIQUE KEY uniq_sched_task (schedule_id, task_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "ALTER TABLE janitor_task_assignments ADD COLUMN task_id INT NULL AFTER task_group_id",
        "ALTER TABLE janitor_task_assignments ADD CONSTRAINT fk_jta_task FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE SET NULL",
        "ALTER TABLE janitor_task_assignments MODIFY COLUMN task_group_id INT NULL",
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
    <h1 class="text-2xl font-bold text-gray-800 mb-2">Migration: Schedule Individual Tasks</h1>
    <p class="text-gray-500 text-sm mb-6">Adds support for individual tasks (not just task groups) in cleaning schedules.</p>

    <?php if (!$ran): ?>
        <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-200 mb-4">
            <h2 class="text-sm font-bold text-gray-700 mb-3">This migration will:</h2>
            <ul class="text-sm text-gray-600 space-y-1 ml-4 list-disc">
                <li>Create <code>cleaning_schedule_tasks</code> table (schedule_id + task_id, many-to-many)</li>
                <li>Add <code>task_id</code> column to <code>janitor_task_assignments</code></li>
                <li>Make <code>task_group_id</code> nullable on <code>janitor_task_assignments</code> (individual tasks don't use groups)</li>
            </ul>
        </div>
        <form method="POST">
            <input type="hidden" name="confirm" value="yes">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg px-4 py-2 text-sm transition">
                Run Migration
            </button>
            <a href="<?= url('/dashboard.php') ?>" class="ml-3 text-gray-500 hover:text-gray-700 text-sm">Cancel</a>
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
            <a href="<?= url('/pages/scheduling.php') ?>" class="bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg px-4 py-2 text-sm transition inline-block">Go to Scheduling</a>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
