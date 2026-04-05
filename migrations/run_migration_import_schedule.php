<?php
$pageTitle = 'Migration: Import Schedule — Church Facility Manager';
require_once __DIR__ . '/../includes/nav.php';
require_once __DIR__ . '/../config/database.php';
requireLogin();
if (!isAdmin()) { echo '<p>Admin only.</p></body></html>'; exit; }

$db = getDB();

// ─── helpers ────────────────────────────────────────────────────────────────

function parseTime(string $t): ?string {
    $t = strtolower(trim($t));
    if (!$t) return null;
    // e.g. "1pm", "5:30pm", "11:30am"
    if (preg_match('/^(\d{1,2})(?::(\d{2}))?\s*(am|pm)$/i', $t, $m)) {
        $h = (int)$m[1];
        $min = isset($m[2]) && $m[2] !== '' ? (int)$m[2] : 0;
        $ampm = strtolower($m[3]);
        if ($ampm === 'pm' && $h < 12) $h += 12;
        if ($ampm === 'am' && $h === 12) $h = 0;
        return sprintf('%02d:%02d:00', $h, $min);
    }
    return null;
}

// ─── Load CSV (pre-converted from xlsx) ─────────────────────────────────────

$csvPath = __DIR__ . '/../NS Schedule.csv';
if (!file_exists($csvPath)) {
    echo '<p class="text-red-600 font-bold">NS Schedule.csv not found in project root. Convert the xlsx first.</p></body></html>';
    exit;
}

$rows = [];
if (($fh = fopen($csvPath, 'r')) !== false) {
    $header = fgetcsv($fh);
    while (($line = fgetcsv($fh)) !== false) {
        $row = array_combine($header, $line);
        if ($row) $rows[] = $row;
    }
    fclose($fh);
}
$totalRows = count($rows);

// ─── Build room maps ────────────────────────────────────────────────────────

