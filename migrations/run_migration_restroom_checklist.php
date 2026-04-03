<?php
/**
 * Migration: Import Restroom Deep Clean Checklist
 * Creates a detailed task group with sections and steps for restroom cleaning.
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if (!isAdmin()) { die('Admin access required.'); }
require_once __DIR__ . '/../config/database.php';

$db = getDB();

/* ── Confirmation screen ─────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['confirm'] ?? '') !== 'yes') {
    ?>
    <!DOCTYPE html>
    <html lang="en"><head>
        <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Import Restroom Deep Clean Checklist</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-lg p-8 max-w-lg w-full">
            <h1 class="text-xl font-bold text-gray-800 mb-4">Import Restroom Deep Clean Checklist</h1>
            <p class="text-gray-600 text-sm mb-4">This will create:</p>
            <ul class="text-sm text-gray-700 space-y-1 mb-6 list-disc pl-5">
                <li>1 parent task group: <strong>Restroom Deep Clean</strong></li>
                <li>17 section sub-groups (Before you begin, Entry & setup, etc.)</li>
                <li>~80 individual step tasks linked to their sections</li>
                <li>Task-supply and task-tool links where applicable</li>
            </ul>
            <form method="POST">
                <input type="hidden" name="confirm" value="yes">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg px-6 py-3 text-sm transition w-full">
                    Import Checklist
                </button>
            </form>
        </div>
    </body></html>
    <?php
    exit;
}

/* ── Execute import ──────────────────────────────────── */
header('Content-Type: text/html; charset=utf-8');
echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><script src="https://cdn.tailwindcss.com"></script></head>';
echo '<body class="bg-gray-100 p-8"><div class="max-w-4xl mx-auto bg-white rounded-2xl shadow p-6">';
echo '<h1 class="text-xl font-bold mb-4">Import Results</h1>';

$results = [];
function logResult($desc, $ok, $detail = '') {
    global $results;
    $results[] = ['desc' => $desc, 'ok' => $ok, 'detail' => $detail];
}

