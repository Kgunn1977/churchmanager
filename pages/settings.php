<?php
$pageTitle = 'Settings — Church Facility Manager';
require_once __DIR__ . '/../includes/nav.php';
require_once __DIR__ . '/../config/database.php';
if (!isAdmin()) { header('Location: ' . url('/dashboard.php')); exit; }
$db = getDB();
?>

<div class="max-w-4xl mx-auto px-4 py-8">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Settings</h1>
        <p class="text-gray-500 text-sm mt-1">System-wide configuration for the facility manager.</p>
    </div>

    <!-- ── Quick Links ────────────────────────────────────── -->
    <div class="grid grid-cols-4 gap-4 mb-6">
        <a href="<?= url('/pages/facilities.php') ?>" class="bg-white rounded-2xl shadow-sm p-5 border border-transparent hover:border-blue-200 transition group text-center">
            <svg class="w-7 h-7 mx-auto mb-2 text-blue-500 group-hover:text-blue-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            <span class="text-sm font-semibold text-gray-700 group-hover:text-blue-700 transition">Facility Map</span>
        </a>
        <a href="<?= url('/pages/users.php') ?>" class="bg-white rounded-2xl shadow-sm p-5 border border-transparent hover:border-blue-200 transition group text-center">
            <svg class="w-7 h-7 mx-auto mb-2 text-blue-500 group-hover:text-blue-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            <span class="text-sm font-semibold text-gray-700 group-hover:text-blue-700 transition">Users</span>
        </a>
        <a href="<?= url('/pages/app.php') ?>" class="bg-white rounded-2xl shadow-sm p-5 border border-transparent hover:border-blue-200 transition group text-center">
            <svg class="w-7 h-7 mx-auto mb-2 text-blue-500 group-hover:text-blue-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            <span class="text-sm font-semibold text-gray-700 group-hover:text-blue-700 transition">Mobile App</span>
        </a>
        <a href="<?= url('/pages/roadmap.php') ?>" class="bg-white rounded-2xl shadow-sm p-5 border border-transparent hover:border-blue-200 transition group text-center">
            <svg class="w-7 h-7 mx-auto mb-2 text-blue-500 group-hover:text-blue-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
            <span class="text-sm font-semibold text-gray-700 group-hover:text-blue-700 transition">Roadmap</span>
        </a>
    </div>

    <!-- ── Scheduling Settings ─────────────────────────────── -->
    <div class="bg-white rounded-2xl shadow-sm p-6 border border-transparent hover:border-blue-200 mb-6">
        <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Scheduling</h2>

        <div class="space-y-5">
            <!-- Auto-Generate Toggle -->
            <div class="flex items-center justify-between">
                <div>
                    <label class="block text-sm font-semibold text-gray-700">Auto-Generate Assignments</label>
                    <p class="text-xs text-gray-400 mt-0.5">Automatically create task assignments on every page load. Missing assignments are filled in up to the generation window.</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input id="set-auto-generate" type="checkbox" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:ring-2 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                </label>
            </div>

            <!-- Generation Window -->
            <div class="flex items-center justify-between">
                <div>
                    <label class="block text-sm font-semibold text-gray-700">Assignment Generation Window</label>
                    <p class="text-xs text-gray-400 mt-0.5">How many days ahead to generate task assignments from schedules.</p>
                </div>
                <div class="flex items-center gap-2">
                    <input id="set-gen-days" type="number" min="1" max="365" value="14"
                           class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-20 text-center">
                    <span class="text-sm text-gray-500">days</span>
                </div>
            </div>

            <!-- Scheduling Mode -->
            <div class="flex items-center justify-between">
                <div>
                    <label class="block text-sm font-semibold text-gray-700">Scheduling Mode</label>
                    <p class="text-xs text-gray-400 mt-0.5">
                        <strong>None:</strong> no time constraints shown.
                        <strong>Deadline:</strong> tasks must be completed by a deadline time.
                        <strong>Time Slot:</strong> tasks are assigned to specific time windows.
                    </p>
                </div>
                <select id="set-sched-mode" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="none">None</option>
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

            <!-- PWA Date Strip: Days Back -->
            <div class="flex items-center justify-between">
                <div>
                    <label class="block text-sm font-semibold text-gray-700">PWA Date Strip — Days Back</label>
                    <p class="text-xs text-gray-400 mt-0.5">How many past days to show in the mobile app date scroller.</p>
                </div>
                <div class="flex items-center gap-2">
                    <input id="set-date-strip-back" type="number" min="0" max="90" value="3"
                           class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-20 text-center">
                    <span class="text-sm text-gray-500">days</span>
                </div>
            </div>

            <!-- PWA Date Strip: Days Forward -->
            <div class="flex items-center justify-between">
                <div>
                    <label class="block text-sm font-semibold text-gray-700">PWA Date Strip — Days Forward</label>
                    <p class="text-xs text-gray-400 mt-0.5">How many future days to show in the mobile app date scroller.</p>
                </div>
                <div class="flex items-center gap-2">
                    <input id="set-date-strip-forward" type="number" min="1" max="90" value="10"
                           class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-20 text-center">
                    <span class="text-sm text-gray-500">days</span>
                </div>
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

    <!-- ── Deployment ─────────────────────────────────────── -->
    <div class="bg-white rounded-2xl shadow-sm p-6 border border-transparent hover:border-blue-200 mb-6">
        <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Deployment</h2>

        <div class="space-y-5">
            <!-- Git Pull -->
            <div class="flex items-center justify-between">
                <div>
                    <label class="block text-sm font-semibold text-gray-700">Pull Latest from GitHub</label>
                    <p class="text-xs text-gray-400 mt-0.5">Pulls the latest committed changes from the master branch.</p>
                </div>
                <button id="git-pull-btn" onclick="gitPull()" class="bg-gray-800 hover:bg-gray-900 text-white font-bold rounded-lg px-4 py-2 text-sm transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 16 16"><path d="M8 0c4.42 0 8 3.58 8 8a8.013 8.013 0 0 1-5.45 7.59c-.4.08-.55-.17-.55-.38 0-.27.01-1.13.01-2.2 0-.75-.25-1.23-.54-1.48 1.78-.2 3.65-.88 3.65-3.95 0-.88-.31-1.59-.82-2.15.08-.2.36-1.02-.08-2.12 0 0-.67-.22-2.2.82-.64-.18-1.32-.27-2-.27-.68 0-1.36.09-2 .27-1.53-1.03-2.2-.82-2.2-.82-.44 1.1-.16 1.92-.08 2.12-.51.56-.82 1.28-.82 2.15 0 3.06 1.86 3.75 3.64 3.95-.23.2-.44.55-.51 1.07-.46.21-1.61.55-2.33-.66-.15-.24-.6-.83-1.23-.82-.67.01-.27.38.01.53.34.19.73.9.82 1.13.16.45.68 1.31 2.69.94 0 .67.01 1.3.01 1.49 0 .21-.15.45-.55.38A7.995 7.995 0 0 1 0 8c0-4.42 3.58-8 8-8Z"/></svg>
                    Git Pull
                </button>
            </div>

            <div id="git-output" class="hidden">
                <pre class="bg-gray-900 text-green-400 rounded-lg p-4 text-xs font-mono overflow-x-auto max-h-48 whitespace-pre-wrap"></pre>
            </div>

            <hr class="border-gray-100">

            <!-- Git Push -->
            <div class="flex items-center justify-between">
                <div>
                    <label class="block text-sm font-semibold text-gray-700">Push to GitHub</label>
                    <p class="text-xs text-gray-400 mt-0.5">Stages all changes, commits, and pushes to the master branch.</p>
                </div>
                <button id="git-push-btn" onclick="gitPush()" class="bg-green-600 hover:bg-green-700 text-white font-bold rounded-lg px-4 py-2 text-sm transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 16 16"><path d="M8 0c4.42 0 8 3.58 8 8a8.013 8.013 0 0 1-5.45 7.59c-.4.08-.55-.17-.55-.38 0-.27.01-1.13.01-2.2 0-.75-.25-1.23-.54-1.48 1.78-.2 3.65-.88 3.65-3.95 0-.88-.31-1.59-.82-2.15.08-.2.36-1.02-.08-2.12 0 0-.67-.22-2.2.82-.64-.18-1.32-.27-2-.27-.68 0-1.36.09-2 .27-1.53-1.03-2.2-.82-2.2-.82-.44 1.1-.16 1.92-.08 2.12-.51.56-.82 1.28-.82 2.15 0 3.06 1.86 3.75 3.64 3.95-.23.2-.44.55-.51 1.07-.46.21-1.61.55-2.33-.66-.15-.24-.6-.83-1.23-.82-.67.01-.27.38.01.53.34.19.73.9.82 1.13.16.45.68 1.31 2.69.94 0 .67.01 1.3.01 1.49 0 .21-.15.45-.55.38A7.995 7.995 0 0 1 0 8c0-4.42 3.58-8 8-8Z"/></svg>
                    Git Push
                </button>
            </div>

            <hr class="border-gray-100">

            <!-- DB Export -->
            <div class="flex items-center justify-between">
                <div>
                    <label class="block text-sm font-semibold text-gray-700">Export Database</label>
                    <p class="text-xs text-gray-400 mt-0.5">Saves a snapshot of this database to <code class="bg-gray-100 px-1 rounded">data/db_snapshot.sql</code>. Push with push.bat.</p>
                </div>
                <button id="db-export-btn" onclick="dbExport()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg px-4 py-2 text-sm transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Export DB
                </button>
            </div>

            <!-- DB Import -->
            <div class="flex items-center justify-between">
                <div>
                    <label class="block text-sm font-semibold text-gray-700">Sync Database from Snapshot</label>
                    <p class="text-xs text-gray-400 mt-0.5">Replaces this database with the snapshot from <code class="bg-gray-100 px-1 rounded">data/db_snapshot.sql</code>. Git Pull first.</p>
                </div>
                <button id="db-import-btn" onclick="dbImport()" class="bg-orange-500 hover:bg-orange-600 text-white font-bold rounded-lg px-4 py-2 text-sm transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Sync DB
                </button>
            </div>

            <div id="db-output" class="hidden">
                <pre class="bg-gray-900 text-green-400 rounded-lg p-4 text-xs font-mono overflow-x-auto max-h-48 whitespace-pre-wrap"></pre>
            </div>
        </div>
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
    const r = await fetch(BASE_PATH + '/api/settings_api.php?action=get_all');
    const s = await r.json();
    if (s.schedule_generation_days) document.getElementById('set-gen-days').value = s.schedule_generation_days;
    if (s.scheduling_mode) document.getElementById('set-sched-mode').value = s.scheduling_mode;
    if (s.default_deadline_time) document.getElementById('set-default-deadline').value = s.default_deadline_time;
    document.getElementById('set-auto-generate').checked = s.auto_generate_assignments === '1';
    if (s.pwa_date_strip_back) document.getElementById('set-date-strip-back').value = s.pwa_date_strip_back;
    if (s.pwa_date_strip_forward) document.getElementById('set-date-strip-forward').value = s.pwa_date_strip_forward;
}