// Link groups (checked first)
$linkStmt = $db->query("
    SELECT rl.id AS link_id, rl.name AS link_name, GROUP_CONCAT(rlm.room_id) AS member_ids
    FROM room_links rl
    JOIN room_link_members rlm ON rl.id = rlm.link_id
    GROUP BY rl.id
");
$linkGroups = []; // name => {link_id, room_ids[]}
foreach ($linkStmt->fetchAll() as $lg) {
    $linkGroups[$lg['link_name']] = [
        'link_id'  => (int)$lg['link_id'],
        'room_ids' => array_map('intval', explode(',', $lg['member_ids'])),
    ];
}

// Individual rooms
$roomStmt = $db->query("SELECT id, name FROM rooms ORDER BY name");
$roomMap = []; // name => id  (first match wins)
foreach ($roomStmt->fetchAll() as $r) {
    if (!isset($roomMap[$r['name']])) {
        $roomMap[$r['name']] = (int)$r['id'];
    }
}

// ─── Resolve each schedule row ──────────────────────────────────────────────

$parsed = [];
$warnings = [];

foreach ($rows as $i => $row) {
    $title     = trim($row['Title'] ?? '');
    $desc      = trim($row['Description'] ?? '');
    $org       = trim($row['Organization'] ?? '');
    $dateRaw   = trim($row['Date'] ?? '');
    $startRaw  = trim($row['Start Time'] ?? '');
    $endRaw    = trim($row['End Time'] ?? '');
    $roomsRaw  = trim($row['Rooms'] ?? '');
    $contact   = trim($row['Contact Name'] ?? '');
    $phone     = trim($row['Contact Phone'] ?? '');
    $email     = trim($row['Contact Email'] ?? '');
    $notes     = trim($row['Notes'] ?? '');

    // Parse date
    $date = date('Y-m-d', strtotime($dateRaw));
    if (!$date || $date === '1970-01-01') {
        $warnings[] = "Row " . ($i+2) . ": bad date '$dateRaw' — skipped";
        continue;
    }

    // Parse times
    $startTime = parseTime($startRaw);
    $endTime   = parseTime($endRaw);
    if (!$startTime || !$endTime) {
        $warnings[] = "Row " . ($i+2) . ": bad time '$startRaw'-'$endRaw' — skipped";
        continue;
    }

    $startDt = "$date $startTime";
    $endDt   = "$date $endTime";

    // Resolve rooms
    $roomNames = array_map('trim', explode(',', $roomsRaw));
    $resolvedRoomIds = [];
    $linkId = null;
    $unmatchedRooms = [];

    foreach ($roomNames as $rn) {
        if ($rn === '') continue;
        // Check link groups first
        if (isset($linkGroups[$rn])) {
            $linkId = $linkGroups[$rn]['link_id'];
            foreach ($linkGroups[$rn]['room_ids'] as $rid) {
                $resolvedRoomIds[$rid] = true;
            }
        } elseif (isset($roomMap[$rn])) {
            $resolvedRoomIds[$roomMap[$rn]] = true;
        } else {
            $unmatchedRooms[] = $rn;
        }
    }

    if (!empty($unmatchedRooms)) {
        $warnings[] = "Row " . ($i+2) . " ($title): unmatched rooms: " . implode(', ', $unmatchedRooms);
    }

    if (empty($resolvedRoomIds) && empty($unmatchedRooms)) {
        // No rooms at all (3 "Keep Free" rows)
        $warnings[] = "Row " . ($i+2) . " ($title): no rooms — skipped";
        continue;
    }

    if (empty($resolvedRoomIds)) continue;

    $parsed[] = [
        'title'      => $title,
        'desc'       => $desc,
        'org'        => $org,
        'start_dt'   => $startDt,
        'end_dt'     => $endDt,
        'room_ids'   => array_keys($resolvedRoomIds),
        'link_id'    => $linkId,
        'contact'    => $contact,
        'phone'      => $phone,
        'email'      => $email,
        'notes'      => $notes,
    ];
}

$importCount = count($parsed);

// ─── Unique organizations to create ─────────────────────────────────────────

$existingOrgs = [];
$orgStmt = $db->query("SELECT id, name FROM organizations");
foreach ($orgStmt->fetchAll() as $o) $existingOrgs[strtolower($o['name'])] = (int)$o['id'];

$schedOrgs = [];
foreach ($parsed as $p) {
    if ($p['org'] !== '' && !isset($existingOrgs[strtolower($p['org'])])) {
        $schedOrgs[$p['org']] = true;
    }
}
$newOrgs = array_keys($schedOrgs);

// ─── Confirmation screen ────────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['confirm'] ?? '') !== 'yes') {
?>
<div class="max-w-3xl mx-auto px-4 py-10">
    <h1 class="text-2xl font-bold text-gray-900 mb-2">Migration: Import Schedule</h1>
    <p class="text-gray-600 mb-6">Imports reservations from <strong>NS Schedule.xlsx</strong> into the database.</p>

    <div class="bg-white rounded-xl border p-6 mb-6 space-y-4">
        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Summary</p>
        <ul class="text-sm text-gray-700 space-y-1">
            <li>📄 <strong><?= $totalRows ?></strong> rows in spreadsheet</li>
            <li>✅ <strong><?= $importCount ?></strong> reservations to import</li>
            <li>🏢 <strong><?= count($newOrgs) ?></strong> new organization<?= count($newOrgs) !== 1 ? 's' : '' ?> to create<?= !empty($newOrgs) ? ': ' . htmlspecialchars(implode(', ', $newOrgs)) : '' ?></li>
            <li>⚠️ <strong><?= count($warnings) ?></strong> warning<?= count($warnings) !== 1 ? 's' : '' ?></li>
        </ul>

        <?php if (!empty($warnings)): ?>
        <details class="mt-3">
            <summary class="text-sm text-amber-700 cursor-pointer font-medium">Show warnings</summary>
            <ul class="mt-2 text-xs text-amber-800 space-y-1 max-h-60 overflow-y-auto">
                <?php foreach ($warnings as $w): ?>
                <li><?= htmlspecialchars($w) ?></li>
                <?php endforeach; ?>
            </ul>
        </details>
        <?php endif; ?>

        <details class="mt-3">
            <summary class="text-sm text-gray-500 cursor-pointer font-medium">Preview first 20 reservations</summary>
            <table class="mt-2 text-xs w-full">
                <thead><tr class="text-left text-gray-400"><th class="pr-2">Title</th><th class="pr-2">Date/Time</th><th class="pr-2">Rooms</th><th>Link</th></tr></thead>
                <tbody>
                <?php foreach (array_slice($parsed, 0, 20) as $p): ?>
                <tr class="border-t border-gray-100">
                    <td class="pr-2 py-1"><?= htmlspecialchars($p['title']) ?></td>
                    <td class="pr-2 py-1 whitespace-nowrap"><?= htmlspecialchars($p['start_dt']) ?> → <?= htmlspecialchars(substr($p['end_dt'], 11)) ?></td>
                    <td class="pr-2 py-1"><?= count($p['room_ids']) ?> room<?= count($p['room_ids']) !== 1 ? 's' : '' ?></td>
                    <td class="py-1"><?= $p['link_id'] ? '🔗 #'.$p['link_id'] : '—' ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </details>
    </div>

    <form method="POST">
        <input type="hidden" name="confirm" value="yes">
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg px-6 py-3 text-sm transition">
            Import <?= $importCount ?> Reservations
        </button>
    </form>
</div>
<?php
    echo '</body></html>';
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════════
// EXECUTE IMPORT
// ═══════════════════════════════════════════════════════════════════════════════

$results = ['created' => 0, 'errors' => [], 'orgs_created' => 0];

// 1) Create new organizations
foreach ($newOrgs as $orgName) {
    try {
        $stmt = $db->prepare("INSERT INTO organizations (name) VALUES (?)");
        $stmt->execute([$orgName]);
        $existingOrgs[strtolower($orgName)] = (int)$db->lastInsertId();
        $results['orgs_created']++;
    } catch (Exception $e) {
        $results['errors'][] = "Org '$orgName': " . $e->getMessage();
    }
}

// 2) Get current user
$user = getCurrentUser();
$userId = $user['id'] ?? 1;

// 3) Insert reservations
$resStmt = $db->prepare("
    INSERT INTO reservations
        (title, description, organization_id, start_datetime, end_datetime, notes,
         contact_name, contact_phone, contact_email, link_id, created_by)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$rrStmt = $db->prepare("INSERT INTO reservation_rooms (reservation_id, room_id) VALUES (?, ?)");

foreach ($parsed as $p) {
    try {
        $orgId = null;
        if ($p['org'] !== '') {
            $orgId = $existingOrgs[strtolower($p['org'])] ?? null;
        }

        $resStmt->execute([
            $p['title'],
            $p['desc'] ?: null,
            $orgId,
            $p['start_dt'],
            $p['end_dt'],
            $p['notes'] ?: null,
            $p['contact'] ?: null,
            $p['phone'] ?: null,
            $p['email'] ?: null,
            $p['link_id'],
            $userId,
        ]);
        $resId = (int)$db->lastInsertId();

        foreach ($p['room_ids'] as $rid) {
            $rrStmt->execute([$resId, $rid]);
        }

        $results['created']++;
    } catch (Exception $e) {
        $results['errors'][] = htmlspecialchars($p['title'] . ' (' . $p['start_dt'] . '): ' . $e->getMessage());
    }
}

// ─── Results ────────────────────────────────────────────────────────────────
?>
<div class="max-w-3xl mx-auto px-4 py-10">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Migration: Import Schedule</h1>

    <div class="bg-white rounded-xl border p-6 space-y-3">
        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Results</p>
        <p class="text-green-700">✓ <?= $results['created'] ?> reservations imported</p>
        <?php if ($results['orgs_created']): ?>
        <p class="text-green-700">✓ <?= $results['orgs_created'] ?> organization<?= $results['orgs_created'] !== 1 ? 's' : '' ?> created</p>
        <?php endif; ?>
        <?php if (!empty($results['errors'])): ?>
        <p class="text-red-600 font-medium"><?= count($results['errors']) ?> errors:</p>
        <ul class="text-xs text-red-700 space-y-1 max-h-60 overflow-y-auto">
            <?php foreach ($results['errors'] as $e): ?>
            <li><?= $e ?></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <?php if (!empty($warnings)): ?>
        <details class="mt-2">
            <summary class="text-sm text-amber-700 cursor-pointer font-medium"><?= count($warnings) ?> warnings (skipped rows)</summary>
            <ul class="mt-2 text-xs text-amber-800 space-y-1 max-h-40 overflow-y-auto">
                <?php foreach ($warnings as $w): ?>
                <li><?= htmlspecialchars($w) ?></li>
                <?php endforeach; ?>
            </ul>
        </details>
        <?php endif; ?>

        <a href="/pages/reservations.php" class="inline-block mt-4 text-blue-600 hover:underline text-sm">← Back to Reservations</a>
    </div>
</div>
<?php
echo '</body></html>';
