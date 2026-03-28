<?php
$pageTitle = 'Facilities — Church Facility Manager';
require_once __DIR__ . '/../includes/nav.php';
require_once __DIR__ . '/../config/database.php';

$db = getDB();

// ─── Determine current view ───────────────────────────────────────────────────
$buildingId = isset($_GET['building_id']) ? (int)$_GET['building_id'] : null;
$floorId    = isset($_GET['floor_id'])    ? (int)$_GET['floor_id']    : null;
$action     = $_GET['action'] ?? null;
$success    = $_GET['saved']  ?? null;

// ─── Handle POST actions ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';

    // Add Building
    if ($type === 'building') {
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        if ($name) {
            $stmt = $db->prepare("INSERT INTO buildings (name, description) VALUES (?, ?)");
            $stmt->execute([$name, $desc]);
        }
        header('Location: /pages/facilities.php?saved=1');
        exit;
    }

    // Add Floor
    if ($type === 'floor') {
        $bid   = (int)($_POST['building_id'] ?? 0);
        $name  = trim($_POST['name'] ?? '');
        $order = (int)($_POST['floor_order'] ?? 1);
        if ($bid && $name) {
            $stmt = $db->prepare("INSERT INTO floors (building_id, name, floor_order) VALUES (?, ?, ?)");
            $stmt->execute([$bid, $name, $order]);
        }
        header("Location: /pages/facilities.php?building_id=$bid&saved=1");
        exit;
    }

    // Add Room
    if ($type === 'room') {
        $fid      = (int)($_POST['floor_id']    ?? 0);
        $bid      = (int)($_POST['building_id'] ?? 0);
        $name     = trim($_POST['name']         ?? '');
        $number   = trim($_POST['room_number']  ?? '');
        $capacity = (int)($_POST['capacity']    ?? 0);
        $desc     = trim($_POST['description']  ?? '');
        if ($fid && $name) {
            $stmt = $db->prepare("INSERT INTO rooms (floor_id, name, room_number, capacity, description) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$fid, $name, $number, $capacity ?: null, $desc]);
        }
        header("Location: /pages/facilities.php?floor_id=$fid&building_id=$bid&saved=1");
        exit;
    }

    // Delete Building
    if ($type === 'delete_building') {
        $bid = (int)($_POST['id'] ?? 0);
        $db->prepare("DELETE FROM buildings WHERE id = ?")->execute([$bid]);
        header('Location: /pages/facilities.php?saved=1');
        exit;
    }

    // Delete Floor
    if ($type === 'delete_floor') {
        $fid = (int)($_POST['id'] ?? 0);
        $bid = (int)($_POST['building_id'] ?? 0);
        $db->prepare("DELETE FROM floors WHERE id = ?")->execute([$fid]);
        header("Location: /pages/facilities.php?building_id=$bid&saved=1");
        exit;
    }

    // Delete Room
    if ($type === 'delete_room') {
        $rid = (int)($_POST['id'] ?? 0);
        $fid = (int)($_POST['floor_id'] ?? 0);
        $bid = (int)($_POST['building_id'] ?? 0);
        $db->prepare("DELETE FROM rooms WHERE id = ?")->execute([$rid]);
        header("Location: /pages/facilities.php?floor_id=$fid&building_id=$bid&saved=1");
        exit;
    }
}

// ─── Fetch data ───────────────────────────────────────────────────────────────
$buildings = $db->query("SELECT b.*, COUNT(DISTINCT f.id) AS floor_count FROM buildings b LEFT JOIN floors f ON f.building_id = b.id GROUP BY b.id ORDER BY b.name")->fetchAll();

$currentBuilding = null;
$floors          = [];
if ($buildingId) {
    $stmt = $db->prepare("SELECT * FROM buildings WHERE id = ?");
    $stmt->execute([$buildingId]);
    $currentBuilding = $stmt->fetch();

    $floors = $db->prepare("SELECT f.*, COUNT(r.id) AS room_count FROM floors f LEFT JOIN rooms r ON r.floor_id = f.id WHERE f.building_id = ? GROUP BY f.id ORDER BY f.floor_order");
    $floors->execute([$buildingId]);
    $floors = $floors->fetchAll();
}

$currentFloor = null;
$rooms        = [];
if ($floorId) {
    $stmt = $db->prepare("SELECT f.*, b.name AS building_name, b.id AS building_id FROM floors f JOIN buildings b ON b.id = f.building_id WHERE f.id = ?");
    $stmt->execute([$floorId]);
    $currentFloor = $stmt->fetch();

    if ($currentFloor) {
        $buildingId = $currentFloor['building_id'];
        $stmt = $db->prepare("SELECT * FROM buildings WHERE id = ?");
        $stmt->execute([$buildingId]);
        $currentBuilding = $stmt->fetch();
    }

    $stmt = $db->prepare("SELECT * FROM rooms WHERE floor_id = ? ORDER BY room_number, name");
    $stmt->execute([$floorId]);
    $rooms = $stmt->fetchAll();
}
?>

