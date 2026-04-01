<?php
require_once 'includes/auth.php';
requireLogin();

if (!isAdmin()) {
    die('<p style="font-family:sans-serif;padding:2rem;color:#dc2626;">Access denied — admins only.</p>');
}

require_once 'config/database.php';

$migrations = [
    'rooms.map_points column' => "ALTER TABLE rooms ADD COLUMN IF NOT EXISTS map_points JSON NULL COMMENT 'Array of [x,y] pairs in feet'",
];

$results = [];
$ran     = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'yes') {
    $ran = true;
    $db  = getDB();
    foreach ($migrations as $label => $sql) {
        try {
            $db->exec($sql);
            $results[$label] = ['ok' => true, 'msg' => 'Applied successfully (or column already exists)'];
        } catch (PDOException $e) {
            $results[$label] = ['ok' => false, 'msg' => $e->getMessage()];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Floor Plan Migration — Church Facility Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-6">

<div class="bg-white rounded-2xl shadow-md w-full max-w-lg p-8">

    <div class="flex items-center gap-3 mb-6">
        <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
            <svg class="w-5 h-5 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
            </svg>
        </div>
        <div>
            <h1 class="text-lg font-bold text-gray-800">Floor Plan Migration</h1>
            <p class="text-sm text-gray-500">Adds map_points column to rooms table</p>
        </div>
    </div>

    <?php if (!$ran): ?>

        <p class="text-sm text-gray-600 mb-4">
            This will add a <code class="font-mono bg-gray-100 px-1 rounded">map_points</code> JSON column to the
            <strong>rooms</strong> table to store floor plan polygon data. Safe to run more than once.
        </p>

        <ul class="space-y-2 mb-6">
            <?php foreach (array_keys($migrations) as $label): ?>
            <li class="flex items-center gap-2 text-sm text-gray-700 bg-gray-50 rounded-lg px-4 py-2">
                <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 10h18M3 6h18M3 14h18M3 18h18"/>
                </svg>
                <code class="font-mono font-semibold"><?= htmlspecialchars($label) ?></code>
            </li>
            <?php endforeach; ?>
        </ul>

        <form method="POST">
            <input type="hidden" name="confirm" value="yes">
            <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl transition text-sm">
                Run Migration
            </button>
        </form>

        <p class="text-center mt-4">
            <a href="/dashboard.php" class="text-sm text-gray-400 hover:text-gray-600 transition">← Back to dashboard</a>
        </p>

    <?php else: ?>

        <?php $allOk = array_reduce($results, fn($c, $r) => $c && $r['ok'], true); ?>

        <div class="mb-5 rounded-xl px-4 py-3 text-sm font-semibold
            <?= $allOk ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200' ?>">
            <?= $allOk ? '✓ Migration completed successfully.' : '✗ Migration failed.' ?>
        </div>

        <ul class="space-y-2 mb-6">
            <?php foreach ($results as $label => $r): ?>
            <li class="flex items-start gap-3 rounded-lg px-4 py-3 <?= $r['ok'] ? 'bg-green-50' : 'bg-red-50' ?>">
                <span class="mt-0.5 text-base leading-none"><?= $r['ok'] ? '✓' : '✗' ?></span>
                <div>
                    <p class="text-sm font-bold <?= $r['ok'] ? 'text-green-800' : 'text-red-800' ?>">
                        <?= htmlspecialchars($label) ?>
                    </p>
                    <p class="text-xs <?= $r['ok'] ? 'text-green-600' : 'text-red-600' ?> mt-0.5">
                        <?= htmlspecialchars($r['msg']) ?>
                    </p>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>

        <?php if ($allOk): ?>
        <a href="/pages/floor_editor.php"
           class="block w-full text-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl transition text-sm">
            Go to Floor Editor →
        </a>
        <?php else: ?>
        <form method="POST">
            <input type="hidden" name="confirm" value="yes">
            <button type="submit"
                    class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 rounded-xl transition text-sm">
                Retry Migration
            </button>
        </form>
        <?php endif; ?>

        <p class="text-center mt-4">
            <a href="/dashboard.php" class="text-sm text-gray-400 hover:text-gray-600 transition">← Back to dashboard</a>
        </p>

    <?php endif; ?>

</div>

</body>
</html>
