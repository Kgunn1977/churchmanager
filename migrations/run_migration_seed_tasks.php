<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../config/database.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'yes') {
    $results = [];

    // Step 1: Add description column to tasks table if missing
    $stmts = [
        "ALTER TABLE tasks ADD COLUMN IF NOT EXISTS description TEXT NULL AFTER name"
    ];
    foreach ($stmts as $sql) {
        try {
            $db->exec($sql);
            $results[] = ['sql' => 'Add description column to tasks', 'ok' => true];
        } catch (PDOException $e) {
            $results[] = ['sql' => 'Add description column to tasks', 'ok' => false, 'err' => $e->getMessage()];
        }
    }

    // Step 2: Get Janitorial task_type_id
    $janId = $db->query("SELECT id FROM task_types WHERE name='Janitorial'")->fetchColumn();
    if (!$janId) {
        $results[] = ['sql' => 'Find Janitorial task type', 'ok' => false, 'err' => 'Janitorial task type not found. Run run_migration_tasks.php first.'];
        goto render;
    }
    $results[] = ['sql' => "Found Janitorial type id=$janId", 'ok' => true];

    // Step 3: Seed supplies into `supplies` table (task-link table)
    $supplyNames = [
        'All Purpose Cleaner', 'Disinfectant Spray', 'Glass Cleaner', 'Toilet Bowl Cleaner',
        'Trash Bags', 'Paper Towels', 'Toilet Paper', 'Hand Soap',
        'Floor Cleaner', 'Stainless Steel Cleaner', 'Carpet Spot Cleaner'
    ];
    $supplyIds = [];
    foreach ($supplyNames as $name) {
        try {
            $db->prepare("INSERT IGNORE INTO supplies (name) VALUES (?)")->execute([$name]);
            $id = $db->query("SELECT id FROM supplies WHERE name=" . $db->quote($name))->fetchColumn();
            $supplyIds[$name] = (int)$id;
        } catch (PDOException $e) {
            $results[] = ['sql' => "Insert supply: $name", 'ok' => false, 'err' => $e->getMessage()];
        }
    }
    $results[] = ['sql' => 'Seeded ' . count($supplyIds) . ' supplies', 'ok' => true];

    // Step 4: Seed tools into `tools` table (task-link table)
    $toolNames = ['Broom', 'Mop & Bucket', 'Vacuum', 'Squeegee', 'Scrub Brush'];
    $toolIds = [];
    foreach ($toolNames as $name) {
        try {
            $db->prepare("INSERT IGNORE INTO tools (name) VALUES (?)")->execute([$name]);
            $id = $db->query("SELECT id FROM tools WHERE name=" . $db->quote($name))->fetchColumn();
            $toolIds[$name] = (int)$id;
        } catch (PDOException $e) {
            $results[] = ['sql' => "Insert tool: $name", 'ok' => false, 'err' => $e->getMessage()];
        }
    }
    $results[] = ['sql' => 'Seeded ' . count($toolIds) . ' tools', 'ok' => true];

    // Step 5: Define all 24 tasks with their descriptions, times, supplies, and tools
    $tasks = [
        ['name'=>'Breakdown Sunday', 'desc'=>'Break down and reset rooms after Sunday services', 'min'=>20, 'supplies'=>[], 'tools'=>[]],
        ['name'=>'Clean Carpets', 'desc'=>'Deep clean carpeted areas, spot treat stains', 'min'=>30, 'supplies'=>['Carpet Spot Cleaner'], 'tools'=>['Vacuum']],
        ['name'=>'Clean Door Glass', 'desc'=>'Clean glass panels on doors', 'min'=>15, 'supplies'=>['Glass Cleaner','Paper Towels'], 'tools'=>['Squeegee']],
        ['name'=>'Clean Glass', 'desc'=>'Clean windows and glass surfaces', 'min'=>15, 'supplies'=>['Glass Cleaner','Paper Towels'], 'tools'=>['Squeegee']],
        ['name'=>'Clean Mirrors', 'desc'=>'Clean and polish mirrors', 'min'=>5, 'supplies'=>['Glass Cleaner','Paper Towels'], 'tools'=>[]],
        ['name'=>'Clean Toilet Bowls', 'desc'=>'Scrub and disinfect toilet bowls', 'min'=>5, 'supplies'=>['Toilet Bowl Cleaner','Disinfectant Spray'], 'tools'=>['Scrub Brush']],
        ['name'=>'Disinfect Changing Table', 'desc'=>'Spray and wipe down baby changing stations', 'min'=>5, 'supplies'=>['Disinfectant Spray','Paper Towels'], 'tools'=>[]],
        ['name'=>'Disinfect Water Fountain', 'desc'=>'Sanitize drinking fountain surfaces', 'min'=>5, 'supplies'=>['Disinfectant Spray','Paper Towels'], 'tools'=>[]],
        ['name'=>'Mop', 'desc'=>'Mop hard floor surfaces', 'min'=>10, 'supplies'=>['Floor Cleaner'], 'tools'=>['Mop & Bucket']],
        ['name'=>'Pickup Trash', 'desc'=>'Walk through and pick up loose trash and debris', 'min'=>30, 'supplies'=>['Trash Bags'], 'tools'=>[]],
        ['name'=>'Scrub Showers', 'desc'=>'Scrub shower walls, floors, and fixtures', 'min'=>10, 'supplies'=>['All Purpose Cleaner','Disinfectant Spray'], 'tools'=>['Scrub Brush']],
        ['name'=>'Scrub Sinks', 'desc'=>'Scrub and disinfect sinks and faucets', 'min'=>5, 'supplies'=>['All Purpose Cleaner','Disinfectant Spray'], 'tools'=>['Scrub Brush']],
        ['name'=>'Stock Supplies', 'desc'=>'Restock paper towels, toilet paper, soap dispensers', 'min'=>5, 'supplies'=>['Paper Towels','Toilet Paper','Hand Soap'], 'tools'=>[]],
        ['name'=>'Sweep', 'desc'=>'Sweep hard floor surfaces', 'min'=>5, 'supplies'=>[], 'tools'=>['Broom']],
        ['name'=>'Sweep Stairs', 'desc'=>'Sweep all stairwells', 'min'=>5, 'supplies'=>[], 'tools'=>['Broom']],
        ['name'=>'Takeout Trash', 'desc'=>'Empty all trash cans and replace liners', 'min'=>5, 'supplies'=>['Trash Bags'], 'tools'=>[]],
        ['name'=>'Vacuum', 'desc'=>'Vacuum carpeted areas', 'min'=>5, 'supplies'=>[], 'tools'=>['Vacuum']],
        ['name'=>'Vacuum Stairs', 'desc'=>'Vacuum carpeted stairwells', 'min'=>10, 'supplies'=>[], 'tools'=>['Vacuum']],
        ['name'=>'Wash Towels', 'desc'=>'Launder cleaning towels and rags', 'min'=>10, 'supplies'=>[], 'tools'=>[]],
        ['name'=>'Wipe Counters', 'desc'=>'Wipe down counter surfaces', 'min'=>5, 'supplies'=>['All Purpose Cleaner','Paper Towels'], 'tools'=>[]],
        ['name'=>'Wipedown Sink', 'desc'=>'Wipe and dry sink area and fixtures', 'min'=>5, 'supplies'=>['All Purpose Cleaner','Paper Towels'], 'tools'=>[]],
        ['name'=>'Wipedown Stair Rails', 'desc'=>'Wipe and disinfect stairwell handrails', 'min'=>5, 'supplies'=>['Disinfectant Spray','Paper Towels'], 'tools'=>[]],
        ['name'=>'Wipedown Tables', 'desc'=>'Wipe and disinfect table surfaces', 'min'=>5, 'supplies'=>['All Purpose Cleaner','Paper Towels'], 'tools'=>[]],
        ['name'=>'Wipedown Toilets', 'desc'=>'Wipe exterior of toilets and flush handles', 'min'=>5, 'supplies'=>['Disinfectant Spray','Paper Towels'], 'tools'=>[]],
    ];

    $taskCount = 0;
    $linkCount = 0;

    foreach ($tasks as $t) {
        // Insert task (skip if name already exists for this type)
        $check = $db->prepare("SELECT id FROM tasks WHERE name=? AND task_type_id=?");
        $check->execute([$t['name'], $janId]);
        $taskId = $check->fetchColumn();

        if ($taskId) {
            // Update description and time
            $db->prepare("UPDATE tasks SET description=?, estimated_minutes=? WHERE id=?")->execute([$t['desc'], $t['min'], $taskId]);
        } else {
            try {
                $db->prepare("INSERT INTO tasks (name, description, task_type_id, estimated_minutes) VALUES (?,?,?,?)")
                   ->execute([$t['name'], $t['desc'], $janId, $t['min']]);
                $taskId = (int)$db->lastInsertId();
                $taskCount++;
            } catch (PDOException $e) {
                $results[] = ['sql' => "Insert task: {$t['name']}", 'ok' => false, 'err' => $e->getMessage()];
                continue;
            }
        }

        // Link supplies
        foreach ($t['supplies'] as $sName) {
            if (isset($supplyIds[$sName])) {
                try {
                    $db->prepare("INSERT IGNORE INTO task_supplies (task_id, supply_id) VALUES (?,?)")
                       ->execute([$taskId, $supplyIds[$sName]]);
                    $linkCount++;
                } catch (PDOException $e) { /* ignore dupes */ }
            }
        }

        // Link tools
        foreach ($t['tools'] as $tName) {
            if (isset($toolIds[$tName])) {
                try {
                    $db->prepare("INSERT IGNORE INTO task_tools (task_id, tool_id) VALUES (?,?)")
                       ->execute([$taskId, $toolIds[$tName]]);
                    $linkCount++;
                } catch (PDOException $e) { /* ignore dupes */ }
            }
        }
    }

    $results[] = ['sql' => "Inserted $taskCount new tasks, $linkCount resource links", 'ok' => true];

    render:

    echo '<!DOCTYPE html><html><head><title>Migration Result</title>'
       . '<script src="https://cdn.tailwindcss.com"></script></head>'
       . '<body class="bg-gray-50 flex items-center justify-center min-h-screen">'
       . '<div class="bg-white rounded-2xl shadow p-8 max-w-lg w-full">'
       . '<h1 class="text-xl font-bold mb-4">Seed Tasks — Migration Complete</h1>';
    foreach ($results as $r) {
        $icon = $r['ok'] ? '✅' : '❌';
        echo "<p class='mb-2'>{$icon} <span class='text-sm'>" . htmlspecialchars($r['sql']) . "</span></p>";
        if (!$r['ok'] && isset($r['err'])) echo "<p class='text-red-600 text-sm ml-6'>" . htmlspecialchars($r['err']) . "</p>";
    }
    echo '<a href="/pages/tasks.php" class="inline-block mt-4 bg-blue-600 text-white rounded-lg px-4 py-2 font-bold text-sm hover:bg-blue-700">Go to Tasks</a>';
    echo '</div></body></html>';
    exit;
}
?>
<!DOCTYPE html>
<html>
<head><title>Migration — Seed Janitorial Tasks</title>
<script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen">
<div class="bg-white rounded-2xl shadow p-8 max-w-lg w-full">
    <h1 class="text-xl font-bold mb-2">Seed Janitorial Tasks</h1>
    <p class="text-gray-600 text-sm mb-4">Populates the task library with 24 janitorial tasks, 11 supplies, 5 tools, and all resource links.</p>
    <ul class="text-sm text-gray-700 mb-6 list-disc pl-5 space-y-1">
        <li>Adds <strong>description</strong> column to tasks table</li>
        <li>Seeds <strong>11 supplies</strong> into <code>supplies</code> table</li>
        <li>Seeds <strong>5 tools</strong> into <code>tools</code> table</li>
        <li>Seeds <strong>24 janitorial tasks</strong> with descriptions and default times</li>
        <li>Creates <strong>task_supplies</strong> and <strong>task_tools</strong> links</li>
        <li>Safe to run multiple times (uses INSERT IGNORE / upsert)</li>
    </ul>
    <form method="POST">
        <input type="hidden" name="confirm" value="yes">
        <button type="submit" class="bg-blue-600 text-white rounded-lg px-6 py-2 font-bold text-sm hover:bg-blue-700 transition">Run Migration</button>
    </form>
</div>
</body>
</html>