try {
    $db->beginTransaction();

    /* ── 1. Get or create "Cleaning" task type ─────────── */
    $stmt = $db->prepare("SELECT id FROM task_types WHERE name = 'Cleaning' LIMIT 1");
    $stmt->execute();
    $taskTypeId = $stmt->fetchColumn();
    if (!$taskTypeId) {
        $db->prepare("INSERT INTO task_types (name, priority_order) VALUES ('Cleaning', 1)")->execute();
        $taskTypeId = (int)$db->lastInsertId();
        logResult('Created task type "Cleaning"', true, "ID: $taskTypeId");
    } else {
        $taskTypeId = (int)$taskTypeId;
        logResult('Found existing task type "Cleaning"', true, "ID: $taskTypeId");
    }

    /* ── 2. Create parent task group ──────────────────── */
    $db->prepare("INSERT INTO task_groups (name, description, task_type_id, estimated_minutes, parent_id, sort_order)
                  VALUES (?, ?, ?, ?, NULL, 0)")
       ->execute(['Restroom Deep Clean', 'Complete restroom deep-cleaning checklist with PPE, disinfection, fixture cleaning, and inspection steps.', $taskTypeId, 90]);
    $parentGroupId = (int)$db->lastInsertId();
    logResult('Created parent task group "Restroom Deep Clean"', true, "ID: $parentGroupId");

    /* ── 3. Define sections and steps ─────────────────── */
    // Each section: [name, [steps]]
    // Steps: 'text' or ['text', 'PPE'] for tagged steps
    $sections = [
        ['Before you begin', [
            'Stock cart with all required materials',
            ['Put on disposable gloves', 'PPE'],
            ['Put on safety glasses', 'PPE'],
        ]],
        ['Entry & setup', [
            'Open door slightly and announce yourself',
            'If occupied, close door and place \'Restroom Closed\' sign; wait for person to exit',
            'If empty, prop door open with door stopper',
            'Bring cart inside if able',
            'Place \'Restroom Closed\' sign in doorway',
        ]],
        ['Initial inspection', [
            'Start at door and observe entire room',
            'Check for broken items, spills, or bio-hazards',
            'Address or report any issues before regular cleaning',
        ]],
        ['Apply disinfectant (dwell time)', [
            'Lightly spray disinfectant directly on sinks, urinals, and toilets and surrounding areas',
            'Allow disinfectant to sit for full recommended dwell time while you complete the next steps',
        ]],
        ['Stock supplies', [
            'Check and refill paper towels',
            'Check and refill soap dispensers',
            'Check and refill toilet paper',
            'Check and refill seat covers',
            'Test battery-operated dispensers; replace batteries if needed',
            'Check sanitary boxes — replace liner bags if needed; use a tool to retrieve any items, never reach inside',
            'Check and restock sanitary product dispensers (if applicable)',
        ]],
        ['Dusting (3-step)', [
            'Step 1 — Use high duster to clean all vent covers',
            'Step 2 — Get a new microfiber cloth; lightly spray with disinfectant; dust all horizontal ledges below 70 inches',
            'Step 2 cont. — Spot clean trash cans, door handles, push/kick plates, and under hand-drying stations',
            'Step 2 cont. — Clean fingerprints and smudges off walls and partitions; place cloth in laundry bag when done',
            'Step 3 — Use extension duster on baseboards throughout room (skip under urinals and toilets)',
            'Step 3 cont. — Dust exposed pipes and pipe covers under sink (if applicable)',
        ]],
        ['Sweeping', [
            'Sweep behind door first, then edges, corners, behind toilets, and under trash containers — move debris to center',
            'Pick up debris with dust pan and dispose in cart trash (never the restroom trash can)',
        ]],
        ['Apply toilet bowl cleaner (dwell time)', [
            ['Put on yellow janitor gloves over disposable gloves', 'PPE'],
            'Remove urinal screens; spray both sides with disinfectant over the urinal; place screens on a paper towel on the floor',
            'Flush urinals twice, then apply toilet bowl cleaner inside each urinal bowl',
            'Flush toilets twice, then apply toilet bowl cleaner inside each toilet bowl',
            'Allow bowl cleaner to sit for full recommended dwell time while you clean the sinks',
        ]],
        ['Sinks', [
            'Wet sponge and sink bowl with cold water',
            'Apply a small amount of Bar Keepers Friend to white scratch pad; scour inside of bowl',
            'Rinse area and sponge; return sponge to container',
            'Wipe out residue with paper towel',
            'Get a new microfiber cloth; spray with disinfectant; polish faucet handles and spout',
            'Wipe counters, back splash, and inside of sink — leave completely dry and shiny; handles uniform',
            'Place cloth in laundry bag',
        ]],
        ['Urinals (if applicable)', [
            'Scrub inside bowl with toilet brush — under rim, back and forth, down drain',
            'Flush and rinse until bowl is clean; replace screens once dry',
            'Get a new microfiber cloth; spray with disinfectant over cart trash receptacle',
            'Wipe flush handles and pipes, then outside of urinal, including back wall',
        ]],
        ['Toilets', [
            'Scrub inside bowl with toilet brush — under rim, all around bowl, down drain hole',
            'Flush to rinse until bowl is clean',
            'Using the same microfiber cloth from urinals, wipe handrails, flush handle and pipes, back wall',
            'Wipe outside of toilet',
            'Leave toilet seat in upright position for inspection',
            'Place cloth in laundry bag',
        ]],
        ['Remove janitor gloves', [
            'Spray both yellow gloves completely with disinfectant over trash receptacle; rub hands together including between fingers',
            'Remove yellow gloves (careful to keep disposable gloves on); roll cuff of one over the other and store on cart',
            ['Inspect gloves for tears or holes — replace if damaged', 'PPE'],
        ]],
        ['Mirrors', [
            'Lightly spray glass cleaner directly on the mirror',
            'Wipe around the edge of the mirror with a paper towel starting at the top and going all the way around',
            'Wipe side-to-side overlapping strokes to the bottom',
            'Check mirror from multiple angles — no spots or smudges',
        ]],
        ['Trash', [
            'Tie trash liner, lift out, place in cart receptacle',
            'Install fresh liner — snug around rim, bottom resting flat',
            'Return trash can to exact original location',
        ]],
        ['Mid-clean inspection', [
            'Walk room in a circle from entry door — check mirrors, sinks, floors, stall doors',
            'Inspect with eyes AND nose — area must smell clean as well as look clean',
        ]],
        ['Mopping', [
            'Use approved mop system with green mop head (restrooms only)',
            'Begin at entry door — mop behind door first, then edges',
            'Mop entire floor in figure-eight pattern from back of room toward entry door, walking backwards',
            'Keep mop in front at all times — do not walk on wet areas',
            'Remove mop head and place in laundry bag',
        ]],
        ['Exit & post-clean', [
            'Place caution wet floor sign in center of doorway after exiting',
            'Remove disposable gloves (turn inside out as you remove; toss in cart trash)',
            'Remove safety glasses',
            'Wash hands thoroughly with soap and warm water for 20+ seconds (not in the restroom just cleaned)',
            'Move to next task; return when floor is fully dry',
            'Remove wet floor sign and reopen restroom when floors are dry',
        ]],
    ];

    /* ── 4. Build supply/tool name maps for linking ───── */
    // Load existing supplies
    $supplyRows = $db->query("SELECT id, name FROM supplies")->fetchAll(PDO::FETCH_ASSOC);
    $supplyMap = [];
    foreach ($supplyRows as $r) {
        $supplyMap[strtolower($r['name'])] = (int)$r['id'];
    }

    // Load existing tools
    $toolRows = $db->query("SELECT id, name FROM tools")->fetchAll(PDO::FETCH_ASSOC);
    $toolMap = [];
    foreach ($toolRows as $r) {
        $toolMap[strtolower($r['name'])] = (int)$r['id'];
    }

    // Keyword-based matching: step text → supply/tool links
    // We'll do fuzzy keyword matching on step text
    $supplyKeywords = [
        'disposable gloves' => ['disposable gloves', 'nitrile gloves'],
        'disinfectant' => ['disinfectant', '2in1 cleaner', 'cleaner and disinfectant'],
        'paper towel' => ['paper towel'],
        'soap' => ['soap', 'hand soap', 'foaming soap'],
        'toilet paper' => ['toilet paper'],
        'seat cover' => ['seat cover'],
        'microfiber cloth' => ['microfiber', 'microfiber cloth'],
        'bar keepers friend' => ['bar keepers friend', 'bar keeper'],
        'toilet bowl cleaner' => ['toilet bowl cleaner', 'bowl cleaner'],
        'glass cleaner' => ['glass cleaner'],
        'trash liner' => ['trash liner', 'liner bag', 'trash bag'],
    ];
    $toolKeywords = [
        'safety glasses' => ['safety glasses'],
        'door stopper' => ['door stopper'],
        'high duster' => ['high duster'],
        'extension duster' => ['extension duster'],
        'dust pan' => ['dust pan', 'dustpan'],
        'yellow janitor gloves' => ['yellow janitor gloves', 'yellow gloves', 'janitor gloves'],
        'sponge' => ['sponge'],
        'scratch pad' => ['scratch pad', 'white scratch pad'],
        'toilet brush' => ['toilet brush'],
        'mop' => ['mop system', 'mop head', 'green mop'],
        'wet floor sign' => ['wet floor sign', 'caution wet floor'],
        'restroom closed sign' => ['restroom closed'],
        'broom' => ['sweep'],
    ];

    function findSupplyId($stepText, $supplyMap, $supplyKeywords) {
        $lower = strtolower($stepText);
        $found = [];
        foreach ($supplyKeywords as $concept => $keywords) {
            foreach ($keywords as $kw) {
                if (strpos($lower, strtolower($kw)) !== false) {
                    // Find matching supply in DB
                    foreach ($supplyMap as $name => $id) {
                        if (strpos($name, strtolower($concept)) !== false) {
                            $found[$id] = true;
                            break;
                        }
                    }
                    // Try each keyword against DB
                    foreach ($supplyMap as $name => $id) {
                        foreach ($keywords as $kw2) {
                            if (strpos($name, strtolower($kw2)) !== false) {
                                $found[$id] = true;
                            }
                        }
                    }
                    break;
                }
            }
        }
        return array_keys($found);
    }

    function findToolId($stepText, $toolMap, $toolKeywords) {
        $lower = strtolower($stepText);
        $found = [];
        foreach ($toolKeywords as $concept => $keywords) {
            foreach ($keywords as $kw) {
                if (strpos($lower, strtolower($kw)) !== false) {
                    foreach ($toolMap as $name => $id) {
                        if (strpos($name, strtolower($concept)) !== false) {
                            $found[$id] = true;
                            break;
                        }
                    }
                    foreach ($toolMap as $name => $id) {
                        foreach ($keywords as $kw2) {
                            if (strpos($name, strtolower($kw2)) !== false) {
                                $found[$id] = true;
                            }
                        }
                    }
                    break;
                }
            }
        }
        return array_keys($found);
    }

    /* ── 5. Insert sections and steps ────────────────── */
    $stmtGroup = $db->prepare("INSERT INTO task_groups (name, description, task_type_id, estimated_minutes, parent_id, sort_order)
                               VALUES (?, ?, ?, ?, ?, ?)");
    $stmtTask = $db->prepare("INSERT INTO tasks (name, description, task_type_id, estimated_minutes, reusable)
                              VALUES (?, ?, ?, 1, 1)");
    $stmtLink = $db->prepare("INSERT INTO task_group_tasks (task_group_id, task_id, sort_order) VALUES (?, ?, ?)");
    $stmtTaskSupply = $db->prepare("INSERT IGNORE INTO task_supplies (task_id, supply_id) VALUES (?, ?)");
    $stmtTaskTool = $db->prepare("INSERT IGNORE INTO task_tools (task_id, tool_id) VALUES (?, ?)");

    $sectionCount = 0;
    $stepCount = 0;
    $linkCount = 0;

    foreach ($sections as $sIdx => $section) {
        $sectionName = $section[0];
        $steps = $section[1];

        // Create section sub-group
        $stmtGroup->execute([$sectionName, null, $taskTypeId, 0, $parentGroupId, $sIdx]);
        $sectionGroupId = (int)$db->lastInsertId();
        $sectionCount++;

        foreach ($steps as $stepIdx => $step) {
            // Parse step text and optional tag
            $tag = null;
            if (is_array($step)) {
                $stepText = $step[0];
                $tag = $step[1] ?? null;
            } else {
                $stepText = $step;
            }

            // Build description with tag if present
            $desc = $tag ? "[{$tag}]" : null;

            // Insert task
            $stmtTask->execute([$stepText, $desc, $taskTypeId]);
            $taskId = (int)$db->lastInsertId();
            $stepCount++;

            // Link task to section group
            $stmtLink->execute([$sectionGroupId, $taskId, $stepIdx]);

            // Auto-link supplies
            $supplyIds = findSupplyId($stepText, $supplyMap, $supplyKeywords);
            foreach ($supplyIds as $sid) {
                $stmtTaskSupply->execute([$taskId, $sid]);
                $linkCount++;
            }

            // Auto-link tools
            $toolIds = findToolId($stepText, $toolMap, $toolKeywords);
            foreach ($toolIds as $tid) {
                $stmtTaskTool->execute([$taskId, $tid]);
                $linkCount++;
            }
        }
    }

    logResult("Created $sectionCount section sub-groups", true);
    logResult("Created $stepCount step tasks", true);
    logResult("Created $linkCount supply/tool links", true);

    $db->commit();
    logResult('Transaction committed', true);

} catch (Exception $e) {
    $db->rollBack();
    logResult('ERROR — rolled back', false, htmlspecialchars($e->getMessage()));
}

/* ── Results table ────────────────────────────────────── */
echo '<table class="w-full text-sm mt-4 border-collapse">';
echo '<tr class="bg-gray-50"><th class="text-left p-2 border">Step</th><th class="text-left p-2 border">Status</th><th class="text-left p-2 border">Detail</th></tr>';
foreach ($results as $r) {
    $color = $r['ok'] ? 'text-green-700' : 'text-red-700';
    $icon  = $r['ok'] ? '✅' : '❌';
    echo "<tr><td class='p-2 border'>" . htmlspecialchars($r['desc']) . "</td>";
    echo "<td class='p-2 border $color'>$icon</td>";
    echo "<td class='p-2 border text-gray-500'>" . htmlspecialchars($r['detail']) . "</td></tr>";
}
echo '</table>';

echo '<div class="mt-6 flex gap-3">';
echo '<a href="' . url('/pages/tasks.php') . '" class="bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg px-4 py-2 text-sm transition">View Tasks</a>';
echo '</div>';
echo '</div></body></html>';
