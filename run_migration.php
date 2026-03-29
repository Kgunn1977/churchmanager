<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
if (!isAdmin()) { die('Admin access required.'); }

$confirmed = ($_POST['confirm'] ?? '') === 'yes';

if (!$confirmed): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Migration — Church Facility Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-8">
<div class="bg-white rounded-2xl shadow-sm p-8 w-full max-w-lg">
    <h1 class="text-xl font-bold text-gray-800 mb-2">Database Migration</h1>
    <p class="text-gray-500 text-sm mb-6">The following changes will be made to the database:</p>
    <ul class="text-sm text-gray-700 space-y-2 mb-8 pl-4">
        <li class="flex items-start gap-2">
            <span class="text-blue-500 mt-0.5">▸</span>
            Add <code class="bg-gray-100 px-1 rounded">abbreviation</code> column (VARCHAR 20) to the <code class="bg-gray-100 px-1 rounded">rooms</code> table
        </li>
    </ul>
    <div class="flex gap-3">
        <a href="/pages/facilities.php"
           class="flex-1 text-center border border-gray-300 hover:bg-gray-50 text-gray-600 font-semibold py-2.5 rounded-xl text-sm transition">
            Cancel
        </a>
        <form method="POST" class="flex-1">
            <input type="hidden" name="confirm" value="yes">
            <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-xl text-sm transition">
                Run Migration
            </button>
        </form>
    </div>
</div>
</body>
</html>
<?php exit; endif;

require_once __DIR__ . '/config/database.php';
$db = getDB();

$steps = [
    "Add abbreviation column to rooms" =>
        "ALTER TABLE rooms ADD COLUMN abbreviation VARCHAR(20) NULL AFTER name",
];

$results = [];
foreach ($steps as $label => $sql) {
    try {
        $db->exec($sql);
        $results[] = ['ok' => true, 'label' => $label];
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'Duplicate column')) {
            $results[] = ['ok' => true, 'label' => $label . ' (already exists — skipped)'];
        } else {
            $results[] = ['ok' => false, 'label' => $label, 'err' => $e->getMessage()];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Migration Result</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-8">
<div class="bg-white rounded-2xl shadow-sm p-8 w-full max-w-lg">
    <h1 class="text-xl font-bold text-gray-800 mb-6">Migration Complete</h1>
    <div class="space-y-3 mb-8">
        <?php foreach ($results as $r): ?>
        <div class="flex items-start gap-3 text-sm <?= $r['ok'] ? 'text-green-700' : 'text-red-700' ?>">
            <span class="mt-0.5 font-bold"><?= $r['ok'] ? '✓' : '✗' ?></span>
            <div>
                <p class="font-medium"><?= htmlspecialchars($r['label']) ?></p>
                <?php if (!$r['ok']): ?>
                    <p class="text-red-500 text-xs mt-0.5"><?= htmlspecialchars($r['err']) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <a href="/pages/facilities.php"
       class="block text-center bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-xl text-sm transition">
        ← Back to Facilities
    </a>
</div>
</body>
</html>
