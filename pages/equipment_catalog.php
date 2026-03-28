<?php
$pageTitle = 'Equipment Catalog — Church Facility Manager';
require_once __DIR__ . '/../includes/nav.php';
require_once __DIR__ . '/../config/database.php';
requireRole(['admin', 'scheduler']);

$db      = getDB();
$error   = '';
$success = $_GET['saved'] ?? null;

$categories = [
    'furniture' => ['label' => 'Furniture',  'color' => 'bg-blue-100 text-blue-700'],
    'av_tech'   => ['label' => 'AV & Tech',  'color' => 'bg-purple-100 text-purple-700'],
    'kitchen'   => ['label' => 'Kitchen',    'color' => 'bg-yellow-100 text-yellow-700'],
    'other'     => ['label' => 'Other',      'color' => 'bg-gray-100 text-gray-600'],
];

$filterCat = $_GET['cat'] ?? 'all';

// ── Handle POST ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';

    if ($type === 'add') {
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $cat  = $_POST['category'] ?? 'other';
        if ($name && array_key_exists($cat, $categories)) {
            $db->prepare("INSERT INTO equipment_catalog (name, description, category) VALUES (?,?,?)")
               ->execute([$name, $desc, $cat]);
        }
        header('Location: /pages/equipment_catalog.php?saved=1');
        exit;
    }

    if ($type === 'edit') {
        $id   = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $cat  = $_POST['category'] ?? 'other';
        if ($id && $name) {
            $db->prepare("UPDATE equipment_catalog SET name=?, description=?, category=? WHERE id=?")
               ->execute([$name, $desc, $cat, $id]);
        }
        header('Location: /pages/equipment_catalog.php?saved=1');
        exit;
    }

    if ($type === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $db->prepare("DELETE FROM equipment_catalog WHERE id=?")->execute([$id]);
        header('Location: /pages/equipment_catalog.php?saved=1');
        exit;
    }
}

// ── Fetch ─────────────────────────────────────────────────────────────────────
$where  = ($filterCat !== 'all' && array_key_exists($filterCat, $categories)) ? "WHERE category = '$filterCat'" : '';
$items  = $db->query("SELECT e.*, COUNT(re.id) AS room_count FROM equipment_catalog e LEFT JOIN room_equipment re ON re.equipment_id = e.id $where GROUP BY e.id ORDER BY e.category, e.name")->fetchAll();
$total  = $db->query("SELECT COUNT(*) FROM equipment_catalog")->fetchColumn();
?>

<main class="max-w-5xl mx-auto px-4 py-8">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Equipment Catalog</h1>
            <p class="text-gray-500 text-sm mt-0.5"><?= $total ?> items in catalog — assign them to rooms from each room's profile.</p>
        </div>
        <button onclick="document.getElementById('addModal').classList.remove('hidden')"
                class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-xl transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add Item
        </button>
    </div>

    <?php if ($success): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl p-3 mb-5 text-sm">✓ Saved successfully.</div>
    <?php endif; ?>

    <!-- Category Filter -->
    <div class="flex flex-wrap gap-2 mb-6">
        <a href="/pages/equipment_catalog.php"
           class="px-4 py-1.5 rounded-full text-sm font-semibold transition <?= $filterCat === 'all' ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200' ?>">
            All (<?= $total ?>)
        </a>
        <?php foreach ($categories as $key => $cat):
            $count = $db->query("SELECT COUNT(*) FROM equipment_catalog WHERE category='$key'")->fetchColumn();
        ?>
            <a href="/pages/equipment_catalog.php?cat=<?= $key ?>"
               class="px-4 py-1.5 rounded-full text-sm font-semibold transition <?= $filterCat === $key ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200' ?>">
                <?= $cat['label'] ?> (<?= $count ?>)
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Items Grid -->
    <?php if (empty($items)): ?>
        <div class="bg-white rounded-2xl shadow-sm p-12 text-center text-gray-400">
            <p class="font-medium">No equipment in catalog yet.</p>
            <p class="text-sm mt-1">Click "Add Item" to build your equipment list.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($items as $item): ?>
                <div class="bg-white rounded-2xl shadow-sm p-4 border border-transparent hover:border-blue-200 hover:shadow-md transition">
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex-1">
                            <span class="<?= $categories[$item['category']]['color'] ?> text-xs font-semibold px-2 py-0.5 rounded-full">
                                <?= $categories[$item['category']]['label'] ?>
                            </span>
                            <h3 class="font-semibold text-gray-800 mt-2"><?= htmlspecialchars($item['name']) ?></h3>
                            <?php if ($item['description']): ?>
                                <p class="text-gray-400 text-xs mt-0.5"><?= htmlspecialchars($item['description']) ?></p>
                            <?php endif; ?>
                            <p class="text-blue-500 text-xs mt-1 font-medium">
                                Used in <?= $item['room_count'] ?> room<?= $item['room_count'] != 1 ? 's' : '' ?>
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-2 mt-3 pt-3 border-t border-gray-100">
                        <button onclick='openEditModal(<?= json_encode($item) ?>)'
                                class="flex-1 text-center text-blue-600 hover:bg-blue-50 text-xs font-semibold py-1.5 rounded-lg transition">
                            Edit
                        </button>
                        <form method="POST" onsubmit="return confirm('Delete this item? It will be removed from all rooms.')">
                            <input type="hidden" name="type" value="delete">
                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                            <button type="submit" class="text-red-400 hover:bg-red-50 text-xs font-semibold py-1.5 px-3 rounded-lg transition">
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<!-- Add Modal -->
<div id="addModal" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-md">
        <h2 class="text-lg font-bold text-gray-800 mb-4">Add Equipment</h2>
        <form method="POST">
            <input type="hidden" name="type" value="add">
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Name *</label>
                <input type="text" name="name" required placeholder="e.g. Round Table (60&quot;)"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Category *</label>
                <select name="category" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                    <?php foreach ($categories as $key => $cat): ?>
                        <option value="<?= $key ?>"><?= $cat['label'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Description (optional)</label>
                <input type="text" name="description" placeholder="e.g. Seats 8 people"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')"
                        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2.5 rounded-xl text-sm transition">Cancel</button>
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-xl text-sm transition">Add Item</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-md">
        <h2 class="text-lg font-bold text-gray-800 mb-4">Edit Equipment</h2>
        <form method="POST">
            <input type="hidden" name="type" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Name *</label>
                <input type="text" name="name" id="edit_name" required
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Category *</label>
                <select name="category" id="edit_category" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                    <?php foreach ($categories as $key => $cat): ?>
                        <option value="<?= $key ?>"><?= $cat['label'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Description (optional)</label>
                <input type="text" name="description" id="edit_description"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')"
                        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2.5 rounded-xl text-sm transition">Cancel</button>
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-xl text-sm transition">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(item) {
    document.getElementById('edit_id').value          = item.id;
    document.getElementById('edit_name').value        = item.name;
    document.getElementById('edit_description').value = item.description ?? '';
    document.getElementById('edit_category').value    = item.category;
    document.getElementById('editModal').classList.remove('hidden');
}
</script>
</body>
</html>
