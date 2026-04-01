<?php
$pageTitle = 'Migration: Task Preferred Workers — Church Facility Manager';
require_once __DIR__ . '/../includes/nav.php';
require_once __DIR__ . '/../config/database.php';
requireLogin();
if (!isAdmin()) { echo '<p class="text-red-600 p-8">Admin access required.</p></body></html>'; exit; }
$db = getDB();

$statements = [
    "CREATE TABLE IF NOT EXISTS task_preferred_workers (
        task_id INT NOT NULL,
        user_id INT NOT NULL,
        PRIMARY KEY (task_id, user_id),
        FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS task_group_preferred_workers (
        task_group_id INT NOT NULL,
        user_id INT NOT NULL,
        PRIMARY KEY (task_group_id, user_id),
        FOREIGN KEY (task_group_id) REFERENCES task_groups(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'yes') {
    echo '<div class="max-w-2xl mx-auto p-8">';
    echo '<h2 class="text-lg font-bold mb-4">Migration Results</h2>';
    $ok = true;
    foreach ($statements as $sql) {
        try {
            $db->exec($sql);
            preg_match('/TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?(\w+)/i', $sql, $m);
            $tbl = $m[1] ?? '?';
            echo "<p class='text-green-700 mb-2'>&#10003; <strong>{$tbl}</strong> — OK</p>";
        } catch (PDOException $e) {
            $ok = false;
            echo "<p class='text-red-600 mb-2'>&#10007; Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    if ($ok) {
        echo '<p class="mt-4"><a href="/pages/tasks.php" class="text-blue-600 hover:underline font-bold">&#8592; Back to Task Library</a></p>';
    }
    echo '</div></body></html>';
    exit;
}
?>
<div class="max-w-2xl mx-auto p-8">
    <h1 class="text-xl font-bold mb-4">Migration: Task Preferred Workers</h1>
    <p class="text-gray-600 mb-4">This migration will create the following tables:</p>
    <ul class="list-disc ml-6 mb-6 text-sm text-gray-700 space-y-1">
        <li><strong>task_preferred_workers</strong> — links tasks to preferred worker (user) assignments</li>
        <li><strong>task_group_preferred_workers</strong> — links task groups to preferred worker assignments</li>
    </ul>
    <form method="POST">
        <input type="hidden" name="confirm" value="yes">
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg px-6 py-2 text-sm transition">Run Migration</button>
    </form>
</div>
</body>
</html>
