<?php
$pageTitle = 'Password Reset Support — Migration';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if (!isAdmin()) { echo 'Admin only.'; exit; }
require_once __DIR__ . '/../config/database.php';
$db = getDB();

$steps = [
    "ALTER TABLE users ADD COLUMN reset_token VARCHAR(64) NULL DEFAULT NULL AFTER password",
    "ALTER TABLE users ADD COLUMN reset_token_expires DATETIME NULL DEFAULT NULL AFTER reset_token",
    "ALTER TABLE users ADD INDEX idx_reset_token (reset_token)",
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'yes') {
    $results = [];
    foreach ($steps as $sql) {
        try {
            $db->exec($sql);
            $results[] = ['sql' => $sql, 'status' => 'ok'];
        } catch (PDOException $e) {
            // Column/index already exists is OK
            if (strpos($e->getMessage(), 'Duplicate column') !== false ||
                strpos($e->getMessage(), 'Duplicate key name') !== false) {
                $results[] = ['sql' => $sql, 'status' => 'skip', 'detail' => 'Already exists'];
            } else {
                $results[] = ['sql' => $sql, 'status' => 'error', 'detail' => $e->getMessage()];
            }
        }
    }
    ?>
    <!DOCTYPE html>
    <html><head><title>Migration Results</title><script src="https://cdn.tailwindcss.com"></script></head>
    <body class="bg-gray-50 p-8">
    <div class="max-w-2xl mx-auto bg-white rounded-2xl shadow p-6">
        <h1 class="text-xl font-bold mb-4">Password Reset Migration — Results</h1>
        <?php foreach ($results as $r): ?>
            <div class="mb-2 p-3 rounded-lg <?= $r['status'] === 'ok' ? 'bg-green-50 text-green-800' : ($r['status'] === 'skip' ? 'bg-yellow-50 text-yellow-800' : 'bg-red-50 text-red-800') ?>">
                <code class="text-xs block"><?= htmlspecialchars(substr($r['sql'], 0, 80)) ?>…</code>
                <?php if (isset($r['detail'])): ?>
                    <span class="text-xs"><?= htmlspecialchars($r['detail']) ?></span>
                <?php else: ?>
                    <span class="text-xs font-bold">✓ OK</span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <a href="/pages/settings.php" class="inline-block mt-4 bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-bold">← Settings</a>
    </div>
    </body></html>
    <?php exit;
}
?>
<!DOCTYPE html>
<html><head><title>Password Reset Migration</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-gray-50 p-8">
<div class="max-w-2xl mx-auto bg-white rounded-2xl shadow p-6">
    <h1 class="text-xl font-bold mb-4">Password Reset Support — Migration</h1>
    <p class="text-sm text-gray-600 mb-4">This will add password reset token columns to the <code>users</code> table:</p>
    <ul class="text-sm text-gray-700 list-disc ml-5 mb-4 space-y-1">
        <li><code>reset_token</code> — Secure random token for password reset links</li>
        <li><code>reset_token_expires</code> — Token expiry timestamp (1 hour)</li>
        <li>Index on <code>reset_token</code> for fast lookup</li>
    </ul>
    <form method="POST">
        <input type="hidden" name="confirm" value="yes">
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg px-6 py-2 text-sm">Run Migration</button>
    </form>
</div>
</body></html>
