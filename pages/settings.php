<?php
$pageTitle = 'Settings — Church Facility Manager';
require_once __DIR__ . '/../includes/nav.php';
require_once __DIR__ . '/../config/database.php';
if (!isAdmin()) { header('Location: /dashboard.php'); exit; }
$db = getDB();
?>

<div class="max-w-4xl mx-auto px-4 py-8">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Settings</h1>
        <p class="text-gray-500 text-sm mt-1">System-wide configuration for the facility manager.</p>
    </div>

    <!-- ── Scheduling Settings ─────────────────────────────── -->
    <div class="bg-white rounded-2xl shadow-sm p-6 border border-transparent hover:border-blue-200 mb-6">
        <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Scheduling</h2>

        <div class="space-y-5">
            <!-- Generation Window -->
            <div class="flex items-center justify-between">
                <div>
                    <label class="block text-sm font-semibold text-gray-700">Assignment Generation Window</label>
                    <p class="text-xs text-gray-400 mt-0.5">How many days ahead to generate task assignments from schedules.</p>
                </div>
                <div class="flex items-center gap-2">
                    <input id="set-gen-days" type="number" min="1" max="90" value="14"
                           class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-20 text-center">
                    <span class="text-sm text-gray-500">days</span>
                </div>
            </div>

            <!-- Scheduling Mode -->
            <div class="flex items-center justify-between">
                <div>
                    <label class="block text-sm font-semibold text-gray-700">Scheduling Mode</label>
                    <p class="text-xs text-gray-400 mt-0.5">
                        <strong>Deadline:</strong> tasks must be completed by a deadline time.
                        <strong>Time Slot:</strong> tasks are assigned to specific time windows.
                    </p>
                </div>
                <select id="set-sched-mode" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="deadline">Deadline</option>
                    <option value="timeslot">Time Slot</option>
                </select>
            </div>

            <!-- Default Deadline -->
            <div class="flex items-center justify-between">
                <div>
                    <label class="block text-sm font-semibold text-gray-700">Default Deadline Time</label>
                    <p class="text-xs text-gray-400 mt-0.5">Used when a schedule doesn't specify its own deadline.</p>
                </div>
                <input id="set-default-deadline" type="time" value="08:00"
                       class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>

            <div class="flex justify-end pt-2">
                <button onclick="saveSchedulingSettings()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg px-4 py-2 text-sm transition">
                    Save Scheduling Settings
                </button>
            </div>
        </div>
    </div>

    <!-- ── Task Types ──────────────────────────────────────── -->
    <div class="bg-white rounded-2xl shadow-sm p-6 border border-transparent hover:border-blue-200 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Task Types</h2>
            <button onclick="openTypeModal()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg px-3 py-1.5 text-sm transition flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Type
            </button>
        </div>
        <p class="text-xs text-gray-400 mb-3">Drag to reorder priority. Higher priority types are scheduled first.</p>
        <div id="type-list" class="space-y-1"></div>
        <div id="type-empty" class="text-center text-gray-400 text-sm py-6 hidden">No task types defined.</div>
    </div>

</div>

<!-- ── Task Type Modal ─────────────────────────────────────── -->
<div id="type-modal" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center hidden">
<div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6">
    <h2 id="type-modal-title" class="text-lg font-bold text-gray-800 mb-4">Add Task Type</h2>
    <input type="hidden" id="tf-id" value="0">
    <div class="mb-4">
        <label class="block text-xs font-bold text-gray-500 mb-1">Type Name</label>
        <input id="tf-name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="e.g. Janitorial">
    </div>
    <div class="flex items-center justify-between">
        <button id="type-delete-btn" onclick="deleteTaskType()" class="text-red-500 hover:text-red-700 text-sm hidden">Delete</button>
        <div class="flex gap-2 ml-auto">
            <button onclick="closeTypeModal()" class="border border-gray-300 hover:bg-gray-50 text-gray-600 rounded-lg px-4 py-2 text-sm transition">Cancel</button>
            <button onclick="saveTaskType()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg px-4 py-2 text-sm transition">Save</button>
        </div>
    </div>
</div>
</div>

<!-- ── Toast ────────────────────────────────────────────────── -->
<div id="toast" class="fixed bottom-6 right-6 bg-green-600 text-white px-4 py-2 rounded-lg shadow-lg text-sm font-medium transition-all translate-y-20 opacity-0 z-50"></div>

<script>
let taskTypes = [];

// ═══════════════════════════════════════════════════════════
// INIT
// ═══════════════════════════════════════════════════════════
loadSettings();
loadTaskTypes();

async function loadSettings() {
    const r = await fetch('/api/settings_api.php?action=get_all');
    const s = await r.json();
    if (s.schedule_generation_days) document.getElementById('set-gen-days').value = s.schedule_generation_days;
    if (s.scheduling_mode) document.getElementById('set-sched-mode').value = s.scheduling_mode;
    if (s.default_deadline_time) document.getElementById('set-default-deadline').value = s.default_deadline_time;
}

