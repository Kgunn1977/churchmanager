<?php
$pageTitle = 'Materials, Supplies & Tools — Church Facility Manager';
require_once __DIR__ . '/../includes/nav.php';
require_once __DIR__ . '/../config/database.php';
requireLogin();

$db      = getDB();
$canEdit = in_array($currentUser['role'], ['admin', 'scheduler']);

$typeLabels = ['material' => 'Material', 'supply' => 'Supply', 'tool' => 'Tool'];
$typeColors = [
    'material' => 'bg-red-100 text-red-700',
    'supply'   => 'bg-blue-100 text-blue-700',
    'tool'     => 'bg-yellow-100 text-yellow-700',
];

$error = null;

// ── Handle POST ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canEdit) {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $type = $_POST['type'] ?? 'supply';
        if ($name && isset($typeLabels[$type])) {
            try {
                $db->prepare("INSERT INTO materials_catalog (name, type) VALUES (?,?)")->execute([$name, $type]);
            } catch (PDOException $e) {
                // Duplicate — silently skip
            }
        }
        header('Location: /pages/materials_catalog.php?saved=1'); exit;
    }

    if ($action === 'edit') {
        $id   = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $type = $_POST['type'] ?? 'supply';
        if ($id && $name && isset($typeLabels[$type])) {
            try {
                $db->prepare("UPDATE materials_catalog SET name=?, type=? WHERE id=?")->execute([$name, $type, $id]);
            } catch (PDOException $e) {}
        }
        header('Location: /pages/materials_catalog.php?saved=1'); exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $db->prepare("DELETE FROM materials_catalog WHERE id=?")->execute([$id]);
        }
        header('Location: /pages/materials_catalog.php?saved=1'); exit;
    }
}

// ── Fetch all items with usage count ─────────────────────────────────────────
$filterType = $_GET['type'] ?? 'all';
$sql = "
    SELECT mc.*,
           COUNT(DISTINCT tm.id) AS usage_count
    FROM materials_catalog mc
    LEFT JOIN task_materials tm
           ON tm.name COLLATE utf8mb4_general_ci = mc.name COLLATE utf8mb4_general_ci
          AND tm.type COLLATE utf8mb4_general_ci = mc.type COLLATE utf8mb4_general_ci
";
$params = [];
if ($filterType !== 'all' && isset($typeLabels[$filterType])) {
    $sql .= " WHERE mc.type = ?";
    $params[] = $filterType;
}
$sql .= " GROUP BY mc.id ORDER BY mc.type, mc.name";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

// Count by type for tab badges
$counts = $db->query("SELECT type, COUNT(*) AS n FROM materials_catalog GROUP BY type")->fetchAll(PDO::FETCH_KEY_PAIR);
$totalCount = array_sum($counts);
?>

