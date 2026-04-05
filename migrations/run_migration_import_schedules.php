<?php
$pageTitle = 'Import Janitorial Schedules — Church Facility Manager';
require_once __DIR__ . '/../includes/nav.php';
require_once __DIR__ . '/../config/database.php';
if (!isAdmin()) { die('Admin only'); }
$db = getDB();

// ─── Room mapping: spreadsheet (Build|Room) → DB room ID ───
// Built by matching spreadsheet names to DB room names + building
$roomMap = [
    // South Building
    'SB|100'              => 179,
    'SB|101'              => 185,
    'SB|102'              => 193,
    'SB|103'              => 194,
    'SB|201'              => 211,
    'SB|202'              => 205,
    'SB|203'              => 203,
    'SB|204'              => 202,
    'SB|205'              => 204,
    'SB|Chapel'           => 170,
    'SB|Corridor A'       => 189,
    'SB|Corridor B'       => 210,
    'SB|Fellowship'       => 177,
    'SB|Kitchen'          => 182,
    'SB|Lobby'            => 176, // "Lounge" in DB
    'SB|Men\'s East'      => 180,
    'SB|Men\' West'       => 192, // typo in spreadsheet "Men' West"
    'SB|Stage'            => 217,
    'SB|Stairs'           => 187, // "Stair 1" in DB
    'SB|Women\'s East'    => 181,
    'SB|Women\'s West'    => 191,
    // North Building
    'NB|201'              => 131,
    'NB|202'              => 132,
    'NB|203'              => 125,
    'NB|204'              => 123,
    'NB|Break Room'       => 57,
    'NB|Children\'s Lobby'=> 88,
    'NB|Coffee'           => 62,
    'NB|CommOpps'         => 58,
    'NB|Corridor A'       => 90,
    'NB|Corridor B'       => 89,
    'NB|Corridor C'       => 91,
    'NB|Corridor D'       => 215,
    'NB|Corridor E'       => 216,
    'NB|Counseling Room'  => 56,
    'NB|Elevator'         => 77,
    'NB|Family Room'      => 74,
    'NB|Fireside Cafe'    => 122,
    'NB|Fireside Room'    => 119,
    'NB|Green Room'       => 93,
    'NB|Gym'              => 65,
    'NB|Kitchen'          => 61,
    'NB|Lobby'            => 110,
    'NB|Men\'s North'     => 72,
    'NB|Men\'s South'     => 59,
    'NB|Men\'s Up'        => 128,
    'NB|Men\'s Shower 1'  => 126,
    'NB|Men\'s Shower 2'  => 127,
    'NB|Nursery'          => 67,
    'NB|Nursery Restroom' => 73,
    'NB|Office Downstairs'=> 103, // "Office Corridor Down"
    'NB|Office Upstairs'  => 118, // "Office Corridor U"
    'NB|Prayer Rooms'     => 81,  // "Prayer Room West" (closest match)
    'NB|Preschool Checkin'=> 79,
    'NB|Preschool East'   => 84,
    'NB|Preschool Main'   => 85,
    'NB|Preschool Restroom'=> 80,
    'NB|Preschool West'   => 83,
    'NB|Sanctuary'        => 66,
    'NB|Stage'            => 168,
    'NB|Women\'s Lounge Down' => 68,
    'NB|Women\'s Lounge Up'   => 136,
    'NB|Women\'s North'   => 71,
    'NB|Women\'s South'   => 60,
    'NB|Women\'s Up'      => 135,
    'NB|Women\'s Shower 1'=> 133,
    'NB|Women\'s Shower 2'=> 134,
];

