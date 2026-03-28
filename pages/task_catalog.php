<?php
$pageTitle = 'Task Catalog — Church Facility Manager';
require_once __DIR__ . '/../includes/nav.php';
require_once __DIR__ . '/../config/database.php';
requireRole(['admin', 'scheduler']);

$db      = getDB();
$success = $_GET['saved'] ?? null;

$categories = [
    'cleaning'    => ['label' => 'Cleaning',    'color' => 'bg-green-100 text-green-700'],
    'maintenance' => ['label' => 'Maintenance', 'color' => 'bg-orange-100 text-orange-700'],
    'setup'       => ['label' => 'Setup',       'color' => 'bg-purple-100 text-purple-700'],
];

$materialTypes = [
    'material' => ['label' => 'Material', 'color' => 'bg-red-100 text-red-600'],
    'supply'   => ['label' => 'Supply',   'color' => 'bg-blue-100 text-blue-600'],
    'tool'     => ['label' => 'Tool',     'color' => 'bg-yellow-100 text-yellow-700'],
];

$filterCat  = $_GET['cat']     ?? 'all';
$editTaskId = (int)($_GET['edit'] ?? 0);

// ── Handle POST ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';

    if ($type === 'add') {
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $cat  = $_POST['category'] ?? 'cleaning';
        if ($name && array_key_exists($cat, $categories)) {
            $db->prepare("INSERT INTO task_catalog (name, description, category) VALUES (?,?,?)")
               ->execute([$name, $desc, $cat]);
        }
        header('Location: /pages/task_catalog.php?saved=1');
        exit;
    }

    if ($type === 'edit') {
        $id   = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $cat  = $_POST['category'] ?? 'cleaning';
        if ($id && $name) {
            $db->prepare("UPDATE task_catalog SET name=?, description=?, category=? WHERE id=?")
               ->execute([$name, $desc, $cat, $id]);
        }
        header("Location: /pages/task_catalog.php?edit=$id&saved=1");
        exit;
    }

    if ($type === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $db->prepare("DELETE FROM task_catalog WHERE id=?")->execute([$id]);
        header('Location: /pages/task_catalog.php?saved=1');
        exit;
    }

    // Add material to task
    if ($type === 'add_material') {
        $taskId   = (int)($_POST['task_id']  ?? 0);
        $matName  = trim($_POST['mat_name']  ?? '');
        $matType  = $_POST['mat_type']       ?? 'supply';
        $matQty   = trim($_POST['mat_qty']   ?? '');
        if ($taskId && $matName) {
            $db->prepare("INSERT INTO task_materials (task_id, name, type, quantity) VALUES (?,?,?,?)")
               ->execute([$taskId, $matName, $matType, $matQty]);
            // Auto-save to catalog for future reuse
            try {
                $db->prepare("INSERT IGNORE INTO materials_catalog (name, type) VALUES (?,?)")
                   ->execute([$matName, $matType]);
            } catch (PDOException $e) {}
        }
        header("Location: /pages/task_catalog.php?edit=$taskId&saved=1");
        exit;
    }

    // Delete material
    if ($type === 'delete_material') {
        $matId  = (int)($_POST['mat_id']  ?? 0);
        $taskId = (int)($_POST['task_id'] ?? 0);
        $db->prepare("DELETE FROM task_materials WHERE id=?")->execute([$matId]);
        header("Location: /pages/task_catalog.php?edit=$taskId&saved=1");
        exit;
    }
}

// ── Fetch tasks ───────────────────────────────────────────────────────────────
$where = ($filterCat !== 'all' && array_key_exists($filterCat, $categories)) ? "WHERE t.category = '$filterCat'" : '';
$items = $db->query("SELECT t.*, COUNT(rt.id) AS room_count FROM task_catalog t LEFT JOIN room_tasks rt ON rt.task_id = t.id $where GROUP BY t.id ORDER BY t.category, t.name")->fetchAll();
$total = $db->query("SELECT COUNT(*) FROM task_catalog")->fetchColumn();

// If editing a task, fetch its details + materials
$editTask      = null;
$editMaterials = [];
if ($editTaskId) {
    $stmt = $db->prepare("SELECT * FROM task_catalog WHERE id=?");
    $stmt->execute([$editTaskId]);
    $editTask = $stmt->fetch();

    $stmt = $db->prepare("SELECT * FROM task_materials WHERE task_id=? ORDER BY type, name");
    $stmt->execute([$editTaskId]);
    $editMaterials = $stmt->fetchAll();
}

