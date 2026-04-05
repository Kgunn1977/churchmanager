<?php
/**
 * Migration: Add contact_name column to reservations table
 * Adds a text field for the person who requested the booking
 * (separate from the logged-in user who created the record).
 */
$pageTitle = 'Migration: Add Contact Name — Church Facility Manager';
require_once __DIR__ . '/../includes/nav.php';
require_once __DIR__ . '/../config/database.php';
requireLogin();
if (!isAdmin()) { echo '<p style="color:red;padding:40px;">Admin access required.</p></body></html>'; exit; }
$db = getDB();

$statements = [
    "ALTER TABLE reservations ADD COLUMN contact_name VARCHAR(255) DEFAULT NULL AFTER notes",
];

$descriptions = [
    "Add <code>contact_name</code> (VARCHAR 255) column to <code>reservations</code> table — stores the name/email of the person who requested the booking",
];

$confirmed = ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'yes');
?>
<div class="max-w-2xl mx-auto px-4 py-10">
    <h1 class="text-2xl font-bold text-gray-800 mb-2">Migration: Add Contact Name Field</h1>
    <p class="text-gray-500 text-sm mb-6">Adds a <code>contact_name</code> column to the reservations table for tracking who requested the booking.</p>

    <?php if (!$confirmed): ?>
        <div class="bg-white rounded-2xl shadow-sm border p-6 mb-4">
            <h2 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-3">Changes to apply</h2>
            <ul class="space-y-2">
                <?php foreach ($descriptions as $desc): ?>
                    <li class="text-sm text-gray-700"><?= $desc ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <form method="POST">
            <input type="hidden" name="confirm" value="yes">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg px-6 py-2 text-sm transition">
                Run Migration
            </button>
        </form>
    <?php else: ?>
        <div class="bg-white rounded-2xl shadow-sm border p-6">
            <h2 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-3">Results</h2>
            <?php foreach ($statements as $i => $sql): ?>
                <?php
                    try {
                        $db->exec($sql);
                        echo '<p class="text-sm text-green-700 mb-2">✅ ' . htmlspecialchars($descriptions[$i]) . '</p>';
                    } catch (PDOException $e) {
                        if (stripos($e->getMessage(), 'Duplicate column') !== false) {
                            echo '<p class="text-sm text-yellow-700 mb-2">⚠️ Column already exists — skipped.</p>';
                        } else {
                            echo '<p class="text-sm text-red-700 mb-2">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
                        }
                    }
                ?>
            <?php endforeach; ?>
            <div class="mt-4">
                <a href="/pages/reservations.php" class="text-blue-600 hover:underline text-sm font-medium">← Back to Reservations</a>
            </div>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