<main class="max-w-6xl mx-auto px-4 py-8">

    <?php if ($success): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl p-3 mb-5 text-sm flex items-center gap-2">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            Saved successfully.
        </div>
    <?php endif; ?>

    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <a href="/pages/facilities.php" class="hover:text-blue-600 transition font-medium
            <?= !$buildingId && !$floorId ? 'text-blue-700' : '' ?>">
            Buildings
        </a>
        <?php if ($currentBuilding): ?>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <a href="/pages/facilities.php?building_id=<?= $currentBuilding['id'] ?>"
               class="hover:text-blue-600 transition font-medium <?= $buildingId && !$floorId ? 'text-blue-700' : '' ?>">
                <?= htmlspecialchars($currentBuilding['name']) ?>
            </a>
        <?php endif; ?>
        <?php if ($currentFloor): ?>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-blue-700 font-medium"><?= htmlspecialchars($currentFloor['name']) ?></span>
        <?php endif; ?>
    </nav>

    <?php
    // ══════════════════════════════════════════════════════
    // VIEW 1: Buildings List
    // ══════════════════════════════════════════════════════
    if (!$buildingId && !$floorId):
    ?>

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Buildings</h1>
                <p class="text-gray-500 text-sm mt-0.5">Select a building to manage its floors and rooms.</p>
            </div>
            <button onclick="document.getElementById('addBuildingModal').classList.remove('hidden')"
                    class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-xl transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Building
            </button>
        </div>

        <?php if (empty($buildings)): ?>
            <div class="bg-white rounded-2xl shadow-sm p-12 text-center text-gray-400">
                <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/>
                </svg>
                <p class="font-medium">No buildings yet.</p>
                <p class="text-sm mt-1">Click "Add Building" to get started.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($buildings as $b): ?>
                    <div class="bg-white rounded-2xl shadow-sm p-5 hover:shadow-md transition border border-transparent hover:border-blue-200">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($b['name']) ?></h3>
                                <?php if ($b['description']): ?>
                                    <p class="text-gray-500 text-sm mt-1"><?= htmlspecialchars($b['description']) ?></p>
                                <?php endif; ?>
                                <p class="text-blue-500 text-sm mt-2 font-medium">
                                    <?= $b['floor_count'] ?> floor<?= $b['floor_count'] != 1 ? 's' : '' ?>
                                </p>
                            </div>
                            <form method="POST" onsubmit="return confirm('Delete this building and all its floors and rooms?')">
                                <input type="hidden" name="type" value="delete_building">
                                <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                <button type="submit" class="text-gray-300 hover:text-red-400 transition p-1" title="Delete">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                        <a href="/pages/facilities.php?building_id=<?= $b['id'] ?>"
                           class="mt-4 block text-center bg-blue-50 hover:bg-blue-100 text-blue-700 text-sm font-semibold py-2 rounded-xl transition">
                            Manage Floors & Rooms →
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Add Building Modal -->
        <div id="addBuildingModal" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-md">
                <h2 class="text-lg font-bold text-gray-800 mb-4">Add Building</h2>
                <form method="POST">
                    <input type="hidden" name="type" value="building">
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Building Name *</label>
                        <input type="text" name="name" required placeholder="e.g. Main Building"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="mb-5">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Description (optional)</label>
                        <input type="text" name="description" placeholder="e.g. Main church facility"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="flex gap-3">
                        <button type="button" onclick="document.getElementById('addBuildingModal').classList.add('hidden')"
                                class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2.5 rounded-xl text-sm transition">
                            Cancel
                        </button>
                        <button type="submit"
                                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-xl text-sm transition">
                            Add Building
                        </button>
                    </div>
                </form>
            </div>
        </div>

    <?php
    // ══════════════════════════════════════════════════════
    // VIEW 2: Floors for a Building
    // ══════════════════════════════════════════════════════
    elseif ($buildingId && !$floorId):
    ?>

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($currentBuilding['name']) ?></h1>
                <p class="text-gray-500 text-sm mt-0.5">Select a floor to manage its rooms.</p>
            </div>
            <button onclick="document.getElementById('addFloorModal').classList.remove('hidden')"
                    class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-xl transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Floor
            </button>
        </div>

        <?php if (empty($floors)): ?>
            <div class="bg-white rounded-2xl shadow-sm p-12 text-center text-gray-400">
                <p class="font-medium">No floors yet.</p>
                <p class="text-sm mt-1">Click "Add Floor" to get started.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($floors as $f): ?>
                    <div class="bg-white rounded-2xl shadow-sm p-5 hover:shadow-md transition border border-transparent hover:border-blue-200">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($f['name']) ?></h3>
                                <p class="text-blue-500 text-sm mt-1 font-medium">
                                    <?= $f['room_count'] ?> room<?= $f['room_count'] != 1 ? 's' : '' ?>
                                </p>
                            </div>
                            <form method="POST" onsubmit="return confirm('Delete this floor and all its rooms?')">
                                <input type="hidden" name="type" value="delete_floor">
                                <input type="hidden" name="id" value="<?= $f['id'] ?>">
                                <input type="hidden" name="building_id" value="<?= $buildingId ?>">
                                <button type="submit" class="text-gray-300 hover:text-red-400 transition p-1" title="Delete">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                        <a href="/pages/facilities.php?floor_id=<?= $f['id'] ?>&building_id=<?= $buildingId ?>"
                           class="mt-4 block text-center bg-blue-50 hover:bg-blue-100 text-blue-700 text-sm font-semibold py-2 rounded-xl transition">
                            Manage Rooms →
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Add Floor Modal -->
        <div id="addFloorModal" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-md">
                <h2 class="text-lg font-bold text-gray-800 mb-4">Add Floor</h2>
                <form method="POST">
                    <input type="hidden" name="type" value="floor">
                    <input type="hidden" name="building_id" value="<?= $buildingId ?>">
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Floor Name *</label>
                        <input type="text" name="name" required placeholder="e.g. First Floor"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="mb-5">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Display Order</label>
                        <input type="number" name="floor_order" value="1" min="1"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-gray-400 text-xs mt-1">Lower numbers appear first (e.g. 1 = ground floor).</p>
                    </div>
                    <div class="flex gap-3">
                        <button type="button" onclick="document.getElementById('addFloorModal').classList.add('hidden')"
                                class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2.5 rounded-xl text-sm transition">
                            Cancel
                        </button>
                        <button type="submit"
                                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-xl text-sm transition">
                            Add Floor
                        </button>
                    </div>
                </form>
            </div>
        </div>

    <?php
    // ══════════════════════════════════════════════════════
    // VIEW 3: Rooms for a Floor
    // ══════════════════════════════════════════════════════
    elseif ($floorId):
    ?>

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">
                    <?= htmlspecialchars($currentFloor['name']) ?>
                </h1>
                <p class="text-gray-500 text-sm mt-0.5">
                    <?= htmlspecialchars($currentBuilding['name']) ?>
                </p>
            </div>
            <button onclick="document.getElementById('addRoomModal').classList.remove('hidden')"
                    class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-xl transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Room
            </button>
        </div>

        <?php if (empty($rooms)): ?>
            <div class="bg-white rounded-2xl shadow-sm p-12 text-center text-gray-400">
                <p class="font-medium">No rooms on this floor yet.</p>
                <p class="text-sm mt-1">Click "Add Room" to get started.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($rooms as $r): ?>
                    <div class="bg-white rounded-2xl shadow-sm p-5 hover:shadow-md transition border border-transparent hover:border-blue-200">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <?php if ($r['room_number']): ?>
                                        <span class="bg-blue-100 text-blue-700 text-xs font-bold px-2 py-0.5 rounded-lg">
                                            <?= htmlspecialchars($r['room_number']) ?>
                                        </span>
                                    <?php endif; ?>
                                    <h3 class="font-bold text-gray-800"><?= htmlspecialchars($r['name']) ?></h3>
                                </div>
                                <?php if ($r['capacity']): ?>
                                    <p class="text-gray-500 text-sm mt-1">Capacity: <?= $r['capacity'] ?></p>
                                <?php endif; ?>
                                <?php if ($r['description']): ?>
                                    <p class="text-gray-400 text-sm mt-1"><?= htmlspecialchars($r['description']) ?></p>
                                <?php endif; ?>
                            </div>
                            <form method="POST" onsubmit="return confirm('Delete this room?')">
                                <input type="hidden" name="type" value="delete_room">
                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                <input type="hidden" name="floor_id" value="<?= $floorId ?>">
                                <input type="hidden" name="building_id" value="<?= $buildingId ?>">
                                <button type="submit" class="text-gray-300 hover:text-red-400 transition p-1" title="Delete">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                        <a href="/pages/room_profile.php?room_id=<?= $r['id'] ?>"
                           class="mt-4 block text-center bg-blue-50 hover:bg-blue-100 text-blue-700 text-sm font-semibold py-2 rounded-xl transition">
                            View Room Profile →
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Add Room Modal -->
        <div id="addRoomModal" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-md">
                <h2 class="text-lg font-bold text-gray-800 mb-4">Add Room</h2>
                <form method="POST">
                    <input type="hidden" name="type" value="room">
                    <input type="hidden" name="floor_id" value="<?= $floorId ?>">
                    <input type="hidden" name="building_id" value="<?= $buildingId ?>">
                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Room Name *</label>
                            <input type="text" name="name" required placeholder="e.g. Sanctuary"
                                   class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Room Number</label>
                            <input type="text" name="room_number" placeholder="e.g. 101"
                                   class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Capacity</label>
                        <input type="number" name="capacity" placeholder="e.g. 50" min="0"
                               class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="mb-5">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Description (optional)</label>
                        <input type="text" name="description" placeholder="e.g. Children's classroom"
                               class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="flex gap-3">
                        <button type="button" onclick="document.getElementById('addRoomModal').classList.add('hidden')"
                                class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2.5 rounded-xl text-sm transition">
                            Cancel
                        </button>
                        <button type="submit"
                                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-xl text-sm transition">
                            Add Room
                        </button>
                    </div>
                </form>
            </div>
        </div>

    <?php endif; ?>

</main>

</body>
</html>
