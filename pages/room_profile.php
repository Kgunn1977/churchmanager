<?php
$pageTitle = 'Room Profile — Church Facility Manager';
require_once __DIR__ . '/../includes/nav.php';
require_once __DIR__ . '/../config/database.php';
requireLogin();

$db     = getDB();
$roomId = (int)($_GET['room_id'] ?? 0);
if (!$roomId) { header('Location: ' . url('/pages/facilities.php')); exit; }

$stmt = $db->prepare("
    SELECT r.*, f.name AS floor_name, f.id AS floor_id, b.name AS building_name, b.id AS building_id
    FROM rooms r
    JOIN floors f ON f.id = r.floor_id
    JOIN buildings b ON b.id = f.building_id
    WHERE r.id = ?
");
$stmt->execute([$roomId]);
$room = $stmt->fetch();
if (!$room) { header('Location: ' . url('/pages/facilities.php')); exit; }

$success = $_GET['saved'] ?? null;
$tab     = $_GET['tab']   ?? 'equipment';

$eqCategories = ['furniture' => 'Furniture', 'av_tech' => 'AV & Tech', 'kitchen' => 'Kitchen', 'other' => 'Other'];

$canEdit = in_array($currentUser['role'], ['admin', 'scheduler']);

// ── Handle POST ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';

    if ($type === 'add_equipment') {
        $eqId    = (int)($_POST['equipment_id'] ?? 0);
        $qty     = max(1, (int)($_POST['quantity'] ?? 1));
        $movable = isset($_POST['is_movable']) ? 1 : 0;
        $note    = trim($_POST['notes'] ?? '');
        if ($eqId) {
            try {
                $db->prepare("INSERT INTO room_equipment (room_id, equipment_id, quantity, is_movable, notes) VALUES (?,?,?,?,?)
                              ON DUPLICATE KEY UPDATE quantity=VALUES(quantity), is_movable=VALUES(is_movable), notes=VALUES(notes)")
                   ->execute([$roomId, $eqId, $qty, $movable, $note]);
            } catch (PDOException $e) {}
        }
        header("Location: " . url("/pages/room_profile.php?room_id=$roomId&tab=equipment&saved=1")); exit;
    }

    if ($type === 'create_and_add_equipment') {
        $name    = trim($_POST['eq_name'] ?? '');
        $cat     = $_POST['eq_category'] ?? 'other';
        $desc    = trim($_POST['eq_description'] ?? '');
        $qty     = max(1, (int)($_POST['quantity'] ?? 1));
        $movable = isset($_POST['is_movable']) ? 1 : 0;
        $note    = trim($_POST['notes'] ?? '');
        if ($name) {
            $db->prepare("INSERT INTO equipment_catalog (name, description, category) VALUES (?,?,?)")->execute([$name, $desc, $cat]);
            $eqId = $db->lastInsertId();
            $db->prepare("INSERT INTO room_equipment (room_id, equipment_id, quantity, is_movable, notes) VALUES (?,?,?,?,?)
                          ON DUPLICATE KEY UPDATE quantity=VALUES(quantity), is_movable=VALUES(is_movable), notes=VALUES(notes)")
               ->execute([$roomId, $eqId, $qty, $movable, $note]);
        }
        header("Location: " . url("/pages/room_profile.php?room_id=$roomId&tab=equipment&saved=1")); exit;
    }

    if ($type === 'update_equipment') {
        $reId    = (int)($_POST['re_id'] ?? 0);
        $qty     = max(1, (int)($_POST['quantity'] ?? 1));
        $movable = isset($_POST['is_movable']) ? 1 : 0;
        $note    = trim($_POST['notes'] ?? '');
        $db->prepare("UPDATE room_equipment SET quantity=?, is_movable=?, notes=? WHERE id=? AND room_id=?")->execute([$qty, $movable, $note, $reId, $roomId]);
        header("Location: " . url("/pages/room_profile.php?room_id=$roomId&tab=equipment&saved=1")); exit;
    }

    if ($type === 'remove_equipment') {
        $reId = (int)($_POST['re_id'] ?? 0);
        $db->prepare("DELETE FROM room_equipment WHERE id=? AND room_id=?")->execute([$reId, $roomId]);
        header("Location: " . url("/pages/room_profile.php?room_id=$roomId&tab=equipment&saved=1")); exit;
    }




}

// ── Fetch data ────────────────────────────────────────────────────────────────
$stmt = $db->prepare("
    SELECT re.*, e.name AS eq_name, e.category, e.description AS eq_desc
    FROM room_equipment re
    JOIN equipment_catalog e ON e.id = re.equipment_id
    WHERE re.room_id = ?
    ORDER BY e.category, e.name
");
$stmt->execute([$roomId]);
$assignedEquipment = $stmt->fetchAll();
$assignedEqIds     = array_column($assignedEquipment, 'equipment_id');
$allEquipment      = $db->query("SELECT * FROM equipment_catalog ORDER BY category, name")->fetchAll();

$assignedTasks   = [];
$assignedTaskIds = [];

$taskMaterials = [];
if ($assignedTasks) {
    $ids = implode(',', array_map('intval', array_column($assignedTasks, 'task_id')));
    try {
        $mats = $db->query("SELECT * FROM task_materials WHERE task_id IN ($ids) ORDER BY type, name")->fetchAll();
        foreach ($mats as $m) { $taskMaterials[$m['task_id']][] = $m; }
    } catch (PDOException $e) { /* run migrate_schedule.php first */ }
}
?>

<main class="max-w-5xl mx-auto px-4 py-8">

    <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <a href="<?= url('/pages/facilities.php') ?>" class="hover:text-blue-600">Buildings</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <a href="<?= url('/pages/facilities.php?building_id=' . $room['building_id']) ?>" class="hover:text-blue-600"><?= htmlspecialchars($room['building_name']) ?></a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <a href="<?= url('/pages/facilities.php?floor_id=' . $room['floor_id'] . '&building_id=' . $room['building_id']) ?>" class="hover:text-blue-600"><?= htmlspecialchars($room['floor_name']) ?></a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-blue-700 font-medium"><?= htmlspecialchars($room['name']) ?></span>
    </nav>

    <div class="bg-white rounded-2xl shadow-sm p-6 mb-6 flex items-center justify-between">
        <div>
            <div class="flex items-center gap-3">
                <?php if ($room['room_number']): ?>
                    <span class="bg-blue-100 text-blue-700 text-sm font-bold px-3 py-1 rounded-lg"><?= htmlspecialchars($room['room_number']) ?></span>
                <?php endif; ?>
                <h1 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($room['name']) ?></h1>
            </div>
            <p class="text-gray-500 text-sm mt-1">
                <?= htmlspecialchars($room['building_name']) ?> — <?= htmlspecialchars($room['floor_name']) ?>
                <?php if ($room['capacity']): ?> — Capacity: <?= $room['capacity'] ?><?php endif; ?>
            </p>
        </div>
        <div class="flex gap-3 text-center">
            <div class="bg-blue-50 rounded-xl px-4 py-2">
                <div class="text-2xl font-bold text-blue-700"><?= count($assignedEquipment) ?></div>
                <div class="text-xs text-blue-500">Equipment</div>
            </div>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl p-3 mb-5 text-sm">✓ Saved successfully.</div>
    <?php endif; ?>


    <?php if ($tab === 'equipment'): ?>
    <!-- EQUIPMENT TAB -->
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-bold text-gray-700">Equipment in This Room</h2>
            <?php if ($canEdit): ?>
                <button onclick="openEquipmentModal()" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-xl transition flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add Equipment
                </button>
            <?php endif; ?>
        </div>
        <?php if (empty($assignedEquipment)): ?>
            <div class="bg-white rounded-2xl shadow-sm p-10 text-center text-gray-400">
                <p class="font-medium">No equipment assigned yet.</p>
                <?php if ($canEdit): ?><button onclick="openEquipmentModal()" class="mt-3 text-blue-600 text-sm hover:underline">+ Add equipment to this room</button><?php endif; ?>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="text-left px-5 py-3 font-semibold text-gray-600">Item</th>
                            <th class="text-left px-5 py-3 font-semibold text-gray-600">Category</th>
                            <th class="text-left px-5 py-3 font-semibold text-gray-600">Qty</th>
                            <th class="text-left px-5 py-3 font-semibold text-gray-600">Notes</th>
                            <?php if ($canEdit): ?><th class="px-5 py-3"></th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($assignedEquipment as $eq): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3 font-medium text-gray-800"><?= htmlspecialchars($eq['eq_name']) ?></td>
                                <td class="px-5 py-3"><span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600"><?= $eqCategories[$eq['category']] ?? $eq['category'] ?></span></td>
                                <td class="px-5 py-3 font-semibold text-gray-700"><?= $eq['quantity'] ?></td>
                                <td class="px-5 py-3 text-gray-400 text-xs"><?= htmlspecialchars($eq['notes'] ?? '') ?></td>
                                <?php if ($canEdit): ?>
                                    <td class="px-5 py-3 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button onclick='openEditEquip(<?= json_encode($eq) ?>)' class="text-blue-500 hover:text-blue-700 text-xs font-medium px-2 py-1 rounded-lg hover:bg-blue-50">Edit</button>
                                            <form method="POST" onsubmit="return confirm('Remove from this room?')">
                                                <input type="hidden" name="type" value="remove_equipment">
                                                <input type="hidden" name="re_id" value="<?= $eq['id'] ?>">
                                                <button type="submit" class="text-red-400 hover:text-red-600 text-xs font-medium px-2 py-1 rounded-lg hover:bg-red-50">Remove</button>
                                            </form>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    <!-- TASKS SECTION -->
    <div class="bg-white rounded-2xl shadow-sm p-6 border border-transparent">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-gray-800">Tasks</h2>
            <a href="<?= url('/pages/tasks.php') ?>" class="text-blue-600 hover:text-blue-700 text-sm font-semibold">
                Manage Tasks →
            </a>
        </div>
        <p class="text-gray-500 text-sm">Task assignments for this room are managed from the <a href="<?= url('/pages/tasks.php') ?>" class="text-blue-600 hover:underline">Tasks page</a>.</p>
    </div>
</main>

<script>
function switchEquipTab(tab) {
    const c = tab === 'catalog';
    document.getElementById('eq_form_catalog').classList.toggle('hidden', !c);
    document.getElementById('eq_form_new').classList.toggle('hidden', c);
    document.getElementById('eq_tab_catalog').className = 'flex-1 py-1.5 rounded-lg text-sm font-semibold transition ' + (c ? 'bg-white shadow-sm text-gray-800' : 'text-gray-500 hover:text-gray-700');
    document.getElementById('eq_tab_new').className     = 'flex-1 py-1.5 rounded-lg text-sm font-semibold transition ' + (!c ? 'bg-white shadow-sm text-gray-800' : 'text-gray-500 hover:text-gray-700');
}
function openEquipmentModal() {
    document.getElementById('equipmentModal').classList.remove('hidden');
    switchEquipTab('catalog');
}
function openEditEquip(eq) {
    document.getElementById('editEquipModal').classList.remove('hidden');
    document.getElementById('ee_name').textContent = eq.eq_name;
    document.getElementById('ee_re_id').value = eq.id;
    document.getElementById('ee_qty').value   = eq.quantity;
    document.getElementById('ee_notes').value = eq.notes || '';
}
// Close modals on backdrop click
['equipmentModal','editEquipModal'].forEach(id => {
    document.getElementById(id)?.addEventListener('click', function(e) {
        if (e.target === this) this.classList.add('hidden');
    });
});
</script>

<!-- EQUIPMENT MODAL -->
<div id="equipmentModal" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="p-6 pb-0">
            <h2 class="text-lg font-bold text-gray-800 mb-4">Add Equipment to Room</h2>
            <div class="flex gap-1 bg-gray-100 p-1 rounded-xl mb-5">
                <button type="button" id="eq_tab_catalog" onclick="switchEquipTab('catalog')" class="flex-1 py-1.5 rounded-lg text-sm font-semibold bg-white shadow-sm text-gray-800">From Catalog</button>
                <button type="button" id="eq_tab_new"     onclick="switchEquipTab('new')"     class="flex-1 py-1.5 rounded-lg text-sm font-semibold text-gray-500 hover:text-gray-700">Create New</button>
            </div>
        </div>
        <form method="POST" id="eq_form_catalog" class="px-6 pb-6">
            <input type="hidden" name="type" value="add_equipment">
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Equipment Item *</label>
                <select name="equipment_id" required class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                    <option value="">— Select an item —</option>
                    <?php $lastCat = '';
                    foreach ($allEquipment as $e) {
                        if ($e['category'] !== $lastCat) { if ($lastCat) echo '</optgroup>'; echo '<optgroup label="'.htmlspecialchars($eqCategories[$e['category']] ?? $e['category']).'">'; $lastCat = $e['category']; }
                        $already = in_array($e['id'], $assignedEqIds);
                        echo '<option value="'.$e['id'].'"'.($already?' disabled':'').'>'.htmlspecialchars($e['name']).($already?' (assigned)':'').'</option>';
                    } if ($lastCat) echo '</optgroup>'; ?>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Quantity *</label>
                <input type="number" name="quantity" value="1" min="1" required class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Notes (optional)</label>
                <input type="text" name="notes" placeholder="e.g. Stored in back closet" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="document.getElementById('equipmentModal').classList.add('hidden')" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2.5 rounded-xl text-sm">Cancel</button>
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-xl text-sm">Add to Room</button>
            </div>
        </form>
        <form method="POST" id="eq_form_new" class="px-6 pb-6 hidden">
            <input type="hidden" name="type" value="create_and_add_equipment">
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Item Name *</label>
                <input type="text" name="eq_name" required placeholder='e.g. Round Table (60")' class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Category *</label>
                <select name="eq_category" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                    <?php foreach ($eqCategories as $k => $lbl): ?><option value="<?= $k ?>"><?= $lbl ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Description (optional)</label>
                <input type="text" name="eq_description" placeholder="e.g. Seats 8 people" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Quantity in This Room *</label>
                <input type="number" name="quantity" value="1" min="1" required class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Notes (optional)</label>
                <input type="text" name="notes" placeholder="e.g. Stored in back closet" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <p class="text-xs text-blue-600 bg-blue-50 rounded-lg p-2 mb-4">This item will be added to the Equipment Catalog and assigned to this room.</p>
            <div class="flex gap-3">
                <button type="button" onclick="document.getElementById('equipmentModal').classList.add('hidden')" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2.5 rounded-xl text-sm">Cancel</button>
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-xl text-sm">Create & Add</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Equipment Modal -->
<div id="editEquipModal" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-md">
        <h2 class="text-lg font-bold text-gray-800 mb-1">Edit Equipment</h2>
        <p class="text-sm text-gray-500 mb-4" id="ee_name"></p>
        <form method="POST">
            <input type="hidden" name="type" value="update_equipment">
            <input type="hidden" name="re_id" id="ee_re_id">
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Quantity</label>
                <input type="number" name="quantity" id="ee_qty" min="1" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Notes</label>
                <input type="text" name="notes" id="ee_notes" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="document.getElementById('editEquipModal').classList.add('hidden')" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2.5 rounded-xl text-sm">Cancel</button>
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-xl text-sm">Save</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