async function saveSchedulingSettings() {
    const settings = {
        schedule_generation_days: document.getElementById('set-gen-days').value,
        scheduling_mode: document.getElementById('set-sched-mode').value,
        default_deadline_time: document.getElementById('set-default-deadline').value,
    };
    const r = await fetch('/api/settings_api.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ action: 'bulk_update', settings })
    });
    const result = await r.json();
    if (result.error) { alert(result.error); return; }
    showToast('Settings saved');
}

// ═══════════════════════════════════════════════════════════
// TASK TYPES
// ═══════════════════════════════════════════════════════════
async function loadTaskTypes() {
    const r = await fetch('/api/settings_api.php?action=get_task_types');
    taskTypes = await r.json();
    renderTaskTypes();
}

function renderTaskTypes() {
    const list = document.getElementById('type-list');
    const empty = document.getElementById('type-empty');
    if (taskTypes.length === 0) { list.innerHTML = ''; empty.classList.remove('hidden'); return; }
    empty.classList.add('hidden');

    list.innerHTML = taskTypes.map((t, i) => `
        <div class="flex items-center gap-3 bg-gray-50 rounded-lg px-4 py-3 group" draggable="true"
             ondragstart="typeDragStart(event, ${i})" ondragover="typeDragOver(event)" ondrop="typeDrop(event, ${i})">
            <svg class="w-4 h-4 text-gray-300 cursor-grab group-hover:text-gray-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path d="M7 2a2 2 0 10.001 4.001A2 2 0 007 2zm0 6a2 2 0 10.001 4.001A2 2 0 007 8zm0 6a2 2 0 10.001 4.001A2 2 0 007 14zm6-8a2 2 0 10-.001-4.001A2 2 0 0013 6zm0 2a2 2 0 10.001 4.001A2 2 0 0013 8zm0 6a2 2 0 10.001 4.001A2 2 0 0013 14z"/>
            </svg>
            <span class="flex-1 font-medium text-gray-700 text-sm">${esc(t.name)}</span>
            <span class="text-xs text-gray-400">Priority ${i + 1}</span>
            <button onclick="openTypeModal(${t.id})" class="text-blue-500 hover:text-blue-700 text-sm font-medium">Edit</button>
        </div>
    `).join('');
}

let dragIdx = null;
function typeDragStart(e, i) { dragIdx = i; e.dataTransfer.effectAllowed = 'move'; }
function typeDragOver(e) { e.preventDefault(); }
async function typeDrop(e, targetIdx) {
    e.preventDefault();
    if (dragIdx === null || dragIdx === targetIdx) return;
    const item = taskTypes.splice(dragIdx, 1)[0];
    taskTypes.splice(targetIdx, 0, item);
    dragIdx = null;
    renderTaskTypes();

    // Save new order
    const orders = taskTypes.map((t, i) => ({ id: t.id, priority_order: i + 1 }));
    await fetch('/api/settings_api.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ action: 'reorder_task_types', orders })
    });
    showToast('Priority order saved');
}

function openTypeModal(id) {
    const t = id ? taskTypes.find(x => x.id === id) : null;
    document.getElementById('type-modal').classList.remove('hidden');
    document.getElementById('type-modal-title').textContent = t ? 'Edit Task Type' : 'Add Task Type';
    document.getElementById('type-delete-btn').classList.toggle('hidden', !t);
    document.getElementById('tf-id').value = t ? t.id : 0;
    document.getElementById('tf-name').value = t ? t.name : '';
}

function closeTypeModal() { document.getElementById('type-modal').classList.add('hidden'); }

async function saveTaskType() {
    const id = parseInt(document.getElementById('tf-id').value);
    const name = document.getElementById('tf-name').value.trim();
    if (!name) { alert('Name is required'); return; }

    const r = await fetch('/api/settings_api.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ action: 'save_task_type', id, name, priority_order: id ? undefined : taskTypes.length + 1 })
    });
    const result = await r.json();
    if (result.error) { alert(result.error); return; }
    closeTypeModal();
    loadTaskTypes();
    showToast(id ? 'Task type updated' : 'Task type added');
}

async function deleteTaskType() {
    if (!confirm('Delete this task type? Tasks using it must be reassigned first.')) return;
    const id = parseInt(document.getElementById('tf-id').value);
    const r = await fetch('/api/settings_api.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ action: 'delete_task_type', id })
    });
    const result = await r.json();
    if (result.error) { alert(result.error); return; }
    closeTypeModal();
    loadTaskTypes();
    showToast('Task type deleted');
}

// ═══════════════════════════════════════════════════════════
// TOAST
// ═══════════════════════════════════════════════════════════
function showToast(msg) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.classList.remove('translate-y-20', 'opacity-0');
    setTimeout(() => t.classList.add('translate-y-20', 'opacity-0'), 2500);
}
</script>
</body>
</html>
