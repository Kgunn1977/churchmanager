<?php
$pageTitle = 'Room Profile — Church Facility Manager';
require_once __DIR__ . '/../includes/nav.php';
require_once __DIR__ . '/../config/database.php';
requireLogin();

$db     = getDB();
$roomId = (int)($_GET['room_id'] ?? 0);
if (!$roomId) { header('Location: /pages/facilities.php'); exit; }

$stmt = $db->prepare("
    SELECT r.*, f.name AS floor_name, f.id AS floor_id, b.name AS building_name, b.id AS building_id
    FROM rooms r
    JOIN floors f ON f.id = r.floor_id
    JOIN buildings b ON b.id = f.building_id
    WHERE r.id = ?
");
$stmt->execute([$roomId]);
$room = $stmt->fetch();
if (!$room) { header('Location: /pages/facilities.php'); exit; }

$success = $_GET['saved'] ?? null;
$tab     = $_GET['tab']   ?? 'equipment';

$eqCategories = ['furniture' => 'Furniture', 'av_tech' => 'AV & Tech', 'kitchen' => 'Kitchen', 'other' => 'Other'];
$taskCats     = ['cleaning' => 'Cleaning', 'maintenance' => 'Maintenance', 'setup' => 'Setup'];
$frequencies  = [
    'daily'           => 'Daily',
    'weekly'          => 'Weekly',
    'monthly'         => 'Monthly',
    'yearly'          => 'Yearly',
    'after_every_use' => 'After Every Use',
    'as_needed'       => 'As Needed',
];
$freqColors = [
    'daily'           => 'bg-red-100 text-red-600',
    'weekly'          => 'bg-orange-100 text-orange-600',
    'monthly'         => 'bg-yellow-100 text-yellow-700',
    'yearly'          => 'bg-blue-100 text-blue-600',
    'after_every_use' => 'bg-purple-100 text-purple-600',
    'as_needed'       => 'bg-gray-100 text-gray-500',
];
$materialTypes = [
    'material' => ['label' => 'Material', 'color' => 'bg-red-100 text-red-600'],
    'supply'   => ['label' => 'Supply',   'color' => 'bg-blue-100 text-blue-600'],
    'tool'     => ['label' => 'Tool',     'color' => 'bg-yellow-100 text-yellow-700'],
];
$dayLabels   = ['mon'=>'Mon','tue'=>'Tue','wed'=>'Wed','thu'=>'Thu','fri'=>'Fri','sat'=>'Sat','sun'=>'Sun'];
$weekLabels  = ['1'=>'1st','2'=>'2nd','3'=>'3rd','4'=>'4th','last'=>'Last'];
$dowLabels   = ['sunday'=>'Sunday','monday'=>'Monday','tuesday'=>'Tuesday','wednesday'=>'Wednesday','thursday'=>'Thursday','friday'=>'Friday','saturday'=>'Saturday'];
$monthLabels = [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];

$canEdit = in_array($currentUser['role'], ['admin', 'scheduler']);

// ── Helpers ───────────────────────────────────────────────────────────────────
function buildScheduleConfig(string $freq, array $post): ?string {
    if ($freq === 'weekly') {
        $days = array_values(array_filter($post['sched_days'] ?? []));
        return $days ? json_encode(['days' => $days]) : null;
    }
    if ($freq === 'monthly') {
        return json_encode(['week' => $post['sched_week'] ?? '1', 'day' => $post['sched_day'] ?? 'sunday']);
    }
    if ($freq === 'yearly') {
        return json_encode(['month' => (int)($post['sched_month'] ?? 1), 'day' => (int)($post['sched_day_num'] ?? 1)]);
    }
    return null;
}

function formatScheduleDetail(string $freq, ?string $schedJson, array $dayLabels, array $weekLabels, array $dowLabels, array $monthLabels): ?string {
    if (!$schedJson) return null;
    $c = json_decode($schedJson, true);
    if (!$c) return null;
    if ($freq === 'weekly' && !empty($c['days'])) {
        return 'Every ' . implode(', ', array_map(fn($d) => $dayLabels[$d] ?? $d, $c['days']));
    }
    if ($freq === 'monthly' && isset($c['week'], $c['day'])) {
        return ($weekLabels[$c['week']] ?? $c['week']) . ' ' . ($dowLabels[$c['day']] ?? $c['day']) . ' of the month';
    }
    if ($freq === 'yearly' && isset($c['month'], $c['day'])) {
        $n = (int)$c['day'];
        $sfx = ($n >= 11 && $n <= 13) ? 'th' : match($n % 10) { 1=>'st', 2=>'nd', 3=>'rd', default=>'th' };
        return ($monthLabels[$c['month']] ?? 'Month '.$c['month']) . ' ' . $n . $sfx;
    }
    return null;
}

function scheduleFields(string $suffix): string {
    $days   = ['mon'=>'Mon','tue'=>'Tue','wed'=>'Wed','thu'=>'Thu','fri'=>'Fri','sat'=>'Sat','sun'=>'Sun'];
    $weeks  = ['1'=>'1st','2'=>'2nd','3'=>'3rd','4'=>'4th','last'=>'Last'];
    $dows   = ['sunday'=>'Sunday','monday'=>'Monday','tuesday'=>'Tuesday','wednesday'=>'Wednesday','thursday'=>'Thursday','friday'=>'Friday','saturday'=>'Saturday'];
    $months = [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];
    $idPfx  = ($suffix === 'edit') ? 'et_' : '';
    ob_start(); ?>
    <div id="sched_weekly_<?= $suffix ?>" class="hidden bg-blue-50 rounded-xl p-3">
        <p class="text-xs font-semibold text-gray-600 mb-2">Which days of the week?</p>
        <div class="flex flex-wrap gap-3">
            <?php foreach ($days as $val => $lbl): ?>
                <label class="flex items-center gap-1 text-sm cursor-pointer">
                    <input type="checkbox" name="sched_days[]" value="<?= $val ?>"
                           class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500">
                    <span class="text-gray-700 font-medium"><?= $lbl ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>
    <div id="sched_monthly_<?= $suffix ?>" class="hidden bg-blue-50 rounded-xl p-3">
        <p class="text-xs font-semibold text-gray-600 mb-2">Which occurrence each month?</p>
        <div class="flex items-center gap-2 flex-wrap">
            <select name="sched_week" id="<?= $idPfx ?>sched_week"
                    class="px-3 py-2 border border-gray-300 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                <?php foreach ($weeks as $k => $lbl): ?><option value="<?= $k ?>"><?= $lbl ?></option><?php endforeach; ?>
            </select>
            <select name="sched_day" id="<?= $idPfx ?>sched_day"
                    class="px-3 py-2 border border-gray-300 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                <?php foreach ($dows as $k => $lbl): ?><option value="<?= $k ?>"><?= $lbl ?></option><?php endforeach; ?>
            </select>
            <span class="text-sm text-gray-500">of the month</span>
        </div>
    </div>
    <div id="sched_yearly_<?= $suffix ?>" class="hidden bg-blue-50 rounded-xl p-3">
        <p class="text-xs font-semibold text-gray-600 mb-2">Which date each year?</p>
        <div class="flex items-center gap-2 flex-wrap">
            <select name="sched_month" id="<?= $idPfx ?>sched_month"
                    class="px-3 py-2 border border-gray-300 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                <?php foreach ($months as $k => $lbl): ?><option value="<?= $k ?>"><?= $lbl ?></option><?php endforeach; ?>
            </select>
            <input type="number" name="sched_day_num" id="<?= $idPfx ?>sched_day_num"
                   min="1" max="31" value="1" placeholder="Day"
                   class="w-20 px-3 py-2 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
    </div>
    <?php return ob_get_clean();
}

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
        header("Location: /pages/room_profile.php?room_id=$roomId&tab=equipment&saved=1"); exit;
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
        header("Location: /pages/room_profile.php?room_id=$roomId&tab=equipment&saved=1"); exit;
    }

    if ($type === 'update_equipment') {
        $reId    = (int)($_POST['re_id'] ?? 0);
        $qty     = max(1, (int)($_POST['quantity'] ?? 1));
        $movable = isset($_POST['is_movable']) ? 1 : 0;
        $note    = trim($_POST['notes'] ?? '');
        $db->prepare("UPDATE room_equipment SET quantity=?, is_movable=?, notes=? WHERE id=? AND room_id=?")->execute([$qty, $movable, $note, $reId, $roomId]);
        header("Location: /pages/room_profile.php?room_id=$roomId&tab=equipment&saved=1"); exit;
    }

    if ($type === 'remove_equipment') {
        $reId = (int)($_POST['re_id'] ?? 0);
        $db->prepare("DELETE FROM room_equipment WHERE id=? AND room_id=?")->execute([$reId, $roomId]);
        header("Location: /pages/room_profile.php?room_id=$roomId&tab=equipment&saved=1"); exit;
    }

    if ($type === 'add_task') {
        $taskId = (int)($_POST['task_id'] ?? 0);
        $freq   = $_POST['frequency'] ?? 'daily';
        $note   = trim($_POST['notes'] ?? '');
        $sched  = buildScheduleConfig($freq, $_POST);
        if ($taskId) {
            try {
                $db->prepare("INSERT INTO room_tasks (room_id, task_id, frequency, schedule_config, notes) VALUES (?,?,?,?,?)
                              ON DUPLICATE KEY UPDATE frequency=VALUES(frequency), schedule_config=VALUES(schedule_config), notes=VALUES(notes)")
                   ->execute([$roomId, $taskId, $freq, $sched, $note]);
            } catch (PDOException $e) {}
        }
        header("Location: /pages/room_profile.php?room_id=$roomId&tab=tasks&saved=1"); exit;
    }

    if ($type === 'create_and_add_task') {
        $name  = trim($_POST['task_name'] ?? '');
        $cat   = $_POST['task_category'] ?? 'cleaning';
        $desc  = trim($_POST['task_description'] ?? '');
        $freq  = $_POST['frequency'] ?? 'daily';
        $note  = trim($_POST['notes'] ?? '');
        $sched = buildScheduleConfig($freq, $_POST);
        if ($name) {
            $db->prepare("INSERT INTO task_catalog (name, description, category) VALUES (?,?,?)")->execute([$name, $desc, $cat]);
            $taskId = $db->lastInsertId();
            $db->prepare("INSERT INTO room_tasks (room_id, task_id, frequency, schedule_config, notes) VALUES (?,?,?,?,?)
                          ON DUPLICATE KEY UPDATE frequency=VALUES(frequency), schedule_config=VALUES(schedule_config), notes=VALUES(notes)")
               ->execute([$roomId, $taskId, $freq, $sched, $note]);
        }
        header("Location: /pages/room_profile.php?room_id=$roomId&tab=tasks&saved=1"); exit;
    }

    if ($type === 'update_task') {
        $rtId  = (int)($_POST['rt_id'] ?? 0);
        $freq  = $_POST['frequency'] ?? 'daily';
        $note  = trim($_POST['notes'] ?? '');
        $sched = buildScheduleConfig($freq, $_POST);
        $db->prepare("UPDATE room_tasks SET frequency=?, schedule_config=?, notes=? WHERE id=? AND room_id=?")
           ->execute([$freq, $sched, $note, $rtId, $roomId]);
        header("Location: /pages/room_profile.php?room_id=$roomId&tab=tasks&saved=1"); exit;
    }

    if ($type === 'remove_task') {
        $rtId = (int)($_POST['rt_id'] ?? 0);
        $db->prepare("DELETE FROM room_tasks WHERE id=? AND room_id=?")->execute([$rtId, $roomId]);
        header("Location: /pages/room_profile.php?room_id=$roomId&tab=tasks&saved=1"); exit;
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

$stmt = $db->prepare("
    SELECT rt.*, t.name AS task_name, t.category, t.description AS task_desc
    FROM room_tasks rt
    JOIN task_catalog t ON t.id = rt.task_id
    WHERE rt.room_id = ?
    ORDER BY t.category, t.name
");
$stmt->execute([$roomId]);
$assignedTasks   = $stmt->fetchAll();
$assignedTaskIds = array_column($assignedTasks, 'task_id');
$allTasks        = $db->query("SELECT * FROM task_catalog ORDER BY category, name")->fetchAll();

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
        <a href="/pages/facilities.php" class="hover:text-blue-600">Buildings</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <a href="/pages/facilities.php?building_id=<?= $room['building_id'] ?>" class="hover:text-blue-600"><?= htmlspecialchars($room['building_name']) ?></a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <a href="/pages/facilities.php?floor_id=<?= $room['floor_id'] ?>&building_id=<?= $room['building_id'] ?>" class="hover:text-blue-600"><?= htmlspecialchars($room['floor_name']) ?></a>
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
            <div class="bg-green-50 rounded-xl px-4 py-2">
                <div class="text-2xl font-bold text-green-700"><?= count($assignedTasks) ?></div>
                <div class="text-xs text-green-500">Tasks</div>
            </div>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl p-3 mb-5 text-sm">✓ Saved successfully.</div>
    <?php endif; ?>

    <div class="flex gap-1 bg-gray-100 p-1 rounded-xl mb-6 w-fit">
        <a href="?room_id=<?= $roomId ?>&tab=equipment"
           class="px-5 py-2 rounded-lg text-sm font-semibold transition <?= $tab === 'equipment' ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500 hover:text-gray-700' ?>">
            Equipment (<?= count($assignedEquipment) ?>)
        </a>
        <a href="?room_id=<?= $roomId ?>&tab=tasks"
           class="px-5 py-2 rounded-lg text-sm font-semibold transition <?= $tab === 'tasks' ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500 hover:text-gray-700' ?>">
            Tasks (<?= count($assignedTasks) ?>)
        </a>
    </div>

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

    <?php else: ?>
    <!-- TASKS TAB -->
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-bold text-gray-700">Tasks for This Room</h2>
            <?php if ($canEdit): ?>
                <button onclick="openTaskModal()" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-xl transition flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Assign Task
                </button>
            <?php endif; ?>
        </div>
        <?php if (empty($assignedTasks)): ?>
            <div class="bg-white rounded-2xl shadow-sm p-10 text-center text-gray-400">
                <p class="font-medium">No tasks assigned yet.</p>
                <?php if ($canEdit): ?><button onclick="openTaskModal()" class="mt-3 text-blue-600 text-sm hover:underline">+ Assign a task to this room</button><?php endif; ?>
            </div>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($assignedTasks as $t):
                    $mats   = $taskMaterials[$t['task_id']] ?? [];
                    $detail = formatScheduleDetail($t['frequency'], $t['schedule_config'] ?? null, $dayLabels, $weekLabels, $dowLabels, $monthLabels);
                ?>
                    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                        <div class="flex items-center justify-between px-5 py-4">
                            <div class="flex items-center gap-3 flex-1 min-w-0">
                                <span class="text-xs px-2 py-0.5 rounded-full flex-shrink-0 <?= $t['category']==='cleaning' ? 'bg-green-100 text-green-700' : ($t['category']==='maintenance' ? 'bg-orange-100 text-orange-700' : 'bg-purple-100 text-purple-700') ?>">
                                    <?= ucfirst($t['category']) ?>
                                </span>
                                <div class="min-w-0">
                                    <span class="font-semibold text-gray-800"><?= htmlspecialchars($t['task_name']) ?></span>
                                    <?php if ($t['task_desc']): ?><span class="text-gray-400 text-xs ml-2"><?= htmlspecialchars($t['task_desc']) ?></span><?php endif; ?>
                                    <?php if ($t['notes']): ?><div class="text-gray-400 text-xs mt-0.5 italic"><?= htmlspecialchars($t['notes']) ?></div><?php endif; ?>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 flex-shrink-0 ml-3">
                                <div class="text-right">
                                    <span class="text-xs px-2 py-0.5 rounded-full <?= $freqColors[$t['frequency']] ?? 'bg-gray-100 text-gray-500' ?>">
                                        <?= $frequencies[$t['frequency']] ?? $t['frequency'] ?>
                                    </span>
                                    <?php if ($detail): ?>
                                        <div class="text-xs text-gray-400 mt-0.5"><?= htmlspecialchars($detail) ?></div>
                                    <?php endif; ?>
                                </div>
                                <?php if ($canEdit): ?>
                                    <button onclick='openEditTask(<?= json_encode($t) ?>)' class="text-blue-500 hover:text-blue-700 text-xs font-medium px-2 py-1 rounded-lg hover:bg-blue-50">Edit</button>
                                    <form method="POST" onsubmit="return confirm('Remove this task from the room?')" class="inline">
                                        <input type="hidden" name="type" value="remove_task">
                                        <input type="hidden" name="rt_id" value="<?= $t['id'] ?>">
                                        <button type="submit" class="text-red-400 hover:text-red-600 text-xs font-medium px-2 py-1 rounded-lg hover:bg-red-50">Remove</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (!empty($mats)): ?>
                            <div class="border-t border-gray-100 px-5 py-2.5 bg-gray-50 flex flex-wrap gap-2">
                                <?php foreach ($mats as $m): ?>
                                    <span class="flex items-center gap-1 text-xs bg-white border border-gray-200 rounded-lg px-2 py-1">
                                        <span class="<?= $materialTypes[$m['type']]['color'] ?> font-semibold"><?= $materialTypes[$m['type']]['label'] ?>:</span>
                                        <span class="text-gray-700"><?= htmlspecialchars($m['name']) ?></span>
                                        <?php if ($m['quantity']): ?><span class="text-gray-400">(<?= htmlspecialchars($m['quantity']) ?>)</span><?php endif; ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</main>

<script>
function handleFreqChange(val, suffix) {
    ['weekly','monthly','yearly'].forEach(f => {
        const el = document.getElementById('sched_' + f + '_' + suffix);
        if (el) el.classList.add('hidden');
    });
    const target = document.getElementById('sched_' + val + '_' + suffix);
    if (target) target.classList.remove('hidden');
}
function switchEquipTab(tab) {
    const c = tab === 'catalog';
    document.getElementById('eq_form_catalog').classList.toggle('hidden', !c);
    document.getElementById('eq_form_new').classList.toggle('hidden', c);
    document.getElementById('eq_tab_catalog').className = 'flex-1 py-1.5 rounded-lg text-sm font-semibold transition ' + (c ? 'bg-white shadow-sm text-gray-800' : 'text-gray-500 hover:text-gray-700');
    document.getElementById('eq_tab_new').className     = 'flex-1 py-1.5 rounded-lg text-sm font-semibold transition ' + (!c ? 'bg-white shadow-sm text-gray-800' : 'text-gray-500 hover:text-gray-700');
}
function switchTaskTab(tab) {
    const c = tab === 'catalog';
    document.getElementById('task_form_catalog').classList.toggle('hidden', !c);
    document.getElementById('task_form_new').classList.toggle('hidden', c);
    document.getElementById('task_tab_catalog').className = 'flex-1 py-1.5 rounded-lg text-sm font-semibold transition ' + (c ? 'bg-white shadow-sm text-gray-800' : 'text-gray-500 hover:text-gray-700');
    document.getElementById('task_tab_new').className     = 'flex-1 py-1.5 rounded-lg text-sm font-semibold transition ' + (!c ? 'bg-white shadow-sm text-gray-800' : 'text-gray-500 hover:text-gray-700');
}
function openEquipmentModal() {
    document.getElementById('equipmentModal').classList.remove('hidden');
    switchEquipTab('catalog');
}
function openTaskModal() {
    document.getElementById('taskModal').classList.remove('hidden');
    switchTaskTab('catalog');
    handleFreqChange('daily', 'cat');
    handleFreqChange('daily', 'new');
    const c = document.getElementById('cat_freq');
    const n = document.getElementById('new_freq');
    if (c) c.value = 'daily';
    if (n) n.value = 'daily';
}
function openEditEquip(eq) {
    document.getElementById('editEquipModal').classList.remove('hidden');
    document.getElementById('ee_name').textContent = eq.eq_name;
    document.getElementById('ee_re_id').value = eq.id;
    document.getElementById('ee_qty').value   = eq.quantity;
    document.getElementById('ee_notes').value = eq.notes || '';
}
function openEditTask(t) {
    document.getElementById('editTaskModal').classList.remove('hidden');
    document.getElementById('et_task_name').textContent = t.task_name;
    document.getElementById('et_rt_id').value  = t.id;
    document.getElementById('et_notes').value  = t.notes  || '';
    document.getElementById('et_freq').value   = t.frequency;
    handleFreqChange(t.frequency, 'edit');
    // Clear weekly checkboxes
    document.querySelectorAll('#sched_weekly_edit input[type=checkbox]').forEach(cb => cb.checked = false);
    if (t.schedule_config) {
        let cfg = {};
        try { cfg = JSON.parse(t.schedule_config); } catch(e) {}
        if (t.frequency === 'weekly' && cfg.days) {
            cfg.days.forEach(d => {
                const cb = document.querySelector('#sched_weekly_edit input[value="' + d + '"]');
                if (cb) cb.checked = true;
            });
        } else if (t.frequency === 'monthly') {
            if (cfg.week) document.getElementById('et_sched_week').value = cfg.week;
            if (cfg.day)  document.getElementById('et_sched_day').value  = cfg.day;
        } else if (t.frequency === 'yearly') {
            if (cfg.month) document.getElementById('et_sched_month').value   = cfg.month;
            if (cfg.day)   document.getElementById('et_sched_day_num').value = cfg.day;
        }
    }
}
// Close modals on backdrop click
['equipmentModal','editEquipModal','taskModal','editTaskModal'].forEach(id => {
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

<!-- TASK MODAL -->
<div id="taskModal" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md max-h-[92vh] overflow-y-auto">
        <div class="p-6 pb-0">
            <h2 class="text-lg font-bold text-gray-800 mb-4">Assign Task to Room</h2>
            <div class="flex gap-1 bg-gray-100 p-1 rounded-xl mb-5">
                <button type="button" id="task_tab_catalog" onclick="switchTaskTab('catalog')" class="flex-1 py-1.5 rounded-lg text-sm font-semibold bg-white shadow-sm text-gray-800">From Catalog</button>
                <button type="button" id="task_tab_new"     onclick="switchTaskTab('new')"     class="flex-1 py-1.5 rounded-lg text-sm font-semibold text-gray-500 hover:text-gray-700">Create New</button>
            </div>
        </div>
        <!-- FROM CATALOG -->
        <form method="POST" id="task_form_catalog" class="px-6 pb-6">
            <input type="hidden" name="type" value="add_task">
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Task *</label>
                <select name="task_id" required class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                    <option value="">— Select a task —</option>
                    <?php $lastCat = '';
                    foreach ($allTasks as $t) {
                        if ($t['category'] !== $lastCat) { if ($lastCat) echo '</optgroup>'; echo '<optgroup label="'.ucfirst($t['category']).'">'; $lastCat = $t['category']; }
                        $already = in_array($t['id'], $assignedTaskIds);
                        echo '<option value="'.$t['id'].'"'.($already?' disabled':'').'>'.htmlspecialchars($t['name']).($already?' (assigned)':'').'</option>';
                    } if ($lastCat) echo '</optgroup>'; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Frequency *</label>
                <select name="frequency" id="cat_freq" onchange="handleFreqChange(this.value,'cat')"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                    <?php foreach ($frequencies as $k => $lbl): ?><option value="<?= $k ?>"><?= $lbl ?></option><?php endforeach; ?>
                </select>
            </div>
            <?= scheduleFields('cat') ?>
            <div class="mb-5 mt-3">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Notes (optional)</label>
                <input type="text" name="notes" placeholder="e.g. Use the industrial mop" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="document.getElementById('taskModal').classList.add('hidden')" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2.5 rounded-xl text-sm">Cancel</button>
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-xl text-sm">Assign Task</button>
            </div>
        </form>
        <!-- CREATE NEW -->
        <form method="POST" id="task_form_new" class="px-6 pb-6 hidden">
            <input type="hidden" name="type" value="create_and_add_task">
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Task Name *</label>
                <input type="text" name="task_name" required placeholder="e.g. Vacuum Carpet" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Category *</label>
                <select name="task_category" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                    <?php foreach ($taskCats as $k => $lbl): ?><option value="<?= $k ?>"><?= $lbl ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Description (optional)</label>
                <input type="text" name="task_description" placeholder="e.g. Focus on high-traffic areas" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-3">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Frequency *</label>
                <select name="frequency" id="new_freq" onchange="handleFreqChange(this.value,'new')"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                    <?php foreach ($frequencies as $k => $lbl): ?><option value="<?= $k ?>"><?= $lbl ?></option><?php endforeach; ?>
                </select>
            </div>
            <?= scheduleFields('new') ?>
            <div class="mb-5 mt-3">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Notes (optional)</label>
                <input type="text" name="notes" placeholder="e.g. Focus on corners" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <p class="text-xs text-blue-600 bg-blue-50 rounded-lg p-2 mb-4">This task will be added to the Task Catalog and assigned to this room.</p>
            <div class="flex gap-3">
                <button type="button" onclick="document.getElementById('taskModal').classList.add('hidden')" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2.5 rounded-xl text-sm">Cancel</button>
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-xl text-sm">Create & Assign</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Task Modal -->
<div id="editTaskModal" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-md max-h-[92vh] overflow-y-auto">
        <h2 class="text-lg font-bold text-gray-800 mb-1">Edit Task Schedule</h2>
        <p class="text-sm text-gray-500 mb-4" id="et_task_name"></p>
        <form method="POST">
            <input type="hidden" name="type" value="update_task">
            <input type="hidden" name="rt_id" id="et_rt_id">
            <div class="mb-3">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Frequency</label>
                <select name="frequency" id="et_freq" onchange="handleFreqChange(this.value,'edit')"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                    <?php foreach ($frequencies as $k => $lbl): ?><option value="<?= $k ?>"><?= $lbl ?></option><?php endforeach; ?>
                </select>
            </div>
            <?= scheduleFields('edit') ?>
            <div class="mb-5 mt-3">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Notes</label>
                <input type="text" name="notes" id="et_notes" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="document.getElementById('editTaskModal').classList.add('hidden')" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2.5 rounded-xl text-sm">Cancel</button>
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-xl text-sm">Save</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
