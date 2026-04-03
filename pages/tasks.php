<?php
$pageTitle = 'Tasks — Church Facility Manager';
require_once __DIR__ . '/../includes/nav.php';
require_once __DIR__ . '/../config/database.php';
$db = getDB();

$taskTypes  = $db->query("SELECT id, name FROM task_types ORDER BY priority_order, name")->fetchAll();
$workers    = $db->query("SELECT id, name FROM users WHERE is_active = 1 ORDER BY name")->fetchAll();
$buildings  = $db->query("SELECT id, name FROM buildings ORDER BY name")->fetchAll();

// Floor plan picker config
$fp_id        = 'tsk';
$fp_div_key   = 'fp_w_tsk';
$fp_default_w = 340;
$fp_buildings = $buildings;
?>

<style>
/* ── App shell (full-bleed like Reservations) ──────────── */
#task-app {
    display: flex;
    height: calc(100vh - 56px);
    overflow: hidden;
    font-family: inherit;
}
#task-main {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
#task-main-header {
    padding: 16px 20px 0;
    flex-shrink: 0;
}
#task-main-list {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    padding: 0 20px 20px;
}

/* ── Room filter banner (always visible) ───────────────── */
#room-filter-banner {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    margin-bottom: 12px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 12px;
    color: #64748b;
    font-weight: 600;
    transition: background .15s, border-color .15s, color .15s;
}
#room-filter-banner.active {
    background: #eff6ff;
    border-color: #bfdbfe;
    color: #1e40af;
}
#room-filter-banner .filter-icon { flex-shrink: 0; }
#room-filter-banner .clear-btn {
    margin-left: auto;
    background: none;
    border: none;
    color: #3b82f6;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: underline;
    display: none;
}
#room-filter-banner.active .clear-btn { display: inline; }
#room-filter-banner .clear-btn:hover { color: #1d4ed8; }

/* ── Editor right pane (persistent, like Reservations) ─── */
#task-editor {
    width: 420px;
    flex-shrink: 0;
    background: #f8fafc;
    border-left: 1px solid #e2e8f0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
#editor-new-row {
    padding: 10px 12px;
    border-bottom: 1px solid #e2e8f0;
    background: #fff;
    flex-shrink: 0;
}
#editor-new-btn {
    width: 100%;
    background: #2563eb;
    color: white;
    border: none;
    border-radius: 8px;
    padding: 8px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition: background .12s;
    font-family: ui-sans-serif, system-ui, sans-serif;
}
#editor-new-btn:hover { background: #1d4ed8; }
#editor-edit-btn:hover { background: #eff6ff !important; border-color: #93c5fd !important; color: #1e40af !important; }
#editor-group-btn:hover { background: #dbeafe !important; border-color: #93c5fd !important; }
#editor-ungroup-btn:hover { background: #fee2e2 !important; border-color: #fca5a5 !important; }
#editor-empty {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #9ca3af;
    padding: 32px 24px;
    text-align: center;
}
#editor-empty svg { margin-bottom: 12px; opacity: .5; }
#editor-form-wrap {
    display: none;
    flex-direction: column;
    flex: 1;
    min-height: 0;
    overflow: hidden;
}
#editor-form-wrap.open { display: flex; }
#editor-hdr {
    padding: 13px 16px;
    border-bottom: 1px solid #e2e8f0;
    background: #fff;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}
#editor-title { font-size: 15px; font-weight: 700; color: #111827; margin: 0; }
#editor-close {
    background: none;
    border: none;
    cursor: pointer;
    color: #9ca3af;
    padding: 3px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: color .12s;
}
#editor-close:hover { color: #374151; }
#editor-body { flex: 1; overflow-y: auto; padding: 16px; }
#editor-actions {
    padding: 12px 16px;
    border-top: 1px solid #e2e8f0;
    background: #fff;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.tab-btn { padding:8px 20px; font-size:13px; font-weight:600; border:none; background:none; color:#6b7280; cursor:pointer; border-bottom:2px solid transparent; transition:all .15s; }
