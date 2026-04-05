<?php
$pageTitle = 'Migration: Reservation Color — Church Facility Manager';
require_once __DIR__ . '/../includes/nav.php';
require_once __DIR__ . '/../config/database.php';
requireLogin();
if (!isAdmin()) { echo '<p>Admin only.</p></body></html>'; exit; }
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'yes') {
    $results = [];
    $stmts = [
        "ALTER TABLE reservations ADD COLUMN IF NOT EXISTS color VARCHAR(7) DEFAULT NULL AFTER link_id"
            => "Add color column to reservations table",
    ];
    foreach ($stmts as $sql => $label) {
        try {
            $db->exec($sql);
            $results[] = ['ok' => true, 'label' => $label];
        } catch (Exception $e) {
            $results[] = ['ok' => false, 'label' => $label . ' — ' . $e->getMessage()];
        }
    }
    ?>
    <div class="max-w-3xl mx-auto px-4 py-10">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Migration: Reservation Color</h1>
        <div class="bg-white rounded-xl border p-6">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Results</p>
            <?php foreach ($results as $r): ?>
            <p class="text-<?= $r['ok'] ? 'green' : 'red' ?>-700"><?= $r['ok'] ? '✓' : '✗' ?> <?= htmlspecialchars($r['label']) ?></p>
            <?php endforeach; ?>
            <a href="/pages/reservations.php" class="inline-block mt-4 text-blue-600 hover:underline text-sm">← Back to Reservations</a>
        </div>
    </div>
    <?php echo '</body></html>'; exit;
}
?>
<div class="max-w-3xl mx-auto px-4 py-10">
    <h1 class="text-2xl font-bold text-gray-900 mb-2">Migration: Reservation Color</h1>
    <p class="text-gray-600 mb-6">Adds a <code>color</code> column to the reservations table for custom timeline colors.</p>
    <div class="bg-white rounded-xl border p-6 mb-6">
        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Changes</p>
        <p class="text-sm text-gray-700"><strong>reservations.color</strong> — VARCHAR(7) DEFAULT NULL (hex color like #3b82f6)</p>
    </div>
    <form method="POST">
        <input type="hidden" name="confirm" value="yes">
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg px-6 py-3 text-sm transition">Run Migration</button>
    </form>
</div>
<?php echo '</body></html>'; ?>
