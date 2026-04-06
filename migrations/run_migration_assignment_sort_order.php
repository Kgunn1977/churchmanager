<?php
$pageTitle = 'Migration: Assignment Sort Order — Church Facility Manager';
require_once __DIR__ . '/../includes/nav.php';
require_once __DIR__ . '/../config/database.php';
if (!isAdmin()) { header('Location: ' . url('/dashboard.php')); exit; }
$db = getDB();

$changes = [
    "ALTER TABLE janitor_task_assignments ADD COLUMN sort_order INT NOT NULL DEFAULT 0 AFTER status"
];
$descriptions = [
    "Add sort_order column to janitor_task_assignments for drag-to-reorder in scheduling day view"
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'yes') {
    $results = [];
    foreach ($changes as $i => $sql) {
        try {
            $db->exec($sql);
            $results[] = ['success' => true, 'desc' => $descriptions[$i]];
        } catch (Exception $e) {
            $msg = $e->getMessage();
            if (stripos($msg, 'Duplicate column') !== false) {
                $results[] = ['success' => true, 'desc' => $descriptions[$i] . ' (already exists)'];
            } else {
                $results[] = ['success' => false, 'desc' => $descriptions[$i], 'error' => $msg];
            }
        }
    }
    ?>
    <div class="max-w-2xl mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Migration Results</h1>
        <div class="space-y-3">
            <?php foreach ($results as $r): ?>
                <div class="flex items-start gap-3 p-4 rounded-lg <?= $r['success'] ? 'bg-green-50' : 'bg-red-50' ?>">
                    <span class="text-lg"><?= $r['success'] ? '✅' : '❌' ?></span>
                    <div>
                        <div class="font-semibold text-sm <?= $r['success'] ? 'text-green-800' : 'text-red-800' ?>"><?= htmlspecialchars($r['desc']) ?></div>
                        <?php if (!empty($r['error'])): ?>
                            <div class="text-xs text-red-600 mt-1"><?= htmlspecialchars($r['error']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <a href="<?= url('/pages/scheduling.php') ?>" class="inline-block mt-6 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg px-4 py-2 text-sm transition">Go to Scheduling</a>
    </div>
    </body></html>
    <?php exit;
}
?>

<div class="max-w-2xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold text-gray-800 mb-2">Migration: Assignment Sort Order</h1>
    <p class="text-gray-500 text-sm mb-6">Adds a <code class="bg-gray-100 px-1 rounded">sort_order</code> column to <code class="bg-gray-100 px-1 rounded">janitor_task_assignments</code> so tasks can be drag-reordered in the scheduling day view.</p>

    <div class="bg-white rounded-2xl shadow-sm p-6 border mb-6">
        <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Changes</h2>
        <ul class="space-y-2">
            <?php foreach ($descriptions as $d): ?>
                <li class="flex items-center gap-2 text-sm text-gray-700">
                    <svg class="w-4 h-4 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <?= htmlspecialchars($d) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <form method="POST">
        <input type="hidden" name="confirm" value="yes">
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg px-6 py-2.5 text-sm transition">Run Migration</button>
    </form>
</div>
</body>
</html>