.tab-btn:hover { color:#111827; }
.tab-btn.active { color:#2563eb; border-bottom-color:#2563eb; }
.tab-panel { display:none; }
.tab-panel.active { display:block; }
.card { background:#fff; border-radius:12px; border:1px solid #e5e7eb; padding:16px; margin-bottom:10px; cursor:pointer; transition:border-color .12s, box-shadow .12s; }
.card-compact { padding:10px 14px; margin-bottom:6px; }
.card:hover { border-color:#bfdbfe; box-shadow:0 2px 8px rgba(59,130,246,.08); }
.card.selected { border-color:#3b82f6; box-shadow:0 0 0 2px rgba(59,130,246,.15); }
.badge { display:inline-block; font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px; }
.badge-blue { background:#dbeafe; color:#1e40af; }
.badge-green { background:#dcfce7; color:#166534; }
.badge-amber { background:#fef3c7; color:#92400e; }
.chip { display:inline-flex; align-items:center; gap:3px; background:#f3f4f6; color:#374151; font-size:11px; font-weight:600; border-radius:20px; padding:3px 10px; margin:2px; }
.chip .x { cursor:pointer; font-size:13px; color:#9ca3af; margin-left:2px; }
.chip .x:hover { color:#ef4444; }
.ef-label { display:block; font-size:12px; font-weight:600; color:#374151; margin-bottom:4px; }
.ef-label span { font-weight:400; color:#9ca3af; }
.ef-input { width:100%; border:1px solid #d1d5db; border-radius:8px; padding:8px 11px; font-size:13px; outline:none; font-family:ui-sans-serif,system-ui,sans-serif; color:#111827; background:white; box-sizing:border-box; }
.ef-input:focus { border-color:#3b82f6; box-shadow:0 0 0 3px rgba(59,130,246,.12); }
.combo-dropdown { position:absolute; z-index:300; background:#fff; border:1px solid #e2e8f0; border-radius:8px; box-shadow:0 8px 24px rgba(0,0,0,.12); max-height:180px; overflow-y:auto; width:100%; top:calc(100% + 2px); left:0; }
.combo-opt { padding:7px 12px; font-size:12px; cursor:pointer; transition:background .1s; }
.combo-opt:hover { background:#f0f9ff; }
.combo-opt.create { color:#2563eb; font-weight:600; }
.combo-opt .sub { color:#9ca3af; font-size:11px; }
</style>

<div id="task-app">

    <!-- ── Room Picker (left pane) ─────────────────────────── -->
    <?php require_once __DIR__ . '/../includes/floor_plan_picker.php'; ?>

    <!-- ── Main content area ───────────────────────────────── -->
    <div id="task-main">
        <div id="task-main-header">
            <!-- Header -->
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-xl font-bold text-gray-800">Task Library</h1>
            </div>

            <!-- Room filter banner (always visible) -->
            <div id="room-filter-banner">
                <svg class="filter-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                <span id="room-filter-text">Filtering by: All</span>
                <button class="clear-btn" onclick="roomPicker.clearSelection()">Clear</button>
            </div>

            <!-- Tabs -->
            <div class="border-b border-gray-200 mb-4">
                <button class="tab-btn active" data-tab="tasks" onclick="switchTab('tasks')">Tasks</button>
                <button class="tab-btn" data-tab="groups" onclick="switchTab('groups')">Task Groups</button>
            </div>

            <!-- Filter bar -->
            <div class="flex gap-3 mb-4">
                <select id="filter-type" class="ef-input" style="width:180px;" onchange="loadList()">
                    <option value="">All Types</option>
                    <?php foreach ($taskTypes as $tt): ?>
                    <option value="<?= $tt['id'] ?>"><?= htmlspecialchars($tt['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="filter-worker" class="ef-input" style="width:180px;" onchange="loadList()">
                    <option value="">All Workers</option>
                    <?php foreach ($workers as $w): ?>
                    <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" id="filter-search" class="ef-input" style="flex:1;" placeholder="Search..." oninput="debounceLoad()">
            </div>
        </div>

        <div id="task-main-list">
            <!-- Task list -->
            <div id="panel-tasks" class="tab-panel active">
                <div id="task-list"></div>
                <p id="task-empty" class="text-center text-gray-400 text-sm py-12 hidden">No tasks found. Create one to get started.</p>
            </div>

            <!-- Group list -->
            <div id="panel-groups" class="tab-panel">
                <div id="group-list"></div>
                <p id="group-empty" class="text-center text-gray-400 text-sm py-12 hidden">No task groups found. Create one to get started.</p>
            </div>
        </div>
    </div>

    <!-- ── Editor (persistent right pane) ──────────────────── -->
    <div id="task-editor">
        <!-- New button row -->
        <div id="editor-new-row">
            <button id="editor-new-btn" onclick="openEditor()">+ New Task</button>
        </div>

        <!-- Selection actions (shown when cards are selected but editor is not open) -->
        <div id="editor-edit-row" style="display:none; padding:10px 12px; border-bottom:1px solid #e2e8f0; background:#fff;">
            <button id="editor-edit-btn" onclick="openEditorForSelected()" style="width:100%; background:#f8fafc; border:1px solid #d1d5db; border-radius:8px; padding:8px; font-size:13px; font-weight:600; cursor:pointer; transition:all .12s; color:#374151; font-family:ui-sans-serif,system-ui,sans-serif;">
                <svg style="display:inline; vertical-align:-2px; margin-right:4px;" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Edit Task(s)
            </button>
            <div id="editor-action-btns" style="display:flex; gap:6px; margin-top:6px;">
                <button id="editor-group-btn" onclick="groupSelected()" style="display:none; flex:1; background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:7px; font-size:12px; font-weight:600; cursor:pointer; transition:all .12s; color:#1e40af; font-family:ui-sans-serif,system-ui,sans-serif;">
                    <svg style="display:inline; vertical-align:-2px; margin-right:3px;" width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    Group
                </button>
                <button id="editor-ungroup-btn" onclick="ungroupSelected()" style="display:none; flex:1; background:#fef2f2; border:1px solid #fecaca; border-radius:8px; padding:7px; font-size:12px; font-weight:600; cursor:pointer; transition:all .12s; color:#991b1b; font-family:ui-sans-serif,system-ui,sans-serif;">
                    <svg style="display:inline; vertical-align:-2px; margin-right:3px;" width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    Ungroup
                </button>
            </div>
            <div id="editor-selection-info" style="display:flex; align-items:center; justify-content:space-between; margin-top:8px; font-size:12px; color:#6b7280;">
                <span id="editor-selection-count">1 selected</span>
                <button onclick="deselectAllCards()" style="background:none; border:none; color:#3b82f6; font-size:12px; font-weight:600; cursor:pointer; text-decoration:underline; padding:0;">Deselect All</button>
            </div>
        </div>

        <!-- Selection detail preview (tasks in group, rooms, etc.) -->
        <div id="editor-selection-detail" style="display:none; padding:12px; overflow-y:auto; flex:1;"></div>

        <!-- Empty state -->
        <div id="editor-empty">
            <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            <div style="font-size:13px;">Select a task or group to edit,<br>or create a new one.</div>
        </div>

        <!-- Form wrapper -->
        <div id="editor-form-wrap">
            <div id="editor-hdr">
                <h2 id="editor-title">New Task</h2>
                <button id="editor-close" onclick="closeEditor()">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div id="editor-body">
                <form id="editor-form" onsubmit="saveItem(event)">
                    <input type="hidden" id="e-id">
                    <input type="hidden" id="e-mode" value="task">

                    <!-- Name -->
                    <div style="margin-bottom:14px;">
                        <label class="ef-label">Name</label>
                        <input type="text" id="e-name" required class="ef-input" placeholder="e.g. Clean Toilet, Tidy Kitchen...">
                    </div>

                    <!-- Description -->
                    <div style="margin-bottom:14px;">
                        <label class="ef-label">Description <span>(optional)</span></label>
                        <textarea id="e-desc" class="ef-input" rows="2" style="resize:none;" placeholder="Brief description..."></textarea>
                    </div>

                    <!-- Type -->
                    <div style="margin-bottom:14px;">
                        <label class="ef-label">Task Type</label>
                        <select id="e-type" required class="ef-input" style="cursor:pointer;">
                            <option value="">— select type —</option>
                            <?php foreach ($taskTypes as $tt): ?>
                            <option value="<?= $tt['id'] ?>"><?= htmlspecialchars($tt['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Estimated time -->
                    <div style="margin-bottom:14px;">
                        <label class="ef-label">Estimated Time <span>(minutes)</span></label>
                        <input type="number" id="e-est-min" required class="ef-input" style="width:120px;" min="1" value="5">
                    </div>

                    <!-- Reusable (task mode only) -->
                    <div id="reusable-section" style="margin-bottom:14px;">
                        <label class="ef-label">Reusable</label>
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;color:#374151;">
                            <input type="checkbox" id="e-reusable" checked style="width:18px;height:18px;accent-color:#2563eb;cursor:pointer;">
                            Show in library &amp; group builder by default
                        </label>
                    </div>

                    <!-- Parent Group (group mode only) -->
                    <div id="parent-group-section" style="display:none; margin-bottom:14px;">
                        <label class="ef-label">Parent Group <span>(optional — nest inside another group)</span></label>
                        <select id="e-parent-group" class="ef-input" style="cursor:pointer;">
                            <option value="">— None (top-level) —</option>
                        </select>
                    </div>

                    <!-- Preferred Workers -->
                    <div style="margin-bottom:14px;">
                        <label class="ef-label">Preferred Workers <span>(optional)</span></label>
                        <div id="e-workers-chips" class="mb-1"></div>
                        <div style="position:relative;">
                            <input type="text" id="e-workers-input" class="ef-input" placeholder="Search workers..." autocomplete="off">
                            <div id="e-workers-dd" class="combo-dropdown hidden"></div>
                        </div>
                    </div>

                    <!-- Rooms (synced from floor plan picker) -->
                    <div id="rooms-section">
                        <div style="margin-bottom:14px;">
                            <label class="ef-label">Assigned Rooms <span>(click rooms in the floor plan to add/remove)</span></label>
                            <div id="e-rooms-chips" class="mb-1"></div>
                            <p id="e-rooms-empty" class="text-xs text-gray-400" style="margin:0;">No rooms selected</p>
                        </div>
                    </div>

                    <!-- Resources (task mode only) -->
                    <div id="resource-section">
                        <div style="margin-bottom:14px;">
                            <label class="ef-label">Tools</label>
                            <div id="e-tools-chips" class="mb-1"></div>
                            <div style="position:relative;">
                                <input type="text" id="e-tools-input" class="ef-input" placeholder="Search or add tool..." autocomplete="off">
                                <div id="e-tools-dd" class="combo-dropdown hidden"></div>
                            </div>
                        </div>
                        <div style="margin-bottom:14px;">
                            <label class="ef-label">Supplies</label>
                            <div id="e-supplies-chips" class="mb-1"></div>
                            <div style="position:relative;">
                                <input type="text" id="e-supplies-input" class="ef-input" placeholder="Search or add supply..." autocomplete="off">
                                <div id="e-supplies-dd" class="combo-dropdown hidden"></div>
                            </div>
                        </div>
                        <div style="margin-bottom:14px;">
                            <label class="ef-label">Materials</label>
                            <div id="e-materials-chips" class="mb-1"></div>
                            <div style="position:relative;">
                                <input type="text" id="e-materials-input" class="ef-input" placeholder="Search or add material..." autocomplete="off">
                                <div id="e-materials-dd" class="combo-dropdown hidden"></div>
                            </div>
                        </div>
                        <div style="margin-bottom:14px;">
                            <label class="ef-label">Equipment</label>
                            <div id="e-equipment-chips" class="mb-1"></div>
                            <div style="position:relative;">
                                <input type="text" id="e-equipment-input" class="ef-input" placeholder="Search or add equipment..." autocomplete="off">
                                <div id="e-equipment-dd" class="combo-dropdown hidden"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Tasks (group mode only) -->
                    <div id="group-tasks-section" style="display:none;">
                        <div style="margin-bottom:14px;">
                            <label class="ef-label">Tasks in this Group</label>
                            <div id="e-group-tasks-chips" class="mb-1"></div>
                            <div style="position:relative;">
                                <input type="text" id="e-group-tasks-input" class="ef-input" placeholder="Search tasks to add..." autocomplete="off">
                                <div id="e-group-tasks-dd" class="combo-dropdown hidden"></div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Actions (pinned to bottom) -->
            <div id="editor-actions">
                <button type="button" id="e-del-btn" onclick="deleteItem()" style="display:none;" class="text-red-500 hover:text-red-700 text-sm font-medium">Delete</button>
                <div class="flex gap-2 ml-auto">
                    <button type="button" onclick="closeEditor()" class="border border-gray-300 hover:bg-gray-50 text-gray-600 rounded-lg px-4 py-2 text-sm transition">Cancel</button>
                    <button type="button" onclick="document.getElementById('editor-form').requestSubmit()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg px-5 py-2 text-sm transition">Save</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// ═══════════════════════════════════════════════════════════
// STATE
// ═══════════════════════════════════════════════════════════
let currentTab = 'tasks';
let editingId = null;
let editingMode = null;
let selectedCards = [];      // [{id, mode, name}, ...] multi-select
let multiEditIds  = [];      // task IDs being batch-edited
let touchedFields = {};      // tracks which fields the user changed during multi-edit
const _MULTI_ = '_MULTI_';  // sentinel value for "multiple different values"
let resourceState = { tools: [], supplies: [], materials: [], equipment: [] };
let groupTasksState = [];
let workersState = [];
let roomsState = [];
let _loadedTasks = [];   // cache of last-loaded task list (for room lookup when grouping)
let _loadedGroups = [];  // cache of last-loaded group list (for detail preview)
let _pendingChildGroupIds = [];  // group IDs to set as children after saving a new parent group
let loadTimer = null;
let selectedRoomIds = []; // room IDs from the floor plan picker

const TYPE_COLORS = {};
const TYPES = <?= json_encode($taskTypes) ?>;
const COLOR_LIST = ['blue','green','amber','purple','pink','orange'];
TYPES.forEach((t,i) => { TYPE_COLORS[t.id] = COLOR_LIST[i % COLOR_LIST.length]; });

function badgeClass(typeId) {
    const c = TYPE_COLORS[typeId] || 'blue';
    return 'badge badge-' + c;
}

// ═══════════════════════════════════════════════════════════
// FLOOR PLAN PICKER
// ═══════════════════════════════════════════════════════════
const roomPicker = new FloorPlanPicker({
    paneId      : 'tsk-fp-pane',
    dividerId   : 'tsk-fp-divider',
    dividerKey  : 'fp_w_tsk',
    defaultWidth: 340,
    linkedGroups: [],
    onChange     : function(rooms) {
        selectedRoomIds = Object.keys(rooms).map(Number);
        updateRoomFilterBanner();

        // If editor is open, sync picker selection → editor roomsState
        if (editorIsOpen()) {
            roomsState = Object.values(rooms).map(r => ({
                id: Number(r.id),
                name: r.name,
                detail: (r.building || '') + ' / ' + (r.floor || '')
            }));
            touchedFields['rooms'] = true;
            renderRoomChips();
        } else {
            loadList();
        }
    }
});

function updateRoomFilterBanner() {
    const banner = document.getElementById('room-filter-banner');
    const text   = document.getElementById('room-filter-text');
    if (selectedRoomIds.length > 0 && !editorIsOpen()) {
        // Filtering mode: picker is being used to filter the list
        banner.classList.add('active');
        const sel = roomPicker.getSelection();
        const names = Object.values(sel).map(r => r.name);
        if (names.length <= 3) {
            text.textContent = 'Filtering by: ' + names.join(', ');
        } else {
            text.textContent = 'Filtering by ' + names.length + ' rooms';
        }
    } else if (selectedRoomIds.length > 0 && editorIsOpen()) {
        // Editor mode: picker is assigning rooms, not filtering
        banner.classList.remove('active');
        text.textContent = 'Filtering by: All';
    } else {
        banner.classList.remove('active');
        text.textContent = 'Filtering by: All';
    }
}

// ═══════════════════════════════════════════════════════════
// TABS
// ═══════════════════════════════════════════════════════════
function switchTab(tab) {
    currentTab = tab;
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.toggle('active', b.dataset.tab === tab));
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.toggle('active', p.id === 'panel-' + tab));
    const newBtn = document.getElementById('editor-new-btn');
    if (tab === 'tasks') {
        newBtn.textContent = '+ New Task';
        newBtn.style.display = '';
    } else {
        newBtn.style.display = 'none';
    }
    closeEditor();
}

// ═══════════════════════════════════════════════════════════
// LIST LOADING
// ═══════════════════════════════════════════════════════════
function debounceLoad() { clearTimeout(loadTimer); loadTimer = setTimeout(loadList, 250); }

function loadList() {
    const typeId   = document.getElementById('filter-type').value;
    const workerId = document.getElementById('filter-worker').value;
    const q        = document.getElementById('filter-search').value.trim();
    // Only apply room filtering when editor is CLOSED — when open, the picker
    // is in "room assignment" mode, not "filter" mode
    const hasRoomFilter = !editorIsOpen() && selectedRoomIds.length > 0;

    if (currentTab === 'tasks') {
        loadTasks(typeId, workerId, q, hasRoomFilter);
    } else {
        loadGroups(typeId, workerId, q, hasRoomFilter);
    }
}

function loadTasks(typeId, workerId, q, hasRoomFilter) {
    let promise;
    if (hasRoomFilter) {
        const p = { room_ids: selectedRoomIds.join(',') };
        if (workerId) p.worker_id = workerId;
        promise = api('get_tasks_by_rooms', p).then(tasks => {
            if (typeId) tasks = tasks.filter(t => t.task_type_id == typeId);
            if (q)      tasks = tasks.filter(t => t.name.toLowerCase().includes(q.toLowerCase()));
            return tasks;
        });
    } else {
        const params = {};
        if (typeId)   params.task_type_id = typeId;
        if (workerId) params.worker_id = workerId;
        if (q)        params.q = q;
        promise = api('get_tasks', params);
    }

    promise.then(tasks => {
        _loadedTasks = tasks;  // cache for grouping room lookup
        const list  = document.getElementById('task-list');
        const empty = document.getElementById('task-empty');
        list.innerHTML = '';
        empty.classList.toggle('hidden', tasks.length > 0);
        if (tasks.length === 0) {
            empty.textContent = hasRoomFilter
                ? 'No tasks assigned to the selected rooms.'
                : 'No tasks found. Create one to get started.';
        }
        tasks.forEach(t => {
            const selClass = selectedCards.some(c => c.mode === 'task' && c.id == t.id) ? ' selected' : '';
            const rooms   = (t.rooms||[]).map(r => esc(r.name)).join(', ');
            const workers = (t.workers||[]).map(w => esc(w.name)).join(', ');
            const meta = [
                `<span class="${badgeClass(t.task_type_id)}">${esc(t.type_name)}</span>`,
                `<span class="text-xs text-gray-400">${t.estimated_minutes} min</span>`,
                rooms   ? `<span class="text-xs text-gray-500">${rooms}</span>` : '',
                workers ? `<span class="text-xs text-indigo-500">${workers}</span>` : ''
            ].filter(Boolean).join('<span class="text-gray-300 mx-1">·</span>');
            list.innerHTML += `
                <div class="card card-compact${selClass}" data-id="${t.id}" data-mode="task" data-name="${esc(t.name)}" onclick="selectCard('task', ${t.id}, this, event)">
                    <div class="font-semibold text-gray-800 text-sm">${esc(t.name)}</div>
                    <div class="flex items-center flex-wrap gap-1 mt-1">${meta}</div>
                </div>`;
        });
    });
}

function loadGroups(typeId, workerId, q, hasRoomFilter) {
    let promise;
    if (hasRoomFilter) {
        const p = { room_ids: selectedRoomIds.join(',') };
        if (workerId) p.worker_id = workerId;
        promise = api('get_groups_by_rooms', p).then(groups => {
            if (typeId) groups = groups.filter(g => g.task_type_id == typeId);
            if (q)      groups = groups.filter(g => g.name.toLowerCase().includes(q.toLowerCase()));
            return groups;
        });
    } else {
        const params = {};
        if (typeId)   params.task_type_id = typeId;
        if (workerId) params.worker_id = workerId;
        if (q)        params.q = q;
        promise = api('get_task_groups', params);
    }

    promise.then(groups => {
        _loadedGroups = groups;  // cache for detail preview
        const list  = document.getElementById('group-list');
        const empty = document.getElementById('group-empty');
        list.innerHTML = '';

        // Build hierarchy: separate top-level from children
        const topLevel = groups.filter(g => !g.parent_id);
        const byParent = {};
        groups.forEach(g => {
            if (g.parent_id) {
                if (!byParent[g.parent_id]) byParent[g.parent_id] = [];
                byParent[g.parent_id].push(g);
            }
        });

        // When searching/filtering, show flat list so results aren't hidden
        const showFlat = q || hasRoomFilter;
        const displayGroups = showFlat ? groups : topLevel;

        empty.classList.toggle('hidden', displayGroups.length > 0);
        if (displayGroups.length === 0) {
            empty.textContent = hasRoomFilter
                ? 'No task groups assigned to the selected rooms.'
                : 'No task groups found. Create one to get started.';
        }

        function renderGroupCard(g, depth) {
            const indent = depth * 24;
            const selClass = selectedCards.some(c => c.mode === 'group' && c.id == g.id) ? ' selected' : '';
            const rooms   = (g.rooms||[]).map(r => esc(r.name)).join(', ');
            const workers = (g.workers||[]).map(w => esc(w.name)).join(', ');
            const childCount = g.child_group_count || 0;
            const meta = [
                `<span class="${badgeClass(g.task_type_id)}">${esc(g.type_name)}</span>`,
                `<span class="text-xs text-gray-400">${g.estimated_minutes} min</span>`,
                `<span class="text-xs text-gray-400">${g.task_count} task${g.task_count!=1?'s':''}</span>`,
                childCount > 0 ? `<span class="text-xs text-purple-500">${childCount} sub-group${childCount!=1?'s':''}</span>` : '',
                rooms   ? `<span class="text-xs text-gray-500">${rooms}</span>` : '',
                workers ? `<span class="text-xs text-indigo-500">${workers}</span>` : ''
            ].filter(Boolean).join('<span class="text-gray-300 mx-1">·</span>');

            const depthIcon = depth > 0 ? `<span style="color:#9ca3af;font-size:11px;margin-right:4px;">${'└'.padStart(depth,'  ')}</span>` : '';

            list.innerHTML += `
                <div class="card card-compact${selClass}" data-id="${g.id}" data-mode="group" data-name="${esc(g.name)}" onclick="selectCard('group', ${g.id}, this, event)" style="margin-left:${indent}px;">
                    <div class="font-semibold text-gray-800 text-sm">${depthIcon}${esc(g.name)}</div>
                    <div class="flex items-center flex-wrap gap-1 mt-1">${meta}</div>
                </div>`;

            // Render children (non-flat mode only)
            if (!showFlat && byParent[g.id]) {
                byParent[g.id].forEach(child => renderGroupCard(child, depth + 1));
            }
        }

        displayGroups.forEach(g => renderGroupCard(g, 0));
    });
}

// ═══════════════════════════════════════════════════════════
// EDITOR (persistent right pane)
// ═══════════════════════════════════════════════════════════
let _editorOpen = false;
function editorIsOpen() { return _editorOpen; }

function openEditor(mode = null, id = null) {
    mode = mode || currentTab.replace(/s$/, '');
    editingMode = mode;
    editingId = id;
    _editorOpen = true;

    document.getElementById('e-mode').value = mode;
    document.getElementById('e-id').value = id || '';
    document.getElementById('e-name').value = '';
    document.getElementById('e-desc').value = '';
    document.getElementById('e-type').value = '';
    document.getElementById('e-est-min').value = mode === 'task' ? 5 : 15;
    document.getElementById('e-del-btn').style.display = id ? '' : 'none';

    // Show/hide mode-specific sections
    document.getElementById('resource-section').style.display = mode === 'task' ? '' : 'none';
    document.getElementById('group-tasks-section').style.display = mode === 'group' ? '' : 'none';
    document.getElementById('reusable-section').style.display = mode === 'task' ? '' : 'none';
    document.getElementById('parent-group-section').style.display = mode === 'group' ? '' : 'none';
    document.getElementById('e-reusable').checked = true;

    // Load parent group options if group mode
    if (mode === 'group') { loadParentGroupOptions(id); }

    // Reset all state
    resourceState = { tools: [], supplies: [], materials: [], equipment: [] };
    groupTasksState = [];
    workersState = [];
    roomsState = [];
    renderResourceChips('tools');
    renderResourceChips('supplies');
    renderResourceChips('materials');
    renderResourceChips('equipment');
    renderGroupTaskChips();
    renderWorkerChips();
    renderRoomChips();

    // Clear picker selection before loading (so it doesn't carry over from filter)
    roomPicker.clearSelection();

    if (id) {
        document.getElementById('editor-title').textContent = mode === 'task' ? 'Edit Task' : 'Edit Task Group';
        const apiAction = mode === 'task' ? 'get_task' : 'get_task_group';
        api(apiAction, { id }).then(data => {
            if (data.error) { alert(data.error); return; }
            document.getElementById('e-name').value = data.name;
            document.getElementById('e-desc').value = data.description || '';
            document.getElementById('e-type').value = data.task_type_id;
            document.getElementById('e-est-min').value = data.estimated_minutes;

            // Workers
            workersState = (data.preferred_workers || []).map(w => ({id: w.id, name: w.name}));
            renderWorkerChips();

            // Rooms — pre-select in picker and sync to editor state
            const roomIds = (data.rooms || []).map(r => Number(r.id));
            if (roomIds.length) {
                roomPicker.selectRooms(roomIds);
                // selectRooms doesn't fire onChange, so sync manually
                const sel = roomPicker.getSelection();
                roomsState = Object.values(sel).map(r => ({
                    id: Number(r.id),
                    name: r.name,
                    detail: (r.building || '') + ' / ' + (r.floor || '')
                }));
                selectedRoomIds = Object.keys(sel).map(Number);
                updateRoomFilterBanner();
                renderRoomChips();
            }

            if (mode === 'task') {
                document.getElementById('e-reusable').checked = data.reusable == 1;
                resourceState.tools      = (data.tools || []).map(r => ({id: r.id, name: r.name}));
                resourceState.supplies   = (data.supplies || []).map(r => ({id: r.id, name: r.name}));
                resourceState.materials  = (data.materials || []).map(r => ({id: r.id, name: r.name}));
                resourceState.equipment  = (data.equipment || []).map(r => ({id: r.id, name: r.name}));
                renderResourceChips('tools');
                renderResourceChips('supplies');
                renderResourceChips('materials');
                renderResourceChips('equipment');
            } else {
                groupTasksState = (data.tasks || []).map(t => ({id: t.id, name: t.name}));
                renderGroupTaskChips();
                // Set parent group
                document.getElementById('e-parent-group').value = data.parent_id || '';
            }
        });
    } else {
        document.getElementById('editor-title').textContent = mode === 'task' ? 'New Task' : 'New Task Group';
    }

    // Show the form, hide empty/edit-row/detail states
    document.getElementById('editor-empty').style.display = 'none';
    document.getElementById('editor-edit-row').style.display = 'none';
    document.getElementById('editor-selection-detail').style.display = 'none';
    document.getElementById('editor-form-wrap').classList.add('open');

    // Highlight selected card
    highlightCard();
}

function closeEditor() {
    editingId = null;
    editingMode = null;
    multiEditIds = [];
    touchedFields = {};
    selectedCards = [];
    _editorOpen = false;
    _pendingChildGroupIds = [];
    document.getElementById('e-name').required = true;
    document.getElementById('e-name').placeholder = 'e.g. Clean Toilet, Tidy Kitchen...';
    document.getElementById('e-desc').placeholder = 'Brief description...';
    document.getElementById('e-est-min').placeholder = '';
    // Remove the _MULTI_ option from type dropdown if it exists
    const multiOpt = document.getElementById('e-type').querySelector('option[value="_MULTI_"]');
    if (multiOpt) multiOpt.remove();
    document.getElementById('editor-form-wrap').classList.remove('open');
    document.getElementById('editor-edit-row').style.display = 'none';
    document.getElementById('editor-selection-detail').style.display = 'none';
    document.getElementById('editor-empty').style.display = '';
    highlightCard();
    // Clear picker — clearSelection fires onChange which (with _editorOpen=false)
    // will set selectedRoomIds=[] and call loadList() for us
    roomPicker.clearSelection();
}

let _lastClickedCard = null;

function selectCard(mode, id, el, ev) {
    // If editor is open, ignore card clicks
    if (editorIsOpen()) return;

    const ctrlKey  = ev && (ev.ctrlKey || ev.metaKey);
    const shiftKey = ev && ev.shiftKey;

    if (ctrlKey) {
        // Ctrl+click: toggle this card in/out of selection
        const idx = selectedCards.findIndex(c => c.id === id && c.mode === mode);
        if (idx !== -1) {
            selectedCards.splice(idx, 1);
        } else {
            selectedCards.push({ id, mode, name: el ? el.dataset.name : '' });
        }
        _lastClickedCard = { mode, id };
    } else if (shiftKey && _lastClickedCard) {
        // Shift+click: range select from last-clicked to this card
        const panel = document.getElementById(currentTab === 'tasks' ? 'task-list' : 'group-list');
        const cards = [...panel.querySelectorAll('.card')];
        const lastIdx = cards.findIndex(c => c.dataset.id == _lastClickedCard.id && c.dataset.mode === _lastClickedCard.mode);
        const curIdx  = cards.findIndex(c => c.dataset.id == id && c.dataset.mode === mode);
        if (lastIdx !== -1 && curIdx !== -1) {
            const lo = Math.min(lastIdx, curIdx);
            const hi = Math.max(lastIdx, curIdx);
            selectedCards = [];
            for (let i = lo; i <= hi; i++) {
                const card = cards[i];
                selectedCards.push({
                    id: Number(card.dataset.id),
                    mode: card.dataset.mode,
                    name: card.dataset.name
                });
            }
        }
    } else {
        // Plain click: single select (replace selection)
        selectedCards = [{ id, mode, name: el ? el.dataset.name : '' }];
        _lastClickedCard = { mode, id };
    }

    highlightCard();
    updateSelectionUI();
}

function deselectAllCards() {
    selectedCards = [];
    highlightCard();
    updateSelectionUI();
}

function updateSelectionUI() {
    const editRow    = document.getElementById('editor-edit-row');
    const countEl    = document.getElementById('editor-selection-count');
    const empty      = document.getElementById('editor-empty');
    const detailEl   = document.getElementById('editor-selection-detail');
    const editBtn    = document.getElementById('editor-edit-btn');
    const groupBtn   = document.getElementById('editor-group-btn');
    const ungroupBtn = document.getElementById('editor-ungroup-btn');

    if (selectedCards.length > 0) {
        editRow.style.display = '';
        empty.style.display = 'none';
        countEl.textContent = selectedCards.length + ' selected';
        editBtn.disabled = false;
        editBtn.style.opacity = '1';
        editBtn.style.cursor = 'pointer';

        // Show Group button when 2+ tasks OR 2+ groups are selected
        const allTasks = selectedCards.every(c => c.mode === 'task');
        const allGroups = selectedCards.every(c => c.mode === 'group');
        groupBtn.style.display = (selectedCards.length >= 2 && (allTasks || allGroups)) ? 'block' : 'none';

        // Show Ungroup button when 1+ groups are selected on the Groups tab
        ungroupBtn.style.display = allGroups ? 'block' : 'none';

        // Build detail preview
        renderSelectionDetail(detailEl);
    } else {
        editRow.style.display = 'none';
        detailEl.style.display = 'none';
        empty.style.display = '';
        groupBtn.style.display = 'none';
        ungroupBtn.style.display = 'none';
    }
}

function renderSelectionDetail(el) {
    const labelStyle = 'font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#9ca3af; margin-bottom:4px;';
    const chipStyle  = 'display:inline-block; background:#f1f5f9; color:#334155; font-size:12px; padding:2px 8px; border-radius:6px; margin:0 4px 4px 0;';
    let html = '';

    const rowStyle = 'display:flex; justify-content:space-between; align-items:center; padding:5px 0; border-bottom:1px solid #f1f5f9; font-size:13px;';
    const taskNameStyle = 'color:#334155; font-weight:500;';
    const roomNameStyle = 'color:#6b7280; font-size:12px; text-align:right;';

    if (selectedCards.length === 1) {
        const c = selectedCards[0];
        if (c.mode === 'group') {
            const g = _loadedGroups.find(g => g.id == c.id);
            if (g) {
                // Tasks with their rooms as a list
                const tasks = g.tasks || [];
                if (tasks.length) {
                    html += `<div style="${labelStyle}">Tasks in Group</div>`;
                    tasks.forEach(t => {
                        const roomNames = (t.rooms || []).map(r => esc(r.name)).join(', ') || '<span style="color:#d1d5db;">—</span>';
                        html += `<div style="${rowStyle}">
                            <span style="${taskNameStyle}">${esc(t.name)}</span>
                            <span style="${roomNameStyle}">${roomNames}</span>
                        </div>`;
                    });
                    html += '<div style="margin-bottom:10px;"></div>';
                }
                // Group-level rooms
                const rooms = g.rooms || [];
                if (rooms.length) {
                    html += `<div style="${labelStyle}">Group Rooms</div>`;
                    html += '<div style="margin-bottom:10px;">';
                    rooms.forEach(r => { html += `<span style="${chipStyle}">${esc(r.name)}</span>`; });
                    html += '</div>';
                }
                // Workers
                const workers = g.workers || [];
                if (workers.length) {
                    html += `<div style="${labelStyle}">Preferred Workers</div>`;
                    html += '<div style="margin-bottom:10px;">';
                    workers.forEach(w => { html += `<span style="${chipStyle}">${esc(w.name)}</span>`; });
                    html += '</div>';
                }
                // Child groups
                const children = g.children || [];
                if (children.length) {
                    html += `<div style="${labelStyle}">Sub-Groups</div>`;
                    children.forEach(ch => {
                        html += `<div style="${rowStyle}">
                            <span style="${taskNameStyle}">${esc(ch.name)}</span>
                            <span style="${roomNameStyle}">${ch.task_count} task${ch.task_count!=1?'s':''}</span>
                        </div>`;
                    });
                    html += '<div style="margin-bottom:10px;"></div>';
                }
                if (!tasks.length && !rooms.length && !workers.length && !children.length) {
                    html += '<div style="color:#9ca3af; font-size:13px; padding:8px 0;">No details to show.</div>';
                }
            }
        } else if (c.mode === 'task') {
            const t = _loadedTasks.find(t => t.id == c.id);
            if (t) {
                const rooms = t.rooms || [];
                if (rooms.length) {
                    html += `<div style="${labelStyle}">Assigned Rooms</div>`;
                    html += '<div style="margin-bottom:10px;">';
                    rooms.forEach(r => { html += `<span style="${chipStyle}">${esc(r.name)}</span>`; });
                    html += '</div>';
                }
                const workers = t.workers || [];
                if (workers.length) {
                    html += `<div style="${labelStyle}">Preferred Workers</div>`;
                    html += '<div style="margin-bottom:10px;">';
                    workers.forEach(w => { html += `<span style="${chipStyle}">${esc(w.name)}</span>`; });
                    html += '</div>';
                }
                if (t.description) {
                    html += `<div style="${labelStyle}">Description</div>`;
                    html += `<div style="font-size:13px; color:#4b5563; margin-bottom:10px;">${esc(t.description)}</div>`;
                }
            }
        }
    } else {
        // Multiple selected — show names
        html += `<div style="${labelStyle}">${selectedCards.length} Items Selected</div>`;
        html += '<div style="margin-bottom:10px;">';
        selectedCards.forEach(c => { html += `<span style="${chipStyle}">${esc(c.name)}</span>`; });
        html += '</div>';
    }

    if (html) {
        el.innerHTML = html;
        el.style.display = '';
    } else {
        el.style.display = 'none';
    }
}

function openEditorForSelected() {
    if (selectedCards.length === 1) {
        const c = selectedCards[0];
        openEditor(c.mode, c.id);
    } else if (selectedCards.length > 1) {
        openMultiEditor();
    }
}

function openMultiEditor() {
    // Only support multi-edit for items of the same mode (tasks or groups)
    const modes = [...new Set(selectedCards.map(c => c.mode))];
    if (modes.length > 1) { alert('Cannot edit tasks and groups together.'); return; }

    const mode = modes[0];
    const ids  = selectedCards.map(c => c.id);
    multiEditIds  = ids;
    touchedFields = {};
    editingMode   = mode;
    editingId     = null; // null signals multi-edit
    _editorOpen   = true;

    document.getElementById('e-mode').value = mode;
    document.getElementById('e-id').value   = '';
    document.getElementById('e-del-btn').style.display = 'none';
    document.getElementById('resource-section').style.display = mode === 'task' ? '' : 'none';
    document.getElementById('group-tasks-section').style.display = mode === 'group' ? '' : 'none';
    document.getElementById('reusable-section').style.display = mode === 'task' ? '' : 'none';
    document.getElementById('parent-group-section').style.display = mode === 'group' ? '' : 'none';

    // Reset
    resourceState = { tools: [], supplies: [], materials: [], equipment: [] };
    groupTasksState = [];
    workersState = [];
    roomsState = [];
    renderResourceChips('tools'); renderResourceChips('supplies');
    renderResourceChips('materials'); renderResourceChips('equipment');
    renderGroupTaskChips(); renderWorkerChips(); renderRoomChips();
    roomPicker.clearSelection();

    // Load all selected items
    const apiAction = mode === 'task' ? 'get_task' : 'get_task_group';
    Promise.all(ids.map(id => api(apiAction, { id }))).then(items => {
        items = items.filter(d => !d.error);
        if (!items.length) return;

        // ── Merge scalar fields ──
        const nameVal = allSame(items, 'name')             ? items[0].name             : _MULTI_;
        const descVal = allSame(items, 'description')      ? (items[0].description||'') : _MULTI_;
        const typeVal = allSame(items, 'task_type_id')      ? items[0].task_type_id     : _MULTI_;
        const estVal  = allSame(items, 'estimated_minutes') ? items[0].estimated_minutes : _MULTI_;

        const nameEl = document.getElementById('e-name');
        const descEl = document.getElementById('e-desc');
        const typeEl = document.getElementById('e-type');
        const estEl  = document.getElementById('e-est-min');

        nameEl.value       = nameVal === _MULTI_ ? '' : nameVal;
        nameEl.placeholder = nameVal === _MULTI_ ? 'Multiple' : '';
        nameEl.dataset.multi = nameVal === _MULTI_ ? '1' : '';

        descEl.value       = descVal === _MULTI_ ? '' : descVal;
        descEl.placeholder = descVal === _MULTI_ ? 'Multiple' : 'Brief description...';
        descEl.dataset.multi = descVal === _MULTI_ ? '1' : '';

        if (typeVal === _MULTI_) {
            // Add a "Multiple" placeholder option
            let multiOpt = typeEl.querySelector('option[value="_MULTI_"]');
            if (!multiOpt) {
                multiOpt = document.createElement('option');
                multiOpt.value = '_MULTI_';
                multiOpt.textContent = '— Multiple —';
                typeEl.insertBefore(multiOpt, typeEl.firstChild);
            }
            typeEl.value = '_MULTI_';
        } else {
            const multiOpt = typeEl.querySelector('option[value="_MULTI_"]');
            if (multiOpt) multiOpt.remove();
            typeEl.value = typeVal;
        }

        estEl.value       = estVal === _MULTI_ ? '' : estVal;
        estEl.placeholder = estVal === _MULTI_ ? 'Multiple' : '';
        estEl.dataset.multi = estVal === _MULTI_ ? '1' : '';

        // ── Merge list fields (workers) ──
        const workerKey = mode === 'task' ? 'preferred_workers' : 'preferred_workers';
        if (allSameList(items, workerKey)) {
            workersState = (items[0][workerKey] || []).map(w => ({id: w.id, name: w.name}));
        } else {
            workersState = [{id: _MULTI_, name: 'Multiple'}];
        }
        renderWorkerChips();

        // ── Merge rooms ──
        const roomKey = 'rooms';
        if (allSameList(items, roomKey)) {
            const roomIds = (items[0].rooms || []).map(r => Number(r.id));
            roomsState = (items[0].rooms || []).map(r => ({id: Number(r.id), name: r.name, detail: ''}));
            if (roomIds.length) {
                roomPicker.selectRooms(roomIds);
                const sel = roomPicker.getSelection();
                roomsState = Object.values(sel).map(r => ({
                    id: Number(r.id), name: r.name,
                    detail: (r.building||'') + ' / ' + (r.floor||'')
                }));
                selectedRoomIds = Object.keys(sel).map(Number);
                updateRoomFilterBanner();
            }
        } else {
            roomsState = [{id: _MULTI_, name: 'Multiple'}];
        }
        renderRoomChips();

        // ── Merge resources (task mode) ──
        if (mode === 'task') {
            ['tools','supplies','materials','equipment'].forEach(rType => {
                if (allSameList(items, rType)) {
                    resourceState[rType] = (items[0][rType] || []).map(r => ({id: r.id, name: r.name}));
                } else {
                    resourceState[rType] = [{id: _MULTI_, name: 'Multiple'}];
                }
                renderResourceChips(rType);
            });
        }

        // ── Track field changes ──
        ['e-name','e-desc','e-est-min'].forEach(elId => {
            const el = document.getElementById(elId);
            el.addEventListener('input', () => { touchedFields[elId] = true; }, {once: true});
        });
        typeEl.addEventListener('change', () => { touchedFields['e-type'] = true; }, {once: true});
    });

    document.getElementById('editor-title').textContent = `Edit ${ids.length} Tasks`;
    document.getElementById('editor-empty').style.display = 'none';
    document.getElementById('editor-edit-row').style.display = 'none';
    document.getElementById('editor-form-wrap').classList.add('open');
    // Remove required from name for multi-edit
    document.getElementById('e-name').required = false;
    highlightCard();
}

// Helper: check if all items have the same scalar value for a key
function allSame(items, key) {
    const v = items[0][key];
    return items.every(i => i[key] === v);
}

// Helper: check if all items have the same list (by sorted IDs)
function allSameList(items, key) {
    const first = (items[0][key] || []).map(x => x.id).sort().join(',');
    return items.every(i => (i[key] || []).map(x => x.id).sort().join(',') === first);
}

function groupSelected() {
    if (selectedCards.length < 2) return;
    const allTasks = selectedCards.every(c => c.mode === 'task');
    const allGroups = selectedCards.every(c => c.mode === 'group');
    if (allTasks) groupSelectedTasks();
    else if (allGroups) groupSelectedGroups();
    else alert('Select items of the same type to group.');
}

function groupSelectedGroups() {
    if (selectedCards.length < 2) return;
    if (!selectedCards.every(c => c.mode === 'group')) {
        alert('Only groups can be grouped together.'); return;
    }

    const groupIds = selectedCards.map(c => c.id);
    const groupNames = selectedCards.map(c => c.name);

    // Open editor in group mode with no pre-filled tasks — this is a parent group
    editingMode = 'group';
    editingId = null;
    multiEditIds = [];
    _editorOpen = true;

    document.getElementById('e-mode').value = 'group';
    document.getElementById('e-id').value = '';
    document.getElementById('e-name').value = '';
    document.getElementById('e-name').placeholder = 'e.g. Bathroom Deep Clean, Kitchen Reset...';
    document.getElementById('e-name').required = true;
    document.getElementById('e-desc').value = '';
    document.getElementById('e-desc').placeholder = 'Brief description...';

    // Sum estimated minutes from selected groups
    const totalMin = groupIds.reduce((sum, gid) => {
        const g = _loadedGroups.find(g => g.id == gid);
        return sum + (g ? Number(g.estimated_minutes) || 0 : 0);
    }, 0);

    // Pre-fill type if all selected groups share the same one
    const groupTypes = groupIds.map(gid => { const g = _loadedGroups.find(g => g.id == gid); return g ? g.task_type_id : null; }).filter(Boolean);
    const sameType = groupTypes.length && groupTypes.every(v => v == groupTypes[0]) ? groupTypes[0] : '';
    document.getElementById('e-type').value = sameType;
    document.getElementById('e-est-min').value = totalMin || 15;
    document.getElementById('e-del-btn').style.display = 'none';

    document.getElementById('resource-section').style.display = 'none';
    document.getElementById('group-tasks-section').style.display = '';
    document.getElementById('reusable-section').style.display = 'none';
    document.getElementById('parent-group-section').style.display = '';
    loadParentGroupOptions(null);

    // Reset states
    resourceState = { tools: [], supplies: [], materials: [], equipment: [] };
    groupTasksState = [];
    workersState = [];
    roomsState = [];
    renderResourceChips('tools'); renderResourceChips('supplies');
    renderResourceChips('materials'); renderResourceChips('equipment');
    renderWorkerChips(); renderRoomChips();
    roomPicker.clearSelection();
    renderGroupTaskChips();

    // Collect union of workers from all selected groups
    const seenWorkerIds = new Set();
    groupIds.forEach(gid => {
        const g = _loadedGroups.find(g => g.id == gid);
        if (g && g.workers) {
            g.workers.forEach(w => {
                if (!seenWorkerIds.has(Number(w.id))) {
                    seenWorkerIds.add(Number(w.id));
                    workersState.push({ id: Number(w.id), name: w.name });
                }
            });
        }
    });
    renderWorkerChips();

    // Collect union of rooms from all selected groups
    const seenRoomIds = new Set();
    const unionRooms = [];
    groupIds.forEach(gid => {
        const g = _loadedGroups.find(g => g.id == gid);
        if (g && g.rooms) {
            g.rooms.forEach(r => {
                if (!seenRoomIds.has(Number(r.id))) {
                    seenRoomIds.add(Number(r.id));
                    unionRooms.push(r);
                }
            });
        }
    });
    if (unionRooms.length) {
        const roomIds = unionRooms.map(r => Number(r.id));
        roomPicker.selectRooms(roomIds);
        const sel = roomPicker.getSelection();
        roomsState = Object.values(sel).map(r => ({
            id: Number(r.id),
            name: r.name,
            detail: (r.building || '') + ' / ' + (r.floor || '')
        }));
        selectedRoomIds = Object.keys(sel).map(Number);
        updateRoomFilterBanner();
        renderRoomChips();
    }

    // Store group IDs to set as children after saving
    _pendingChildGroupIds = groupIds;

    document.getElementById('editor-title').textContent = 'New Parent Group';
    document.getElementById('editor-empty').style.display = 'none';
    document.getElementById('editor-edit-row').style.display = 'none';
    document.getElementById('editor-selection-detail').style.display = 'none';
    document.getElementById('editor-form-wrap').classList.add('open');
}

function groupSelectedTasks() {
    if (selectedCards.length < 2) return;
    if (!selectedCards.every(c => c.mode === 'task')) {
        alert('Only tasks can be grouped.'); return;
    }

    const taskIds = selectedCards.map(c => c.id);
    const taskNames = selectedCards.map(c => c.name);

    // Switch to groups tab and open the editor for a new group pre-filled with these tasks
    currentTab = 'group';
    // Don't switch tab visually — keep on tasks tab so user sees context

    // Open editor in group mode with pre-filled tasks
    editingMode = 'group';
    editingId = null;
    multiEditIds = [];
    _editorOpen = true;

    document.getElementById('e-mode').value = 'group';
    document.getElementById('e-id').value = '';
    document.getElementById('e-name').value = '';
    document.getElementById('e-name').placeholder = 'e.g. Sanctuary Cleanup, Kitchen Reset...';
    document.getElementById('e-name').required = true;
    document.getElementById('e-desc').value = '';
    document.getElementById('e-desc').placeholder = 'Brief description...';
    // Sum estimated minutes from selected tasks, fallback to 15
    const totalMin = taskIds.reduce((sum, tid) => {
        const t = _loadedTasks.find(t => t.id == tid);
        return sum + (t ? Number(t.estimated_minutes) || 0 : 0);
    }, 0);
    // Pre-fill type if all selected tasks share the same one
    const taskTypes = taskIds.map(tid => { const t = _loadedTasks.find(t => t.id == tid); return t ? t.task_type_id : null; }).filter(Boolean);
    const sameType = taskTypes.length && taskTypes.every(v => v == taskTypes[0]) ? taskTypes[0] : '';
    document.getElementById('e-type').value = sameType;
    document.getElementById('e-est-min').value = totalMin || 15;
    document.getElementById('e-del-btn').style.display = 'none';

    document.getElementById('resource-section').style.display = 'none';
    document.getElementById('group-tasks-section').style.display = '';
    document.getElementById('reusable-section').style.display = 'none';
    document.getElementById('parent-group-section').style.display = '';
    loadParentGroupOptions(null);

    // Reset states
    resourceState = { tools: [], supplies: [], materials: [], equipment: [] };
    workersState = [];
    roomsState = [];
    renderResourceChips('tools'); renderResourceChips('supplies');
    renderResourceChips('materials'); renderResourceChips('equipment');
    renderWorkerChips(); renderRoomChips();
    roomPicker.clearSelection();

    // Pre-fill the tasks
    groupTasksState = taskIds.map((id, i) => ({ id, name: taskNames[i] }));
    renderGroupTaskChips();

    // Collect union of workers from all selected tasks
    const seenWorkerIds = new Set();
    taskIds.forEach(tid => {
        const t = _loadedTasks.find(t => t.id == tid);
        if (t && t.workers) {
            t.workers.forEach(w => {
                if (!seenWorkerIds.has(Number(w.id))) {
                    seenWorkerIds.add(Number(w.id));
                    workersState.push({ id: Number(w.id), name: w.name });
                }
            });
        }
    });
    renderWorkerChips();

    // Collect union of rooms from all selected tasks
    const seenRoomIds = new Set();
    const unionRooms = [];
    taskIds.forEach(tid => {
        const t = _loadedTasks.find(t => t.id == tid);
        if (t && t.rooms) {
            t.rooms.forEach(r => {
                if (!seenRoomIds.has(Number(r.id))) {
                    seenRoomIds.add(Number(r.id));
                    unionRooms.push(r);
                }
            });
        }
    });
    if (unionRooms.length) {
        const roomIds = unionRooms.map(r => Number(r.id));
        roomPicker.selectRooms(roomIds);
        const sel = roomPicker.getSelection();
        roomsState = Object.values(sel).map(r => ({
            id: Number(r.id),
            name: r.name,
            detail: (r.building || '') + ' / ' + (r.floor || '')
        }));
        selectedRoomIds = Object.keys(sel).map(Number);
        updateRoomFilterBanner();
        renderRoomChips();
    }

    document.getElementById('editor-title').textContent = 'New Task Group';
    document.getElementById('editor-empty').style.display = 'none';
    document.getElementById('editor-edit-row').style.display = 'none';
    document.getElementById('editor-selection-detail').style.display = 'none';
    document.getElementById('editor-form-wrap').classList.add('open');
}

function ungroupSelected() {
    const groups = selectedCards.filter(c => c.mode === 'group');
    if (!groups.length) return;
    const names = groups.map(g => g.name).join(', ');
    if (!confirm(`Ungroup "${names}"? The tasks inside will remain but the group(s) will be deleted.`)) return;

    Promise.all(groups.map(g => {
        const fd = new FormData();
        fd.set('action', 'delete_task_group');
        fd.set('id', g.id);
        return postApi(fd);
    })).then(() => {
        selectedCards = [];
        highlightCard();
        updateSelectionUI();
        document.getElementById('editor-edit-row').style.display = 'none';
        document.getElementById('editor-empty').style.display = '';
        loadList();
    });
}

function highlightCard() {
    document.querySelectorAll('.card').forEach(c => c.classList.remove('selected'));
    if (editorIsOpen() && editingId) {
        const card = document.querySelector(`.card[data-id="${editingId}"]`);
        if (card) card.classList.add('selected');
    } else {
        selectedCards.forEach(sc => {
            const card = document.querySelector(`.card[data-mode="${sc.mode}"][data-id="${sc.id}"]`);
            if (card) card.classList.add('selected');
        });
    }
}

function saveItem(e) {
    e.preventDefault();
    const mode = document.getElementById('e-mode').value;

    // ── Multi-edit save ──
    if (multiEditIds.length > 1) {
        const fd = new FormData();
        fd.set('action', 'batch_save_tasks');
        fd.set('ids', multiEditIds.join(','));

        // Only send fields the user actually changed
        if (touchedFields['e-name']) {
            fd.set('name', document.getElementById('e-name').value.trim());
        }
        if (touchedFields['e-desc']) {
            fd.set('description', document.getElementById('e-desc').value.trim());
        }
        if (touchedFields['e-type']) {
            const tv = document.getElementById('e-type').value;
            fd.set('task_type_id', tv === '_MULTI_' ? '_MULTI_' : tv);
        }
        if (touchedFields['e-est-min']) {
            fd.set('estimated_minutes', document.getElementById('e-est-min').value);
        }

        // List fields: send if they were changed from _MULTI_ placeholder
        const hasMultiWorker = workersState.length === 1 && workersState[0].id === _MULTI_;
        if (touchedFields['workers'] || !hasMultiWorker) {
            if (!hasMultiWorker) {
                fd.set('worker_ids', workersState.map(w => w.id).join(','));
            } else {
                fd.set('worker_ids', '_MULTI_');
            }
        }

        const hasMultiRoom = roomsState.length === 1 && roomsState[0].id === _MULTI_;
        if (touchedFields['rooms'] || !hasMultiRoom) {
            if (!hasMultiRoom) {
                fd.set('room_ids', roomsState.map(r => r.id).join(','));
            } else {
                fd.set('room_ids', '_MULTI_');
            }
        }

        if (mode === 'task') {
            ['tools','supplies','materials','equipment'].forEach(rType => {
                const isMulti = resourceState[rType].length === 1 && resourceState[rType][0].id === _MULTI_;
                const key = rType === 'tools' ? 'tool_ids' : rType === 'supplies' ? 'supply_ids' : rType === 'materials' ? 'material_ids' : 'equipment_ids';
                if (touchedFields[rType] || !isMulti) {
                    fd.set(key, isMulti ? '_MULTI_' : resourceState[rType].map(r => r.id).join(','));
                }
            });
        }

        postApi(fd).then(r => {
            if (r.error) { alert(r.error); return; }
            closeEditor();
        });
        return;
    }

    // ── Single-item save ──
    const fd   = new FormData();
    fd.set('action', mode === 'task' ? 'save_task' : 'save_task_group');
    fd.set('id', document.getElementById('e-id').value);
    fd.set('name', document.getElementById('e-name').value.trim());
    fd.set('description', document.getElementById('e-desc').value.trim());
    fd.set('task_type_id', document.getElementById('e-type').value);
    fd.set('estimated_minutes', document.getElementById('e-est-min').value);
    fd.set('worker_ids', workersState.map(w => w.id).join(','));
    fd.set('room_ids', roomsState.map(r => r.id).join(','));

    if (mode === 'task') {
        fd.set('reusable', document.getElementById('e-reusable').checked ? 1 : 0);
        fd.set('tool_ids',      resourceState.tools.map(r => r.id).join(','));
        fd.set('supply_ids',    resourceState.supplies.map(r => r.id).join(','));
        fd.set('material_ids',  resourceState.materials.map(r => r.id).join(','));
        fd.set('equipment_ids', resourceState.equipment.map(r => r.id).join(','));
    } else {
        fd.set('task_ids', groupTasksState.map(t => t.id).join(','));
        fd.set('parent_id', document.getElementById('e-parent-group').value);
    }

    postApi(fd).then(r => {
        if (r.error) { alert(r.error); return; }

        // If we were creating a parent group from selected groups, update their parent_id
        if (mode === 'group' && _pendingChildGroupIds.length > 0 && r.id) {
            const newParentId = r.id;
            Promise.all(_pendingChildGroupIds.map(childId => {
                const childFd = new FormData();
                childFd.set('action', 'set_group_parent');
                childFd.set('id', childId);
                childFd.set('parent_id', newParentId);
                return postApi(childFd);
            })).then(() => {
                _pendingChildGroupIds = [];
                closeEditor();
            });
        } else {
            _pendingChildGroupIds = [];
            closeEditor();
        }
    });
}

function deleteItem() {
    const mode = document.getElementById('e-mode').value;
    const id   = document.getElementById('e-id').value;
    if (!id) return;
    const label = mode === 'task' ? 'task' : 'task group';
    if (!confirm(`Delete this ${label}?`)) return;
    const fd = new FormData();
    fd.set('action', mode === 'task' ? 'delete_task' : 'delete_task_group');
    fd.set('id', id);
    postApi(fd).then(r => { if (r.success) { closeEditor(); loadList(); } });
}

// ═══════════════════════════════════════════════════════════
// WORKERS COMBOBOX
// ═══════════════════════════════════════════════════════════
function renderWorkerChips() {
    const c = document.getElementById('e-workers-chips');
    c.innerHTML = workersState.map(w => {
        if (w.id === _MULTI_) return `<span class="chip" style="background:#fef3c7;color:#92400e;font-style:italic;">Multiple</span>`;
        return `<span class="chip">${esc(w.name)}<span class="x" onclick="removeWorker(${w.id})">&times;</span></span>`;
    }).join('');
}
function removeWorker(id) {
    workersState = workersState.filter(w => w.id !== id);
    touchedFields['workers'] = true;
    renderWorkerChips();
}
function addWorker(item) {
    // Clear _MULTI_ sentinel when user adds a specific worker
    workersState = workersState.filter(w => w.id !== _MULTI_);
    if (workersState.some(w => w.id === item.id)) return;
    workersState.push(item);
    touchedFields['workers'] = true;
    renderWorkerChips();
    document.getElementById('e-workers-input').value = '';
    document.getElementById('e-workers-dd').classList.add('hidden');
}

(function() {
    const input = document.getElementById('e-workers-input');
    const dd    = document.getElementById('e-workers-dd');
    let timer = null;
    input.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(() => searchWorkers(input.value), 200); });
    input.addEventListener('focus', () => searchWorkers(input.value));
    document.addEventListener('click', e => { if (!input.parentElement.contains(e.target)) dd.classList.add('hidden'); });
})();

function searchWorkers(q) {
    const dd = document.getElementById('e-workers-dd');
    api('get_workers', { q }).then(items => {
        const existing = workersState.map(w => w.id);
        const filtered = items.filter(i => !existing.includes(i.id));
        dd.innerHTML = '';
        filtered.forEach(item => {
            const opt = document.createElement('div');
            opt.className = 'combo-opt';
            opt.textContent = item.name;
            opt.onmousedown = e => { e.preventDefault(); addWorker(item); };
            dd.appendChild(opt);
        });
        dd.classList.toggle('hidden', !dd.children.length);
    });
}

// ═══════════════════════════════════════════════════════════
// ROOMS (synced from floor plan picker)
// ═══════════════════════════════════════════════════════════
function renderRoomChips() {
    const c = document.getElementById('e-rooms-chips');
    const empty = document.getElementById('e-rooms-empty');
    c.innerHTML = roomsState.map(r => {
        if (r.id === _MULTI_) return `<span class="chip" style="background:#fef3c7;color:#92400e;font-style:italic;">Multiple</span>`;
        return `<span class="chip">${esc(r.name)}<span class="x" onclick="deselectRoom(${r.id})">&times;</span></span>`;
    }).join('');
    empty.style.display = roomsState.length ? 'none' : '';
}
function deselectRoom(id) {
    const sel = roomPicker.getSelection();
    if (sel[id]) {
        const remaining = Object.keys(sel).map(Number).filter(rid => rid !== id);
        roomPicker.clearSelection();
        if (remaining.length) {
            roomPicker.selectRooms(remaining);
            // selectRooms doesn't fire onChange, so manually sync state
            const newSel = roomPicker.getSelection();
            roomsState = Object.values(newSel).map(r => ({
                id: Number(r.id),
                name: r.name,
                detail: (r.building || '') + ' / ' + (r.floor || '')
            }));
            selectedRoomIds = Object.keys(newSel).map(Number);
            updateRoomFilterBanner();
        }
        renderRoomChips();
    }
}

// ═══════════════════════════════════════════════════════════
// RESOURCE COMBOBOXES
// ═══════════════════════════════════════════════════════════
const RES_CONFIG = {
    tools:     { get: 'get_tools',      create: 'create_tool',      label: 'tool' },
    supplies:  { get: 'get_supplies',   create: 'create_supply',    label: 'supply' },
    materials: { get: 'get_materials',  create: 'create_material',  label: 'material' },
    equipment: { get: 'get_equipment',  create: 'create_equipment', label: 'equipment' },
};

function renderResourceChips(type) {
    const container = document.getElementById(`e-${type}-chips`);
    container.innerHTML = resourceState[type].map(r => {
        if (r.id === _MULTI_) return `<span class="chip" style="background:#fef3c7;color:#92400e;font-style:italic;">Multiple</span>`;
        return `<span class="chip">${esc(r.name)}<span class="x" onclick="removeResource('${type}',${r.id})">&times;</span></span>`;
    }).join('');
}

function removeResource(type, id) {
    resourceState[type] = resourceState[type].filter(r => r.id !== id);
    touchedFields[type] = true;
    renderResourceChips(type);
}

function addResource(type, item) {
    // Clear _MULTI_ sentinel when user adds a specific resource
    resourceState[type] = resourceState[type].filter(r => r.id !== _MULTI_);
    if (resourceState[type].some(r => r.id === item.id)) return;
    resourceState[type].push(item);
    touchedFields[type] = true;
    renderResourceChips(type);
    document.getElementById(`e-${type}-input`).value = '';
    document.getElementById(`e-${type}-dd`).classList.add('hidden');
}

Object.keys(RES_CONFIG).forEach(type => {
    const cfg = RES_CONFIG[type];
    const input = document.getElementById(`e-${type}-input`);
    const dd    = document.getElementById(`e-${type}-dd`);
    let timer = null;

    input.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => searchResource(type, input.value), 200);
    });
    input.addEventListener('focus', () => searchResource(type, input.value));
    document.addEventListener('click', e => {
        if (!input.parentElement.contains(e.target)) dd.classList.add('hidden');
    });
});

function searchResource(type, q) {
    const cfg = RES_CONFIG[type];
    const dd  = document.getElementById(`e-${type}-dd`);
    api(cfg.get, { q }).then(items => {
        const trimmed = q.trim();
        const existing = resourceState[type].map(r => r.id);
        const filtered = items.filter(i => !existing.includes(i.id));
        const exact = items.some(i => i.name.toLowerCase() === trimmed.toLowerCase());
        dd.innerHTML = '';
        filtered.forEach(item => {
            const opt = document.createElement('div');
            opt.className = 'combo-opt';
            opt.textContent = item.name;
            opt.onmousedown = e => { e.preventDefault(); addResource(type, item); };
            dd.appendChild(opt);
        });
        if (trimmed && !exact) {
            const create = document.createElement('div');
            create.className = 'combo-opt create';
            create.textContent = `+ Create "${trimmed}"`;
            create.onmousedown = e => {
                e.preventDefault();
                const fd = new FormData();
                fd.set('action', cfg.create);
                fd.set('name', trimmed);
                postApi(fd).then(r => { if (r.id) addResource(type, r); });
            };
            dd.appendChild(create);
        }
        dd.classList.toggle('hidden', !dd.children.length);
    });
}

// ═══════════════════════════════════════════════════════════
// GROUP TASKS COMBOBOX
// ═══════════════════════════════════════════════════════════
function renderGroupTaskChips() {
    const container = document.getElementById('e-group-tasks-chips');
    container.innerHTML = groupTasksState.map(t =>
        `<span class="chip">${esc(t.name)}<span class="x" onclick="removeGroupTask(${t.id})">&times;</span></span>`
    ).join('');
}

function removeGroupTask(id) {
    groupTasksState = groupTasksState.filter(t => t.id !== id);
    renderGroupTaskChips();
}

(function() {
    const input = document.getElementById('e-group-tasks-input');
    const dd    = document.getElementById('e-group-tasks-dd');
    let timer = null;

    input.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => searchGroupTasks(input.value), 200);
    });
    input.addEventListener('focus', () => searchGroupTasks(input.value));
    document.addEventListener('click', e => {
        if (!input.parentElement.contains(e.target)) dd.classList.add('hidden');
    });
})();

function searchGroupTasks(q) {
    const dd = document.getElementById('e-group-tasks-dd');
    api('get_tasks', { q }).then(tasks => {
        const existing = groupTasksState.map(t => t.id);
        const filtered = tasks.filter(t => !existing.includes(t.id));
        dd.innerHTML = '';
        filtered.forEach(t => {
            const opt = document.createElement('div');
            opt.className = 'combo-opt';
            opt.textContent = `${t.name} (${t.type_name})`;
            opt.onmousedown = e => { e.preventDefault(); addGroupTask(t); };
            dd.appendChild(opt);
        });
        dd.classList.toggle('hidden', !dd.children.length);
    });
}

function addGroupTask(task) {
    if (groupTasksState.some(t => t.id === task.id)) return;
    groupTasksState.push({ id: task.id, name: task.name });
    renderGroupTaskChips();
    document.getElementById('e-group-tasks-input').value = '';
    document.getElementById('e-group-tasks-dd').classList.add('hidden');
}

// ═══════════════════════════════════════════════════════════
// PARENT GROUP SELECTOR
// ═══════════════════════════════════════════════════════════
function loadParentGroupOptions(excludeId) {
    // Fetch all groups (flat) and build options excluding the current group and its descendants
    api('get_task_groups', { flat: 1 }).then(groups => {
        const sel = document.getElementById('e-parent-group');
        sel.innerHTML = '<option value="">— None (top-level) —</option>';

        // Build a quick tree to find descendants of excludeId (to prevent circular refs)
        const descendants = new Set();
        if (excludeId) {
            descendants.add(Number(excludeId));
            let changed = true;
            while (changed) {
                changed = false;
                groups.forEach(g => {
                    if (g.parent_id && descendants.has(Number(g.parent_id)) && !descendants.has(Number(g.id))) {
                        descendants.add(Number(g.id));
                        changed = true;
                    }
                });
            }
        }

        // Build indented options showing hierarchy
        const byParent = {};
        groups.forEach(g => {
            const pid = g.parent_id || 0;
            if (!byParent[pid]) byParent[pid] = [];
            byParent[pid].push(g);
        });

        function addOptions(parentId, depth) {
            const children = byParent[parentId] || [];
            children.forEach(g => {
                if (descendants.has(Number(g.id))) return; // skip self & descendants
                const indent = '\u00A0\u00A0'.repeat(depth);
                const opt = document.createElement('option');
                opt.value = g.id;
                opt.textContent = indent + (depth > 0 ? '└ ' : '') + g.name;
                sel.appendChild(opt);
                addOptions(g.id, depth + 1);
            });
        }
        addOptions(0, 0);
    });
}

// ═══════════════════════════════════════════════════════════
// HELPERS
// ═══════════════════════════════════════════════════════════
function api(action, params = {}) {
    return fetch(`${BASE_PATH}/api/tasks_api.php?${new URLSearchParams({action, ...params})}`).then(r => r.json());
}
function postApi(fd) {
    return fetch(BASE_PATH + '/api/tasks_api.php', { method: 'POST', body: fd }).then(r => r.json());
}

// ═══════════════════════════════════════════════════════════
// INIT
// ═══════════════════════════════════════════════════════════
loadList();
</script>

</body>
</html>