// ─── Task mapping: spreadsheet task name → DB task ID ───
$taskMap = [
    'Breakdown Sunday'          => 1,
    'Clean Carpets'             => 2,
    'Disinfect Changing Table'  => 3,
    'Disinfect Water Fountain'  => 4,
    'Empty Diaper Genie'        => 5,
    'Empty Trash Bathroom'      => 6,
    'Empty Trash Large'         => 7,
    'Empty Trash Small'         => 8,
    'Mop'                       => 9,
    'Pickup Trash'              => 10,
    'Scrub Showers'             => 11,
    'Scrub Sinks'               => 12,
    'Scrub Toilets'             => 13,
    'Scrub Urinals'             => 14,
    'Spot Clean Door Glass'     => 15,
    'Spot Clean Glass'          => 16,
    'Spot Clean Mirrors'        => 17,
    'Stock Bathroom Supplies'   => 18,
    'Stock Kitchen Supplies'    => 19,
    'Straighten Chairs'         => 20,
    'Sweep'                     => 21,
    'Sweep Stairs'              => 22,
    'Vacuum High Traffic'       => 24,
    'Vacuum Light Traffic'      => 23,
    'Vacuum Rugs'               => 25,
    'Vacuum Stairs'             => 26,
    'Wash Towels'               => 27,
    'Wipe down Counters'        => 28,
    'Wipe down Sink'            => 29,
    'Wipe down Stair Rails'     => 30,
    'Wipe down Tables'          => 31,
    'Wipe down Toilets'         => 32,
    'Wipe down Urinals'         => 33,
];

// ─── Worker mapping: spreadsheet name → DB user ID ───
$workerMap = [
    'Cayla'   => null,  // Not in DB yet
    'Garrett' => null,  // Not in DB yet
    'Kevin'   => 16,
    'Ryan'    => 18,
    'Josh'    => 19,
];

// ─── Day name to ISO day number (1=Mon..7=Sun) ───
$dayNum = ['Mon' => 1, 'Tue' => 2, 'Wed' => 3, 'Thu' => 4, 'Fri' => 5];

// ─── Read the spreadsheet ───
// We'll parse the uploaded XLSX using PhpSpreadsheet or a simple approach
// Since we can't rely on PhpSpreadsheet, we'll embed the grouped data directly from our Python analysis

?>

