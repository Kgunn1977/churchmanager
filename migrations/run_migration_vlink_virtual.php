<?php
/**
 * Migration: V-Link Virtual Rooms
 *
 * Adds virtual_room_id column to room_links table so V-Link groups
 * can have a single virtual room (like H-Link combinations do).
 * Also backfills existing V-Link groups with virtual rooms.
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if (!isAdmin()) { echo 'Admin only'; exit; }
require_once __DIR__ . '/../config/database.php';
$db = getDB();

// ── Check if column already exists ──
$cols = $db->query("SHOW COLUMNS FROM room_links LIKE 'virtual_room_id'")->fetchAll();
$alreadyHasCol = count($cols) > 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'yes') {
    $results = [];

    // 1. Add virtual_room_id column if needed
    if (!$alreadyHasCol) {
        try {
            $db->exec("ALTER TABLE room_links ADD COLUMN virtual_room_id INT DEFAULT NULL COMMENT 'Auto-created virtual room representing this V-Link group'");
            $results[] = ['ALTER TABLE room_links ADD virtual_room_id', 'OK'];
        } catch (Exception $e) {
            $results[] = ['ALTER TABLE room_links ADD virtual_room_id', 'FAIL: ' . $e->getMessage()];
        }
    } else {
        $results[] = ['virtual_room_id column', 'Already exists — skipped'];
    }

    // 2. Backfill existing V-Link groups that don't have a virtual room yet
    $stmt = $db->query("
        SELECT rl.id, rl.name, rl.building_id,
               GROUP_CONCAT(rlm.room_id ORDER BY rlm.room_id) AS room_ids_csv
        FROM room_links rl
        JOIN room_link_members rlm ON rlm.link_id = rl.id
        WHERE rl.virtual_room_id IS NULL
        GROUP BY rl.id
    ");
    $groups = $stmt->fetchAll();

    foreach ($groups as $grp) {
        $roomIds = array_map('intval', explode(',', $grp['room_ids_csv']));
        $ph = implode(',', array_fill(0, count($roomIds), '?'));

        try {
            $db->beginTransaction();

            // Sum capacity
            $s = $db->prepare("SELECT COALESCE(SUM(capacity), 0) FROM rooms WHERE id IN ($ph)");
            $s->execute($roomIds);
            $totalCap = (int)$s->fetchColumn();

            // Merge polygons (convex hull)
            $s = $db->prepare("SELECT id, map_points FROM rooms WHERE id IN ($ph)");
            $s->execute($roomIds);
            $memberRooms = $s->fetchAll();
            $mergedPoints = _mergePolygons($memberRooms);

            // Determine floor_id — use first member's floor
            $s = $db->prepare("SELECT floor_id FROM rooms WHERE id = ?");
            $s->execute([$roomIds[0]]);
            $floorId = (int)$s->fetchColumn();

            // Auto abbreviation
            $abbr = _autoAbbrev($grp['name']);

            // Create virtual room
            $s = $db->prepare("
                INSERT INTO rooms (floor_id, name, abbreviation, capacity, map_points, is_reservable, is_storage, is_virtual)
                VALUES (?, ?, ?, ?, ?, 1, 0, 1)
            ");
            $s->execute([$floorId, $grp['name'], $abbr, $totalCap, $mergedPoints ? json_encode($mergedPoints) : null]);
            $virtualRoomId = (int)$db->lastInsertId();

            // Link back
            $db->prepare("UPDATE room_links SET virtual_room_id = ? WHERE id = ?")->execute([$virtualRoomId, $grp['id']]);

            $db->commit();
            $results[] = ["Backfill V-Link \"{$grp['name']}\" → virtual room #$virtualRoomId", 'OK'];
        } catch (Exception $e) {
            $db->rollBack();
            $results[] = ["Backfill V-Link \"{$grp['name']}\"", 'FAIL: ' . $e->getMessage()];
        }
    }

    if (empty($groups)) {
        $results[] = ['Backfill', 'No V-Link groups need backfilling'];
    }

    // Show results
    echo '<!DOCTYPE html><html><head><title>Migration: V-Link Virtual Rooms</title>';
    echo '<script src="https://cdn.tailwindcss.com"></script></head>';
    echo '<body class="bg-gray-50 p-8 font-sans">';
    echo '<div class="max-w-2xl mx-auto bg-white rounded-2xl shadow p-6">';
    echo '<h1 class="text-xl font-bold mb-4">Migration Results</h1>';
    echo '<table class="w-full text-sm">';
    foreach ($results as [$desc, $status]) {
        $color = str_contains($status, 'FAIL') ? 'text-red-600' : 'text-green-600';
        echo "<tr><td class='py-1'>$desc</td><td class='py-1 $color font-medium'>$status</td></tr>";
    }
    echo '</table>';
    echo '<a href="/pages/facilities.php" class="inline-block mt-4 text-blue-600 hover:underline">← Back to Facilities</a>';
    echo '</div></body></html>';
    exit;
}

// ── Confirmation screen ──
?>
<!DOCTYPE html>
<html><head><title>Migration: V-Link Virtual Rooms</title>
<script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-gray-50 p-8 font-sans">
<div class="max-w-2xl mx-auto bg-white rounded-2xl shadow p-6">
    <h1 class="text-xl font-bold mb-2">Migration: V-Link Virtual Rooms</h1>
    <p class="text-gray-600 mb-4">This migration will:</p>
    <ul class="list-disc ml-6 text-sm text-gray-700 space-y-1 mb-4">
        <li>Add <code>virtual_room_id</code> column to <code>room_links</code> table</li>
        <li>Create a virtual room for each existing V-Link group (backfill)</li>
        <li>Virtual rooms get merged polygon, summed capacity, and <code>is_virtual = 1</code></li>
    </ul>
    <?php if ($alreadyHasCol): ?>
        <p class="text-amber-600 text-sm mb-4">Note: <code>virtual_room_id</code> column already exists. Only backfill will run.</p>
    <?php endif; ?>
    <form method="POST">
        <input type="hidden" name="confirm" value="yes">
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg px-4 py-2 text-sm transition">
            Run Migration
        </button>
        <a href="/pages/facilities.php" class="ml-3 text-gray-500 hover:text-gray-700 text-sm">Cancel</a>
    </form>
</div>
</body></html>
<?php

// ── Helper functions (same as h_link_api.php) ──
function _mergePolygons(array $memberRooms): ?array {
    $allPts = [];
    foreach ($memberRooms as $r) {
        $pts = $r['map_points'] ? json_decode($r['map_points'], true) : null;
        if ($pts && is_array($pts)) {
            foreach ($pts as $p) $allPts[] = $p;
        }
    }
    if (count($allPts) < 3) return null;

    usort($allPts, fn($a, $b) => $a[0] === $b[0] ? $a[1] <=> $b[1] : $a[0] <=> $b[0]);
    $allPts = array_values(array_unique(array_map('json_encode', $allPts)));
    $allPts = array_map('json_decode', $allPts);

    if (count($allPts) < 3) return array_map(fn($p) => [(float)$p[0], (float)$p[1]], $allPts);

    $cross = fn($O, $A, $B) => ($A[0] - $O[0]) * ($B[1] - $O[1]) - ($A[1] - $O[1]) * ($B[0] - $O[0]);
    $lower = [];
    foreach ($allPts as $p) {
        while (count($lower) >= 2 && $cross($lower[count($lower)-2], $lower[count($lower)-1], $p) <= 0)
            array_pop($lower);
        $lower[] = $p;
    }
    $upper = [];
    foreach (array_reverse($allPts) as $p) {
        while (count($upper) >= 2 && $cross($upper[count($upper)-2], $upper[count($upper)-1], $p) <= 0)
            array_pop($upper);
        $upper[] = $p;
    }
    array_pop($lower); array_pop($upper);
    $hull = array_merge($lower, $upper);
    return array_map(fn($p) => [(float)$p[0], (float)$p[1]], $hull);
}

function _autoAbbrev(string $name): string {
    $w = preg_split('/\s+/', trim($name));
    if (count($w) === 1) return strtoupper(substr($name, 0, 4));
    return strtoupper(substr(implode('', array_map(fn($x) => $x[0], $w)), 0, 5));
}
