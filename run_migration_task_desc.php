<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/config/database.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'yes') {
    $results = [];
    $statements = [
        "ALTER TABLE tasks ADD COLUMN IF NOT EXISTS description TEXT NULL AFTER name",
        "ALTER TABLE task_groups ADD COLUMN IF NOT EXISTS description TEXT NULL AFTER name",
    ];

    foreach ($statements as $sql) {
        try {
            $db->exec($sql);
            $results[] = ['sql' => substr($sql, 0, 80) . '...', 'ok' => true];
        } catch (PDOException $e) {
            $results[] = ['sql' => substr($sql, 0, 80) . '...', 'ok' => false, 'err' => $e->getMessage()];
        }
    }

    echo '<!DOCTYPE html><html><head><title>Migration Result</title>'
       . '<script src="https://cdn.tailwindcss.com"></script></head>'
       . '<body class="bg-gray-50 flex items-center justify-center min-h-screen">'
       . '<div class="bg-white rounded-2xl shadow p-8 max-w-lg w-full">'
       . '<h1 class="text-xl font-bold mb-4">Migration Complete</h1>';
    foreach ($results as $r) {
        $icon = $r['ok'] ? '✅' : '❌';
        echo "<p class='mb-2'>{$icon} <code class='text-sm'>" . htmlspecialchars($r['sql']) . "</code></p>";
        if (!$r['ok']) echo "<p class='text-red-600 text-sm ml-6'>" . htmlspecialchars($r['err']) . "</p>";
    }
    echo '<a href="/pages/tasks.php" class="inline-block mt-4 bg-blue-600 text-white rounded-lg px-4 py-2 font-bold text-sm hover:bg-blue-700">Go to Tasks</a>';
    echo '</div></body></html>';
    exit;
}
?>
<!DOCTYPE html>
<html>
<head><title>Migration — Task Description</title>
<script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen">
<div class="bg-white rounded-2xl shadow p-8 max-w-lg w-full">
    <h1 class="text-xl font-bold mb-2">Add Description Field</h1>
    <p class="text-gray-600 text-sm mb-4">Adds a description column to tasks and task_groups.</p>
    <form method="POST">
        <input type="hidden" name="confirm" value="yes">
        <button type="submit" class="bg-blue-600 text-white rounded-lg px-6 py-2 font-bold text-sm hover:bg-blue-700 transition">Run Migration</button>
    </form>
</div>
</body>
</html>