<div class="max-w-4xl mx-auto px-4 py-6">
<h1 class="text-xl font-bold text-gray-800 mb-2">Import Janitorial Schedules from Spreadsheet</h1>
<p class="text-sm text-gray-500 mb-4">This will create cleaning schedule rules based on the Janitorial.xlsx Weekly sheet.</p>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'yes') {
    // ─── Parse the embedded CSV data ───
    $csvData = $_POST['csv_data'] ?? '';
    $lines = array_filter(explode("\n", $csvData), 'strlen');

    $created = 0;
    $skipped = 0;
    $errors = [];

    foreach ($lines as $line) {
        $parts = explode('|', $line);
        if (count($parts) < 5) { $skipped++; continue; }

        $worker    = trim($parts[0]);
        $dayPattern= trim($parts[1]); // e.g. "Mon,Wed" or "Fri"
        $task      = trim($parts[2]);
        $buildRooms= trim($parts[3]); // e.g. "SB:100,SB:101"
        $buildKey  = trim($parts[4] ?? ''); // unused but available

        // Resolve worker
        $userId = $workerMap[$worker] ?? null;

        // Resolve task
        $taskId = $taskMap[$task] ?? null;
        if (!$taskId) {
            $errors[] = "Task not found: $task";
            continue;
        }

        // Resolve days → ISO numbers
        $dayNames = array_map('trim', explode(',', $dayPattern));
        $days = [];
        foreach ($dayNames as $dn) {
            if (isset($dayNum[$dn])) $days[] = $dayNum[$dn];
        }
        if (empty($days)) {
            $errors[] = "No valid days for: $worker / $task / $dayPattern";
            continue;
        }

        // Determine frequency
        sort($days);
        if ($days == [1,2,3,4,5]) {
            $frequency = 'weekdays';
            $freqConfig = null;
        } else {
            $frequency = 'specific_days';
            $freqConfig = json_encode(['days' => $days]);
        }

        // Resolve rooms
        $roomEntries = array_map('trim', explode(',', $buildRooms));
        $roomIds = [];
        foreach ($roomEntries as $entry) {
            $p = explode(':', $entry, 2);
            if (count($p) !== 2) continue;
            $key = $p[0] . '|' . $p[1];
            if (isset($roomMap[$key])) {
                $roomIds[] = $roomMap[$key];
            } else {
                $errors[] = "Room not mapped: $key";
            }
        }
        if (empty($roomIds)) {
            $errors[] = "No rooms resolved for: $worker / $task";
            continue;
        }

        // Build a name for the schedule
        $schedName = $task . ' — ' . $dayPattern;
        if ($userId) {
            $workerName = $worker;
            $schedName .= " ($workerName)";
        }

        // Create the schedule
        $stmt = $db->prepare("
            INSERT INTO cleaning_schedules (name, frequency, frequency_config, assign_to_type, assign_to_user_id, assign_to_role, deadline_time, is_active, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NULL, 1, ?, NOW())
        ");
        $assignType = $userId ? 'user' : 'role';
        $assignRole = $userId ? null : 'custodial';
        $stmt->execute([$schedName, $frequency, $freqConfig, $assignType, $userId, $assignRole, 16]);
        $schedId = (int)$db->lastInsertId();

        // Link rooms
        $roomStmt = $db->prepare("INSERT INTO cleaning_schedule_rooms (schedule_id, room_id) VALUES (?, ?)");
        foreach ($roomIds as $rid) {
            $roomStmt->execute([$schedId, $rid]);
        }

        // Link task
        $taskStmt = $db->prepare("INSERT INTO cleaning_schedule_tasks (schedule_id, task_id) VALUES (?, ?)");
        $taskStmt->execute([$schedId, $taskId]);

        $created++;
    }

    echo '<div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-4">';
    echo "<p class='text-green-800 font-bold'>Created $created schedule rules.</p>";
    if ($skipped) echo "<p class='text-yellow-700 text-sm'>Skipped $skipped rows.</p>";
    echo '</div>';

    if (!empty($errors)) {
        echo '<div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 mb-4">';
        echo '<p class="text-yellow-800 font-bold mb-2">Warnings (' . count($errors) . '):</p>';
        echo '<ul class="text-sm text-yellow-700 space-y-1 max-h-48 overflow-y-auto">';
        foreach ($errors as $e) echo '<li>' . htmlspecialchars($e) . '</li>';
        echo '</ul></div>';
    }

    echo '<a href="/pages/scheduling.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg px-4 py-2 text-sm inline-block">Go to Scheduling</a>';
    echo '</div></body></html>';
    exit;
}

// ─── Preview mode: show what will be imported ───
// Build grouped data from spreadsheet via embedded Python-generated CSV
// Format per line: Worker|DayPattern|Task|Build:Room,Build:Room,...|count

// We'll generate this data inline using PHP reading of a pre-processed CSV
// For now, embed the data directly (generated from Python analysis)
?>

<p class="text-sm text-gray-600 mb-4">Paste the grouped schedule data below and click Import. The data should be generated by the Python pre-processor.</p>

<form method="POST">
    <input type="hidden" name="confirm" value="yes">
    <textarea id="csv_data" name="csv_data" class="w-full h-64 border border-gray-300 rounded-lg p-3 text-xs font-mono mb-4" placeholder="Loading..."></textarea>
    <div class="flex gap-2">
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg px-4 py-2 text-sm transition">Import Schedules</button>
        <a href="/pages/scheduling.php" class="border border-gray-300 hover:bg-gray-50 text-gray-600 rounded-lg px-4 py-2 text-sm transition inline-block">Cancel</a>
    </div>
</form>

<script>
// Generate the grouped CSV data from the spreadsheet via fetch
// We'll call a helper endpoint that does the Python-equivalent grouping
// For simplicity, we pre-load the data here

const csvData = <?php
// Read the XLSX using PhpSpreadsheet if available, otherwise use a simpler approach
// Check if we can use a PHP XLSX reader
$xlsxPath = __DIR__ . '/../uploads/Janitorial.xlsx';
if (!file_exists($xlsxPath)) {
    // Try the uploads mount path
    $xlsxPath = '/sessions/determined-admiring-albattani/mnt/uploads/Janitorial.xlsx';
}

// We'll parse using a simple XLSX reader approach with ZipArchive
function parseXlsx($path) {
    $rows = [];
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) return $rows;

    // Read shared strings
    $strings = [];
    $ssXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($ssXml) {
        $ss = new SimpleXMLElement($ssXml);
        foreach ($ss->si as $si) {
            $strings[] = (string)$si->t ?: (string)$si;
        }
    }

    // Read first sheet
    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    if (!$sheetXml) { $zip->close(); return $rows; }

    $sheet = new SimpleXMLElement($sheetXml);
    foreach ($sheet->sheetData->row as $row) {
        $rowData = [];
        foreach ($row->c as $cell) {
            $val = '';
            $type = (string)$cell['t'];
            if ($type === 's') {
                $idx = (int)$cell->v;
                $val = $strings[$idx] ?? '';
            } elseif ($type === 'inlineStr') {
                $val = (string)$cell->is->t;
            } else {
                $val = (string)$cell->v;
            }

            // Get column letter from ref
            $ref = (string)$cell['r'];
            preg_match('/([A-Z]+)/', $ref, $m);
            $col = $m[1] ?? 'A';
            $colIdx = 0;
            for ($i = 0; $i < strlen($col); $i++) {
                $colIdx = $colIdx * 26 + (ord($col[$i]) - ord('A') + 1);
            }
            $colIdx--; // 0-based

            while (count($rowData) <= $colIdx) $rowData[] = '';
            $rowData[$colIdx] = $val;
        }
        $rows[] = $rowData;
    }
    $zip->close();
    return $rows;
}