async function saveSchedulingSettings() {
    const settings = {
        schedule_generation_days: document.getElementById('set-gen-days').value,
        scheduling_mode: document.getElementById('set-sched-mode').value,
        default_deadline_time: document.getElementById('set-default-deadline').value,
        auto_generate_assignments: document.getElementById('set-auto-generate').checked ? '1' : '0',
        pwa_date_strip_back: document.getElementById('set-date-strip-back').value,
        pwa_date_strip_forward: document.getElementById('set-date-strip-forward').value,
    };
    const r = await fetch(BASE_PATH + '/api/settings_api.php', {
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
    const r = await fetch(BASE_PATH + '/api/settings_api.php?action=get_task_types');
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
    await fetch(BASE_PATH + '/api/settings_api.php', {
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

    const r = await fetch(BASE_PATH + '/api/settings_api.php', {
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
    const r = await fetch(BASE_PATH + '/api/settings_api.php', {
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
// DATABASE SYNC
// ═══════════════════════════════════════════════════════════
async function dbExport() {
    const btn = document.getElementById('db-export-btn');
    const box = document.getElementById('db-output');
    const pre = box.querySelector('pre');

    btn.disabled = true;
    btn.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg> Exporting...';

    try {
        const r = await fetch(BASE_PATH + '/api/settings_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ action: 'db_export' })
        });
        const result = await r.json();
        box.classList.remove('hidden');

        if (result.success) {
            pre.textContent = `✅ Database exported successfully!\n\nTables: ${result.tables}\nSize: ${result.size}\nFile: ${result.file}\n\nNext steps:\n1. Run push.bat (on your Windows machine)\n2. Click Git Pull on the live site\n3. Click Sync DB on the live site`;
            pre.classList.remove('text-red-400');
            pre.classList.add('text-green-400');
            showToast('Database exported');
        } else {
            pre.textContent = 'Error: ' + (result.error || 'Unknown error');
            pre.classList.remove('text-green-400');
            pre.classList.add('text-red-400');
        }
    } catch (e) {
        box.classList.remove('hidden');
        pre.textContent = 'Error: ' + e.message;
        pre.classList.add('text-red-400');
    }

    btn.disabled = false;
    btn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg> Export DB';
}

async function dbImport() {
    if (!confirm('This will REPLACE the entire database with the local snapshot. Are you sure?')) return;

    const btn = document.getElementById('db-import-btn');
    const box = document.getElementById('db-output');
    const pre = box.querySelector('pre');

    btn.disabled = true;
    btn.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg> Syncing...';

    try {
        const r = await fetch(BASE_PATH + '/api/settings_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ action: 'db_import' })
        });
        const result = await r.json();
        box.classList.remove('hidden');

        if (result.success) {
            pre.textContent = `✅ Database synced successfully!\n\nStatements executed: ${result.statements}\nSnapshot date: ${result.snapshot_date}`;
            pre.classList.remove('text-red-400');
            pre.classList.add('text-green-400');
            showToast('Database synced');
        } else {
            let msg = 'Error: ' + (result.error || 'Import had errors');
            if (result.errors && result.errors.length > 0) {
                msg += '\n\nStatements executed: ' + result.statements;
                msg += '\n\nErrors:\n' + result.errors.join('\n');
            }
            pre.textContent = msg;
            pre.classList.remove('text-green-400');
            pre.classList.add('text-red-400');
        }
    } catch (e) {
        box.classList.remove('hidden');
        pre.textContent = 'Error: ' + e.message;
        pre.classList.add('text-red-400');
    }

    btn.disabled = false;
    btn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg> Sync DB';
}

// ═══════════════════════════════════════════════════════════
// GIT PUSH
// ═══════════════════════════════════════════════════════════
async function gitPush() {
    if (!confirm('This will commit all changes and push to GitHub. Continue?')) return;

    const btn = document.getElementById('git-push-btn');
    const box = document.getElementById('git-output');
    const pre = box.querySelector('pre');

    btn.disabled = true;
    btn.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg> Pushing...';

    try {
        const r = await fetch(BASE_PATH + '/api/settings_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ action: 'git_push' })
        });
        const result = await r.json();
        box.classList.remove('hidden');
        pre.textContent = result.output || 'No output';

        if (result.success) {
            showToast('Push successful');
            pre.classList.remove('text-red-400');
            pre.classList.add('text-green-400');
        } else {
            showToast('Push failed — see output below');
            pre.classList.remove('text-green-400');
            pre.classList.add('text-red-400');
        }
    } catch (e) {
        box.classList.remove('hidden');
        pre.textContent = 'Error: ' + e.message;
        pre.classList.add('text-red-400');
    }

    btn.disabled = false;
    btn.innerHTML = '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 16 16"><path d="M8 0c4.42 0 8 3.58 8 8a8.013 8.013 0 0 1-5.45 7.59c-.4.08-.55-.17-.55-.38 0-.27.01-1.13.01-2.2 0-.75-.25-1.23-.54-1.48 1.78-.2 3.65-.88 3.65-3.95 0-.88-.31-1.59-.82-2.15.08-.2.36-1.02-.08-2.12 0 0-.67-.22-2.2.82-.64-.18-1.32-.27-2-.27-.68 0-1.36.09-2 .27-1.53-1.03-2.2-.82-2.2-.82-.44 1.1-.16 1.92-.08 2.12-.51.56-.82 1.28-.82 2.15 0 3.06 1.86 3.75 3.64 3.95-.23.2-.44.55-.51 1.07-.46.21-1.61.55-2.33-.66-.15-.24-.6-.83-1.23-.82-.67.01-.27.38.01.53.34.19.73.9.82 1.13.16.45.68 1.31 2.69.94 0 .67.01 1.3.01 1.49 0 .21-.15.45-.55.38A7.995 7.995 0 0 1 0 8c0-4.42 3.58-8 8-8Z"/></svg> Git Push';
}

// ═══════════════════════════════════════════════════════════
// GIT PULL
// ═══════════════════════════════════════════════════════════
async function gitPull() {
    const btn = document.getElementById('git-pull-btn');
    const box = document.getElementById('git-output');
    const pre = box.querySelector('pre');

    btn.disabled = true;
    btn.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg> Pulling...';

    try {
        const r = await fetch(BASE_PATH + '/api/settings_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ action: 'git_pull' })
        });
        const result = await r.json();
        box.classList.remove('hidden');
        pre.textContent = result.output || 'No output';

        if (result.success) {
            showToast('Pull successful');
            pre.classList.remove('text-red-400');
            pre.classList.add('text-green-400');
        } else {
            showToast('Pull failed — see output below');
            pre.classList.remove('text-green-400');
            pre.classList.add('text-red-400');
        }
    } catch (e) {
        box.classList.remove('hidden');
        pre.textContent = 'Error: ' + e.message;
        pre.classList.add('text-red-400');
    }

    btn.disabled = false;
    btn.innerHTML = '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 16 16"><path d="M8 0c4.42 0 8 3.58 8 8a8.013 8.013 0 0 1-5.45 7.59c-.4.08-.55-.17-.55-.38 0-.27.01-1.13.01-2.2 0-.75-.25-1.23-.54-1.48 1.78-.2 3.65-.88 3.65-3.95 0-.88-.31-1.59-.82-2.15.08-.2.36-1.02-.08-2.12 0 0-.67-.22-2.2.82-.64-.18-1.32-.27-2-.27-.68 0-1.36.09-2 .27-1.53-1.03-2.2-.82-2.2-.82-.44 1.1-.16 1.92-.08 2.12-.51.56-.82 1.28-.82 2.15 0 3.06 1.86 3.75 3.64 3.95-.23.2-.44.55-.51 1.07-.46.21-1.61.55-2.33-.66-.15-.24-.6-.83-1.23-.82-.67.01-.27.38.01.53.34.19.73.9.82 1.13.16.45.68 1.31 2.69.94 0 .67.01 1.3.01 1.49 0 .21-.15.45-.55.38A7.995 7.995 0 0 1 0 8c0-4.42 3.58-8 8-8Z"/></svg> Git Pull';
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
