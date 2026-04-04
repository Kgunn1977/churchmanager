<?php
$pageTitle = 'Task Completion Log — Church Facility Manager';
require_once __DIR__ . '/../includes/nav.php';
require_once __DIR__ . '/../config/database.php';
$db = getDB();

// Access control: admin and scheduler roles only
if (!isAdmin() && getCurrentUser()['role'] !== 'scheduler') {
    header('Location: ' . url('/dashboard.php'));
    exit;
}

// Load dropdown data
$workers = $db->query("SELECT id, name FROM users WHERE is_active = 1 ORDER BY name")->fetchAll();
$rooms = $db->query("
    SELECT rm.id, rm.name, b.name as building_name
    FROM rooms rm
    JOIN floors fl ON fl.id = rm.floor_id
    JOIN buildings b ON b.id = fl.building_id
    ORDER BY b.name, rm.name
")->fetchAll();
$buildings = $db->query("SELECT id, name FROM buildings ORDER BY name")->fetchAll();

// Default date range: today minus 7 days to today
$today = date('Y-m-d');
$sevenDaysAgo = date('Y-m-d', strtotime('-7 days'));
?>

<div class="max-w-6xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-6">Task Completion Log</h1>

    <!-- Filter Bar -->
    <div class="bg-white rounded-2xl shadow-sm p-6 border border-transparent hover:border-gray-200 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
            <!-- Date Range -->
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Start Date</label>
                <input type="date" id="filterStartDate" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?= htmlspecialchars($sevenDaysAgo) ?>">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">End Date</label>
                <input type="date" id="filterEndDate" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?= htmlspecialchars($today) ?>">
            </div>

            <!-- Worker Dropdown -->
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Worker</label>
                <select id="filterWorker" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Workers</option>
                    <?php foreach ($workers as $w): ?>
                        <option value="<?= (int)$w['id'] ?>"><?= htmlspecialchars($w['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Building Dropdown -->
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Building</label>
                <select id="filterBuilding" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Buildings</option>
                    <?php foreach ($buildings as $b): ?>
                        <option value="<?= (int)$b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Room Dropdown -->
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Room</label>
                <select id="filterRoom" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Rooms</option>
                    <?php foreach ($rooms as $r): ?>
                        <option value="<?= (int)$r['id'] ?>"><?= htmlspecialchars($r['building_name'] . ' - ' . $r['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Task Search -->
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Task Name</label>
                <input type="text" id="filterTaskSearch" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Search tasks...">
            </div>
        </div>

        <!-- Apply Filters Button -->
        <button id="applyFiltersBtn" class="bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg px-4 py-2 text-sm transition">
            Apply Filters
        </button>
    </div>

    <!-- Results Table -->
    <div class="bg-white rounded-2xl shadow-sm p-6 border border-transparent hover:border-gray-200">
        <div id="loadingSpinner" class="text-center py-8 text-gray-500">Loading...</div>
        <table id="resultsTable" class="w-full hidden">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="text-left text-xs font-bold text-gray-700 uppercase tracking-wider py-3 px-4">Date/Time</th>
                    <th class="text-left text-xs font-bold text-gray-700 uppercase tracking-wider py-3 px-4">Worker</th>
                    <th class="text-left text-xs font-bold text-gray-700 uppercase tracking-wider py-3 px-4">Task</th>
                    <th class="text-left text-xs font-bold text-gray-700 uppercase tracking-wider py-3 px-4">Group</th>
                    <th class="text-left text-xs font-bold text-gray-700 uppercase tracking-wider py-3 px-4">Room / Building</th>
                    <th class="text-center text-xs font-bold text-gray-700 uppercase tracking-wider py-3 px-4">Status</th>
                </tr>
            </thead>
            <tbody id="resultsBody">
            </tbody>
        </table>
        <div id="emptyState" class="text-center py-8 text-gray-500">No results found</div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load initial data
    loadTaskLog();

    // Apply filters on button click
    document.getElementById('applyFiltersBtn').addEventListener('click', loadTaskLog);

    // Enter key on search field
    document.getElementById('filterTaskSearch').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') loadTaskLog();
    });

    // Building filter change → update room options
    document.getElementById('filterBuilding').addEventListener('change', function() {
        const buildingId = parseInt(this.value) || null;
        const roomSelect = document.getElementById('filterRoom');
        const currentValue = roomSelect.value;
        roomSelect.innerHTML = '<option value="">All Rooms</option>';
        const allRooms = <?= json_encode($rooms) ?>;
        allRooms.forEach(room => {
            if (!buildingId || room.building_id === buildingId) {
                const opt = document.createElement('option');
                opt.value = room.id;
                opt.textContent = room.building_name + ' - ' + room.name;
                roomSelect.appendChild(opt);
            }
        });
        roomSelect.value = currentValue;
    });
});

function loadTaskLog() {
    const startDate = document.getElementById('filterStartDate').value;
    const endDate = document.getElementById('filterEndDate').value;
    const userId = document.getElementById('filterWorker').value || '';
    const roomId = document.getElementById('filterRoom').value || '';
    const buildingId = document.getElementById('filterBuilding').value || '';
    const taskSearch = document.getElementById('filterTaskSearch').value.trim();

    const params = new URLSearchParams({
        action: 'get_task_log',
        start_date: startDate,
        end_date: endDate,
        user_id: userId,
        room_id: roomId,
        building_id: buildingId,
        task_search: taskSearch
    });

    document.getElementById('loadingSpinner').style.display = 'block';
    document.getElementById('resultsTable').classList.add('hidden');
    document.getElementById('emptyState').style.display = 'none';

    fetch('/api/tasks_api.php?' + params)
        .then(r => r.json())
        .then(data => {
            document.getElementById('loadingSpinner').style.display = 'none';
            if (!data || data.length === 0) {
                document.getElementById('emptyState').style.display = 'block';
                return;
            }
            const tbody = document.getElementById('resultsBody');
            tbody.innerHTML = '';
            data.forEach(row => {
                const tr = document.createElement('tr');
                tr.className = 'border-b border-gray-100 hover:bg-gray-50 transition';
                const completedAt = new Date(row.completed_at);
                const dateStr = completedAt.toLocaleString('en-US', { dateStyle: 'short', timeStyle: 'short' });
                tr.innerHTML = `
                    <td class="py-3 px-4 text-sm text-gray-700">${dateStr}</td>
                    <td class="py-3 px-4 text-sm text-gray-700">${htmlEscape(row.worker_name || '-')}</td>
                    <td class="py-3 px-4 text-sm text-gray-700">${htmlEscape(row.task_name || '-')}</td>
                    <td class="py-3 px-4 text-sm text-gray-700">${htmlEscape(row.group_name || '-')}</td>
                    <td class="py-3 px-4 text-sm text-gray-700">${htmlEscape((row.room_name || '-') + ' / ' + (row.building_name || '-'))}</td>
                    <td class="py-3 px-4 text-center text-sm">
                        ${row.completed ? '<span class="text-green-600 text-lg">✓</span>' : '<span class="text-gray-300 text-lg">○</span>'}
                    </td>
                `;
                tbody.appendChild(tr);
            });
            document.getElementById('resultsTable').classList.remove('hidden');
        })
        .catch(err => {
            document.getElementById('loadingSpinner').style.display = 'none';
            document.getElementById('emptyState').style.display = 'block';
            document.getElementById('emptyState').textContent = 'Error loading data: ' + err.message;
        });
}

function htmlEscape(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}
</script>

</body>
</html>