$xlsxRows = parseXlsx($xlsxPath);
// Skip header row
$header = array_shift($xlsxRows);
// Columns: 0=Build, 1=Room, 2=Task, 3=Days, 4=Worker, 5=Instructions, 6=Equipment, 7=Mon, 8=Tue, 9=Wed, 10=Thu, 11=Fri

// Group by (worker, day_pattern, task)
$groups = [];
foreach ($xlsxRows as $row) {
    $build  = trim($row[0] ?? '');
    $room   = trim($row[1] ?? '');
    $task   = trim($row[2] ?? '');
    $worker = trim($row[4] ?? '');

    if (!$build || !$room || !$task || !$worker) continue;

    $days = [];
    if (!empty(trim($row[7] ?? ''))) $days[] = 'Mon';
    if (!empty(trim($row[8] ?? ''))) $days[] = 'Tue';
    if (!empty(trim($row[9] ?? ''))) $days[] = 'Wed';
    if (!empty(trim($row[10] ?? ''))) $days[] = 'Thu';
    if (!empty(trim($row[11] ?? ''))) $days[] = 'Fri';

    if (empty($days)) continue;

    $dayPattern = implode(',', $days);
    $key = "$worker|$dayPattern|$task";

    if (!isset($groups[$key])) {
        $groups[$key] = ['worker' => $worker, 'days' => $dayPattern, 'task' => $task, 'rooms' => []];
    }
    $roomKey = "$build:$room";
    if (!in_array($roomKey, $groups[$key]['rooms'])) {
        $groups[$key]['rooms'][] = $roomKey;
    }
}

// Build CSV lines
$csvLines = [];
foreach ($groups as $g) {
    $roomStr = implode(',', $g['rooms']);
    $csvLines[] = $g['worker'] . '|' . $g['days'] . '|' . $g['task'] . '|' . $roomStr . '|' . count($g['rooms']);
}

echo json_encode(implode("\n", $csvLines));
?>;

document.getElementById('csv_data').value = csvData;
</script>

</div>
</body>
</html>
