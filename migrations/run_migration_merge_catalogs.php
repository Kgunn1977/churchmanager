<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../config/database.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'yes') {
    $results = [];

    $statements = [
        // Add quantity column to supplies table (task-link table)
        "ALTER TABLE supplies ADD COLUMN IF NOT EXISTS quantity INT NOT NULL DEFAULT 1",

        // Add quantity column to tools table (task-link table)
        "ALTER TABLE tools ADD COLUMN IF NOT EXISTS quantity INT NOT NULL DEFAULT 1",

        // Add quantity column to materials table (task-link table)
        "ALTER TABLE materials ADD COLUMN IF NOT EXISTS quantity INT NOT NULL DEFAULT 1",
    ];

    foreach ($statements as $sql) {
        try {
            $db->exec($sql);
            $results[] = ['sql' => substr($sql, 0, 80) . '...', 'ok' => true];
        } catch (PDOException $e) {
            $results[] = ['sql' => substr($sql, 0, 80) . '...', 'ok' => false, 'err' => $e->getMessage()];
        }
    }

    // Merge supplies_catalog data into supplies
    try {
        $rows = $db->query("SELECT name, quantity FROM supplies_catalog")->fetchAll();
        $ins = $db->prepare("INSERT IGNORE INTO supplies (name, quantity) VALUES (?, ?)");
        $upd = $db->prepare("UPDATE supplies SET quantity = ? WHERE name = ? AND quantity = 1");
        $count = 0;
        foreach ($rows as $r) {
            $ins->execute([$r['name'], $r['quantity']]);
            $upd->execute([$r['quantity'], $r['name']]);
            $count++;
        }
        $results[] = ['sql' => "Merged $count rows from supplies_catalog → supplies", 'ok' => true];
    } catch (PDOException $e) {
        $results[] = ['sql' => 'Merge supplies_catalog → supplies', 'ok' => false, 'err' => $e->getMessage()];
    }

    // Merge tools_catalog data into tools
    try {
        $rows = $db->query("SELECT name, quantity FROM tools_catalog")->fetchAll();
        $ins = $db->prepare("INSERT IGNORE INTO tools (name, quantity) VALUES (?, ?)");
        $upd = $db->prepare("UPDATE tools SET quantity = ? WHERE name = ? AND quantity = 1");
        $count = 0;
        foreach ($rows as $r) {
            $ins->execute([$r['name'], $r['quantity']]);
            $upd->execute([$r['quantity'], $r['name']]);
            $count++;
        }
        $results[] = ['sql' => "Merged $count rows from tools_catalog → tools", 'ok' => true];
    } catch (PDOException $e) {
        $results[] = ['sql' => 'Merge tools_catalog → tools', 'ok' => false, 'err' => $e->getMessage()];
    }

    // Drop the old catalog tables
    try {
        $db->exec("DROP TABLE IF EXISTS supplies_catalog");
        $results[] = ['sql' => 'Dropped supplies_catalog', 'ok' => true];
    } catch (PDOException $e) {
        $results[] = ['sql' => 'Drop supplies_catalog', 'ok' => false, 'err' => $e->getMessage()];
    }

    try {
        $db->exec("DROP TABLE IF EXISTS tools_catalog");
        $results[] = ['sql' => 'Dropped tools_catalog', 'ok' => true];
    } catch (PDOException $e) {
        $results[] = ['sql' => 'Drop tools_catalog', 'ok' => false, 'err' => $e->getMessage()];
    }

    echo '<!DOCTYPE html><html><head><title>Migration Result</title>'
       . '<script src="https://cdn.tailwindcss.com"></script></head>'
       . '<body class="bg-gray-50 flex items-center justify-center min-h-screen">'
       . '<div class="bg-white rounded-2xl shadow p-8 max-w-lg w-full">'
       . '<h1 class="text-xl font-bold mb-4">Merge Catalogs — Migration Complete</h1>';
    foreach ($results as $r) {
        $icon = $r['ok'] ? '✅' : '❌';
        echo "<p class='mb-2'>{$icon} <span class='text-sm'>" . htmlspecialchars($r['sql']) . "</span></p>";
        if (!$r['ok'] && isset($r['err'])) echo "<p class='text-red-600 text-sm ml-6'>" . htmlspecialchars($r['err']) . "</p>";
    }
    echo '<a href="' . url('/pages/supplies.php') . '" class="inline-block mt-4 bg-blue-600 text-white rounded-lg px-4 py-2 font-bold text-sm hover:bg-blue-700">Go to Supplies</a>';
    echo '</div></body></html>';
    exit;
}
?>
<!DOCTYPE html>
<html>
<head><title>Migration — Merge Catalog Tables</title>
<script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen">
<div class="bg-white rounded-2xl shadow p-8 max-w-lg w-full">
    <h1 class="text-xl font-bold mb-2">Merge Catalog Tables</h1>
    <p class="text-gray-600 text-sm mb-4">Consolidates supplies_catalog/tools_catalog into the main supplies/tools tables and adds quantity support to materials.</p>
    <ul class="text-sm text-gray-700 mb-6 list-disc pl-5 space-y-1">
        <li>Adds <strong>quantity</strong> column to <code>supplies</code>, <code>tools</code>, <code>materials</code></li>
        <li>Merges all rows from <code>supplies_catalog</code> → <code>supplies</code></li>
        <li>Merges all rows from <code>tools_catalog</code> → <code>tools</code></li>
        <li>Drops <code>supplies_catalog</code> and <code>tools_catalog</code></li>
    </ul>
    <form method="POST">
        <input type="hidden" name="confirm" value="yes">
        <button type="submit" class="bg-blue-600 text-white rounded-lg px-6 py-2 font-bold text-sm hover:bg-blue-700 transition">Run Migration</button>
    </form>
</div>
</body>
</html>