// Fetch materials catalog for autocomplete (grouped by type)
$matCatalog = ['material' => [], 'supply' => [], 'tool' => []];
try {
    $rows = $db->query("SELECT name, type FROM materials_catalog ORDER BY type, name")->fetchAll();
    foreach ($rows as $r) { $matCatalog[$r['type']][] = $r['name']; }
} catch (PDOException $e) { /* run migrate_materials_catalog.php */ }
?>

<main class="max-w-6xl mx-auto px-4 py-8">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Task Catalog</h1>
            <p class="text-gray-500 text-sm mt-0.5"><?= $total ?> tasks — assign them to rooms from each room's profile.</p>
        </div>
        <button onclick="document.getElementById('addModal').classList.remove('hidden')"
                class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-xl transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add Task
        </button>
    </div>

    <?php if ($success): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl p-3 mb-5 text-sm">✓ Saved successfully.</div>
    <?php endif; ?>

    <div class="flex gap-6">

        <!-- Task List -->
        <div class="flex-1">

            <!-- Category Filter -->
            <div class="flex flex-wrap gap-2 mb-5">
                <a href="/pages/task_catalog.php<?= $editTaskId ? "?edit=$editTaskId" : '' ?>"
                   class="px-4 py-1.5 rounded-full text-sm font-semibold transition <?= $filterCat === 'all' ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200' ?>">
                    All (<?= $total ?>)
                </a>
                <?php foreach ($categories as $key => $cat):
                    $count = $db->query("SELECT COUNT(*) FROM task_catalog WHERE category='$key'")->fetchColumn();
                ?>
                    <a href="/pages/task_catalog.php?cat=<?= $key ?><?= $editTaskId ? "&edit=$editTaskId" : '' ?>"
                       class="px-4 py-1.5 rounded-full text-sm font-semibold transition <?= $filterCat === $key ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200' ?>">
                        <?= $cat['label'] ?> (<?= $count ?>)
                    </a>
                <?php endforeach; ?>
            </div>

            <?php if (empty($items)): ?>
                <div class="bg-white rounded-2xl shadow-sm p-12 text-center text-gray-400">
                    <p class="font-medium">No tasks yet. Click "Add Task" to get started.</p>
                </div>
            <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($items as $item): ?>
                        <?php $isEditing = $editTaskId === (int)$item['id']; ?>
                        <div class="bg-white rounded-xl shadow-sm border-2 transition <?= $isEditing ? 'border-blue-400' : 'border-transparent hover:border-gray-200' ?>">
                            <div class="flex items-center justify-between p-4">
                                <div class="flex items-center gap-3 flex-1">
                                    <span class="<?= $categories[$item['category']]['color'] ?> text-xs font-semibold px-2 py-0.5 rounded-full flex-shrink-0">
                                        <?= $categories[$item['category']]['label'] ?>
                                    </span>
                                    <div>
                                        <span class="font-semibold text-gray-800"><?= htmlspecialchars($item['name']) ?></span>
                                        <?php if ($item['description']): ?>
                                            <span class="text-gray-400 text-xs ml-2"><?= htmlspecialchars($item['description']) ?></span>
                                        <?php endif; ?>
                                        <span class="text-blue-400 text-xs ml-2">
                                            <?= $item['room_count'] ?> room<?= $item['room_count'] != 1 ? 's' : '' ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <a href="/pages/task_catalog.php?edit=<?= $item['id'] ?><?= $filterCat !== 'all' ? "&cat=$filterCat" : '' ?>"
                                       class="text-blue-500 hover:bg-blue-50 text-xs font-semibold px-3 py-1.5 rounded-lg transition">
                                        <?= $isEditing ? 'Editing ✓' : 'Edit / Materials' ?>
                                    </a>
                                    <form method="POST" onsubmit="return confirm('Delete this task? It will be removed from all rooms.')">
                                        <input type="hidden" name="type" value="delete">
                                        <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                        <button type="submit" class="text-red-400 hover:bg-red-50 text-xs font-semibold px-2 py-1.5 rounded-lg transition">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Edit Panel (shown when editing a task) -->
        <?php if ($editTask): ?>
        <div class="w-80 flex-shrink-0">
            <div class="bg-white rounded-2xl shadow-sm p-5 sticky top-6">
                <h2 class="font-bold text-gray-800 mb-4">Edit Task</h2>

                <!-- Edit form -->
                <form method="POST" class="mb-6">
                    <input type="hidden" name="type" value="edit">
                    <input type="hidden" name="id" value="<?= $editTask['id'] ?>">
                    <div class="mb-3">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Task Name *</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($editTask['name']) ?>" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="mb-3">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Category</label>
                        <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                            <?php foreach ($categories as $key => $cat): ?>
                                <option value="<?= $key ?>" <?= $editTask['category'] === $key ? 'selected' : '' ?>><?= $cat['label'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Description</label>
                        <input type="text" name="description" value="<?= htmlspecialchars($editTask['description'] ?? '') ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded-xl text-sm transition">
                        Save Changes
                    </button>
                </form>

                <hr class="border-gray-100 mb-5">

                <!-- Materials / Supplies / Tools -->
                <h3 class="font-bold text-gray-700 mb-3">Materials, Supplies & Tools</h3>

                <?php if (empty($editMaterials)): ?>
                    <p class="text-gray-400 text-xs mb-4">None added yet.</p>
                <?php else: ?>
                    <div class="space-y-2 mb-4">
                        <?php foreach ($editMaterials as $m): ?>
                            <div class="flex items-center justify-between bg-gray-50 rounded-xl px-3 py-2 text-sm">
                                <div>
                                    <span class="<?= $materialTypes[$m['type']]['color'] ?> text-xs font-semibold px-1.5 py-0.5 rounded-md mr-1">
                                        <?= $materialTypes[$m['type']]['label'] ?>
                                    </span>
                                    <span class="text-gray-700 font-medium"><?= htmlspecialchars($m['name']) ?></span>
                                    <?php if ($m['quantity']): ?>
                                        <span class="text-gray-400 text-xs ml-1">(<?= htmlspecialchars($m['quantity']) ?>)</span>
                                    <?php endif; ?>
                                </div>
                                <form method="POST">
                                    <input type="hidden" name="type" value="delete_material">
                                    <input type="hidden" name="mat_id" value="<?= $m['id'] ?>">
                                    <input type="hidden" name="task_id" value="<?= $editTask['id'] ?>">
                                    <button type="submit" class="text-gray-300 hover:text-red-400 transition text-xs">✕</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Add material form -->
                <form method="POST" id="addMatForm">
                    <input type="hidden" name="type" value="add_material">
                    <input type="hidden" name="task_id" value="<?= $editTask['id'] ?>">
                    <!-- Type first so datalist can update -->
                    <div class="mb-2">
                        <select name="mat_type" id="mat_type_sel"
                                onchange="updateMatDatalist(this.value)"
                                class="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                            <?php foreach ($materialTypes as $key => $mt): ?>
                                <option value="<?= $key ?>"><?= $mt['label'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <input type="text" name="mat_name" id="mat_name_input" required
                               placeholder="Name — or pick from list"
                               list="mat_names_datalist"
                               autocomplete="off"
                               class="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <datalist id="mat_names_datalist"></datalist>
                    </div>
                    <div class="mb-3">
                        <input type="text" name="mat_qty" placeholder="Qty (e.g. 1 bottle)"
                               class="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <button type="submit" class="w-full bg-gray-800 hover:bg-gray-900 text-white font-semibold py-2 rounded-xl text-sm transition">
                        + Add Item
                    </button>
                </form>

                <script>
                const MAT_CATALOG = <?= json_encode($matCatalog) ?>;
                function updateMatDatalist(type) {
                    const dl = document.getElementById('mat_names_datalist');
                    dl.innerHTML = '';
                    (MAT_CATALOG[type] || []).forEach(name => {
                        const opt = document.createElement('option');
                        opt.value = name;
                        dl.appendChild(opt);
                    });
                }
                // Populate on page load with default type
                updateMatDatalist(document.getElementById('mat_type_sel').value);
                </script>

                <a href="/pages/task_catalog.php" class="mt-3 block text-center text-gray-400 hover:text-gray-600 text-xs">
                    ← Back to full list
                </a>
            </div>
        </div>
        <?php endif; ?>

    </div>
</main>

<!-- Add Modal -->
<div id="addModal" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-md">
        <h2 class="text-lg font-bold text-gray-800 mb-4">Add Task</h2>
        <form method="POST">
            <input type="hidden" name="type" value="add">
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Task Name *</label>
                <input type="text" name="name" required placeholder="e.g. Vacuum Floor"
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
                <input type="text" name="description" placeholder="e.g. Use commercial vacuum on all carpeted areas"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <p class="text-xs text-gray-400 mb-4">You can add materials, supplies, and tools after creating the task.</p>
            <div class="flex gap-3">
                <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')"
                        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2.5 rounded-xl text-sm transition">Cancel</button>
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-xl text-sm transition">Add Task</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
