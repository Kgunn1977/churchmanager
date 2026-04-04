<?php
$pageTitle = 'Migration: Task Completion Log — Church Facility Manager';
require_once __DIR__ . '/../includes/nav.php';
require_once __DIR__ . '/../config/database.php';
if (!isAdmin()) { header('Location: ' . url('/dashboard.php')); exit; }
$db = getDB();
$ran = false;
$results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'yes') {
    $ran = true;
    $statements = [
        // Add completed_by column to janitor_task_checklist (FK to users.id)
        "ALTER TABLE janitor_task_checklist ADD COLUMN completed_by INT NULL AFTER completed_at",
        "ALTER TABLE janitor_task_checklist ADD CONSTRAINT fk_jtc_completed_by FOREIGN KEY (completed_by) REFERENCES users(id) ON DELETE SET NULL",

        // Add index on completed_at for fast log queries
        "ALTER TABLE janitor_task_checklist ADD INDEX idx_completed_at (completed_at)",
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
    <h1 class="text-2xl font-bold text-gray-800 mb-2">Migration: Task Completion Log</h1>
    <p class="text-gray-500 text-sm mb-6">Adds tracking for who completed tasks and enables fast log queries.</p>

    <?php if (!$ran): ?>
        <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-200 mb-4">
            <h2 class="text-sm font-bold text-gray-700 mb-3">This migration will:</h2>
            <ul class="text-sm text-gray-600 space-y-1 ml-4 list-disc">
                <li>Add <code>completed_by</code> column to <code>janitor_task_checklist</code> (FK to users.id, tracks who completed the task)</li>
                <li>Add an index on <code>completed_at</code> for fast log queries</li>
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
            <a href="<?= url('/pages/task_log.php') ?>" class="bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg px-4 py-2 text-sm transition inline-block">Go to Task Log</a>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
