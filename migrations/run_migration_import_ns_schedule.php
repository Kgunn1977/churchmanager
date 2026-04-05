<?php
/**
 * Migration: Import NS Schedule data into reservations
 * Reads import_data.json, maps room names to IDs, creates organizations as needed,
 * and inserts reservations with room assignments.
 */
$pageTitle = 'Migration: Import NS Schedule — Church Facility Manager';
require_once __DIR__ . '/../includes/nav.php';
require_once __DIR__ . '/../config/database.php';
requireLogin();
if (!isAdmin()) { echo '<p style="color:red;padding:40px;">Admin access required.</p></body></html>'; exit; }
$db = getDB();

$jsonFile = __DIR__ . '/../import_data.json';
if (!file_exists($jsonFile)) {
    echo '<p style="color:red;padding:40px;">import_data.json not found.</p></body></html>';
    exit;
}
$records = json_decode(file_get_contents($jsonFile), true);
$totalRecords = count($records);

// Build room lookup: name (lowercase) => id, room_number => id, abbreviation => id
$roomRows = $db->query("
    SELECT r.id, LOWER(r.name) AS lname, r.room_number, LOWER(COALESCE(r.abbreviation,'')) AS abbr
    FROM rooms r
    JOIN floors f ON r.floor_id = f.id
    JOIN buildings b ON f.building_id = b.id
    WHERE r.is_reservable = 1
")->fetchAll();

$roomByName = [];
$roomByNumber = [];
$roomByAbbr = [];
foreach ($roomRows as $r) {
    $roomByName[$r['lname']] = $r['id'];
    if ($r['room_number']) $roomByNumber[$r['room_number']] = $r['id'];
    if ($r['abbr']) $roomByAbbr[$r['abbr']] = $r['id'];
}

// Map user-friendly names to DB room names/numbers
$nameAliases = [
    'break room'        => 'break room',
    'staff break room'  => 'staff break room',
    'prayer room east'  => 'prayer room east',
    'prayer room west'  => 'prayer room west',
    'preschool room'    => 'preschool',
    'preschool'         => 'preschool',
    'family room'       => 'family room',
    'fireside room'     => 'fireside',
    'fireside cafe'     => 'fireside cafe',
    'children\'s lobby' => 'children\'s lobby',
    'classrooms'        => 'classrooms',
    'coffee'            => 'coffee',
    'lobby'             => 'lobby',
    'lounge'            => 'lounge',
    'stage'             => 'stage',
];

function resolveRoomId($roomRef, $roomByName, $roomByNumber, $roomByAbbr, $nameAliases) {
    $ref = trim($roomRef);
    $lower = strtolower($ref);

    // Direct name match
    if (isset($roomByName[$lower])) return $roomByName[$lower];

    // Alias match
    if (isset($nameAliases[$lower]) && isset($roomByName[$nameAliases[$lower]])) {
        return $roomByName[$nameAliases[$lower]];
    }

    // Number match (e.g. "100", "201")
    if (isset($roomByNumber[$ref])) return $roomByNumber[$ref];

    // Abbreviation match
    if (isset($roomByAbbr[$lower])) return $roomByAbbr[$lower];

    return null;
}

// Build user lookup for created_by
$userRows = $db->query("SELECT id, name, email FROM users")->fetchAll();
$userByName = [];
$userByEmail = [];
foreach ($userRows as $u) {
    $userByName[strtolower($u['name'])] = $u['id'];
    $userByEmail[strtolower($u['email'])] = $u['id'];
}

// Org lookup
$orgRows = $db->query("SELECT id, name FROM organizations")->fetchAll();
$orgByName = [];
foreach ($orgRows as $o) {
    $orgByName[strtolower($o['name'])] = $o['id'];
}

$confirmed = ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'yes');
?>
<div class="max-w-3xl mx-auto px-4 py-10">
    <h1 class="text-2xl font-bold text-gray-800 mb-2">Migration: Import NS Schedule</h1>
    <p class="text-gray-500 text-sm mb-6">Imports <?= number_format($totalRecords) ?> reservations from NS Schedule.xlsx into the database.</p>

    <?php if (!$confirmed): ?>
        <?php
        // Preview: check room mapping
        $unmapped = [];
        foreach ($records as $rec) {
            foreach ($rec['rooms'] as $rm) {
                $id = resolveRoomId($rm, $roomByName, $roomByNumber, $roomByAbbr, $nameAliases);
                if (!$id && !in_array($rm, $unmapped)) $unmapped[] = $rm;
            }
        }
        ?>
        <div class="bg-white rounded-2xl shadow-sm border p-6 mb-4">
            <h2 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-3">Import Summary</h2>
            <ul class="text-sm text-gray-700 space-y-1">
                <li><strong><?= number_format($totalRecords) ?></strong> reservations to import</li>
                <li>Date range: <strong><?= htmlspecialchars($records[0]['start_datetime']) ?></strong> to <strong><?= htmlspecialchars(end($records)['start_datetime']) ?></strong></li>
            </ul>

            <?php if ($unmapped): ?>
                <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <p class="text-sm font-bold text-yellow-800 mb-1">Unmapped rooms (<?= count($unmapped) ?>):</p>
                    <p class="text-sm text-yellow-700"><?= htmlspecialchars(implode(', ', $unmapped)) ?></p>
                    <p class="text-xs text-yellow-600 mt-1">These rooms won't be linked. Reservations will still be created.</p>
                </div>
            <?php else: ?>
                <p class="mt-3 text-sm text-green-700">All room names map to existing rooms in the database.</p>
            <?php endif; ?>

            <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mt-4 mb-2">Room Mapping</h3>
            <div class="text-xs text-gray-600 space-y-0.5 max-h-48 overflow-y-auto">
                <?php
                $allRoomRefs = [];
                foreach ($records as $rec) foreach ($rec['rooms'] as $rm) {
                    $key = trim($rm);
                    if (!isset($allRoomRefs[$key])) $allRoomRefs[$key] = 0;
                    $allRoomRefs[$key]++;
                }
                ksort($allRoomRefs);
                foreach ($allRoomRefs as $ref => $count):
                    $rid = resolveRoomId($ref, $roomByName, $roomByNumber, $roomByAbbr, $nameAliases);
                    $status = $rid ? "ID $rid" : "NOT FOUND";
                    $color = $rid ? 'text-green-700' : 'text-red-600';
                ?>
                    <div><span class="<?= $color ?>"><?= htmlspecialchars($ref) ?> &rarr; <?= $status ?></span> <span class="text-gray-400">(<?= $count ?>x)</span></div>
                <?php endforeach; ?>
            </div>
        </div>
        <form method="POST">
            <input type="hidden" name="confirm" value="yes">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg px-6 py-2 text-sm transition">
                Import <?= number_format($totalRecords) ?> Reservations
            </button>
        </form>

    <?php else: ?>
        <div class="bg-white rounded-2xl shadow-sm border p-6">
            <h2 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-3">Import Results</h2>
            <?php
            $inserted = 0;
            $skipped = 0;
            $roomLinked = 0;
            $roomMissed = 0;
            $errors = [];

            $insertStmt = $db->prepare("
                INSERT INTO reservations
                    (title, description, organization_id, start_datetime, end_datetime,
                     notes, contact_name, contact_phone, contact_email, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $rrStmt = $db->prepare("INSERT INTO reservation_rooms (reservation_id, room_id) VALUES (?, ?)");
            $orgInsert = $db->prepare("INSERT INTO organizations (name) VALUES (?)");

            foreach ($records as $i => $rec) {
                try {
                    // Resolve organization
                    $orgId = null;
                    if ($rec['organization']) {
                        $orgKey = strtolower($rec['organization']);
                        if (isset($orgByName[$orgKey])) {
                            $orgId = $orgByName[$orgKey];
                        } else {
                            $orgInsert->execute([$rec['organization']]);
                            $orgId = (int)$db->lastInsertId();
                            $orgByName[$orgKey] = $orgId;
                        }
                    }

                    // Resolve created_by user
                    $userId = null;
                    if ($rec['created_by_raw']) {
                        $raw = strtolower($rec['created_by_raw']);
                        if (isset($userByEmail[$raw])) $userId = $userByEmail[$raw];
                        elseif (isset($userByName[$raw])) $userId = $userByName[$raw];
                    }

                    // Insert reservation
                    $insertStmt->execute([
                        $rec['title'],
                        $rec['description'],
                        $orgId,
                        $rec['start_datetime'],
                        $rec['end_datetime'],
                        $rec['notes'],
                        $rec['contact_name'],
                        $rec['contact_phone'],
                        $rec['contact_email'],
                        $userId
                    ]);
                    $resId = (int)$db->lastInsertId();
                    $inserted++;

                    // Link rooms
                    foreach ($rec['rooms'] as $rm) {
                        $roomId = resolveRoomId($rm, $roomByName, $roomByNumber, $roomByAbbr, $nameAliases);
                        if ($roomId) {
                            $rrStmt->execute([$resId, $roomId]);
                            $roomLinked++;
                        } else {
                            $roomMissed++;
                        }
                    }
                } catch (Exception $e) {
                    $errors[] = "Row $i: " . $e->getMessage();
                    $skipped++;
                }
            }
            ?>
            <div class="space-y-2 text-sm">
                <p class="text-green-700"><strong><?= number_format($inserted) ?></strong> reservations imported</p>
                <p class="text-blue-700"><strong><?= number_format($roomLinked) ?></strong> room links created</p>
                <?php if ($roomMissed): ?>
                    <p class="text-yellow-700"><strong><?= $roomMissed ?></strong> room references could not be mapped</p>
                <?php endif; ?>
                <?php if ($skipped): ?>
                    <p class="text-red-700"><strong><?= $skipped ?></strong> rows skipped due to errors</p>
                <?php endif; ?>
                <?php if ($errors): ?>
                    <div class="mt-2 p-3 bg-red-50 border border-red-200 rounded-lg text-xs text-red-700 max-h-32 overflow-y-auto">
                        <?php foreach (array_slice($errors, 0, 20) as $err): ?>
                            <div><?= htmlspecialchars($err) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="mt-4">
                <a href="/pages/reservations.php" class="text-blue-600 hover:underline text-sm font-medium">&larr; Back to Reservations</a>
            </div>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