<main class="max-w-4xl mx-auto px-4 py-8">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Materials, Supplies & Tools</h1>
            <p class="text-sm text-gray-500 mt-1">Reusable catalog for items used in cleaning and maintenance tasks.</p>
        </div>
        <?php if ($canEdit): ?>
            <button onclick="document.getElementById('addModal').classList.remove('hidden')"
                    class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-xl transition flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Item
            </button>
        <?php endif; ?>
    </div>

    <?php if (isset($_GET['saved'])): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl p-3 mb-5 text-sm">✓ Saved successfully.</div>
    <?php endif; ?>

    <!-- Filter Tabs -->
    <div class="flex gap-1 bg-gray-100 p-1 rounded-xl mb-6 w-fit">
        <?php
        $tabs = ['all' => 'All', 'material' => 'Materials', 'supply' => 'Supplies', 'tool' => 'Tools'];
        foreach ($tabs as $key => $label):
            $count = ($key === 'all') ? $totalCount : ($counts[$key] ?? 0);
            $active = $filterType === $key;
        ?>
            <a href="?type=<?= $key ?>"
               class="px-4 py-2 rounded-lg text-sm font-semibold transition <?= $active ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500 hover:text-gray-700' ?>">
                <?= $label ?>
                <span class="ml-1 text-xs <?= $active ? 'text-blue-600' : 'text-gray-400' ?>">(<?= $count ?>)</span>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($items)): ?>
        <div class="bg-white rounded-2xl shadow-sm p-10 text-center text-gray-400">
            <p class="text-lg font-medium mb-1">No items yet.</p>
            <p class="text-sm">Items are added here automatically when you type a new name while editing a task, or you can add them manually.</p>
            <?php if ($canEdit): ?>
                <button onclick="document.getElementById('addModal').classList.remove('hidden')"
                        class="mt-4 text-blue-600 text-sm font-medium hover:underline">+ Add your first item</button>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left px-5 py-3 font-semibold text-gray-600">Name</th>
                        <th class="text-left px-5 py-3 font-semibold text-gray-600">Type</th>
                        <th class="text-left px-5 py-3 font-semibold text-gray-600">Used in Tasks</th>
                        <?php if ($canEdit): ?><th class="px-5 py-3"></th><?php endif; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($items as $item): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-medium text-gray-800"><?= htmlspecialchars($item['name']) ?></td>
                            <td class="px-5 py-3">
                                <span class="text-xs px-2 py-0.5 rounded-full font-semibold <?= $typeColors[$item['type']] ?? 'bg-gray-100 text-gray-600' ?>">
                                    <?= $typeLabels[$item['type']] ?? $item['type'] ?>
                                </span>
                            </td>
                            <td class="px-5 py-3 text-gray-500">
                                <?php if ($item['usage_count'] > 0): ?>
                                    <span class="text-green-600 font-semibold"><?= $item['usage_count'] ?></span>
                                    <span class="text-gray-400 text-xs ml-1"><?= $item['usage_count'] === '1' ? 'task' : 'tasks' ?></span>
                                <?php else: ?>
                                    <span class="text-gray-300 text-xs">Not used</span>
                                <?php endif; ?>
                            </td>
                            <?php if ($canEdit): ?>
                                <td class="px-5 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button onclick='openEdit(<?= json_encode($item) ?>)'
                                                class="text-blue-500 hover:text-blue-700 text-xs font-medium px-2 py-1 rounded-lg hover:bg-blue-50">Edit</button>
                                        <form method="POST" onsubmit="return confirm('Delete this item? It will no longer appear as a suggestion, but existing task entries using this name will not be removed.')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                            <button type="submit" class="text-red-400 hover:text-red-600 text-xs font-medium px-2 py-1 rounded-lg hover:bg-red-50">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="text-xs text-gray-400 mt-3 text-center">
            New items are added to this catalog automatically when you type a name that doesn't exist yet while editing a task.
        </p>
    <?php endif; ?>
</main>

<script>
function openEdit(item) {
    document.getElementById('editModal').classList.remove('hidden');
    document.getElementById('edit_id').value   = item.id;
    document.getElementById('edit_name').value = item.name;
    document.getElementById('edit_type').value = item.type;
}
['addModal','editModal'].forEach(id => {
    document.getElementById(id)?.addEventListener('click', function(e) {
        if (e.target === this) this.classList.add('hidden');
    });
});
</script>

<!-- Add Modal -->
<div id="addModal" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-sm">
        <h2 class="text-lg font-bold text-gray-800 mb-4">Add Item to Catalog</h2>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Type *</label>
                <select name="type" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                    <option value="material">Material</option>
                    <option value="supply" selected>Supply</option>
                    <option value="tool">Tool</option>
                </select>
            </div>
            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Name *</label>
                <input type="text" name="name" required placeholder="e.g. Vacuum, Standard"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')"
                        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2.5 rounded-xl text-sm">Cancel</button>
                <button type="submit"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-xl text-sm">Add</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-sm">
        <h2 class="text-lg font-bold text-gray-800 mb-4">Edit Item</h2>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Type *</label>
                <select name="type" id="edit_type" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                    <option value="material">Material</option>
                    <option value="supply">Supply</option>
                    <option value="tool">Tool</option>
                </select>
            </div>
            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Name *</label>
                <input type="text" name="name" id="edit_name" required
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')"
                        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2.5 rounded-xl text-sm">Cancel</button>
                <button type="submit"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-xl text-sm">Save</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
