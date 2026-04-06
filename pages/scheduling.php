<?php
$pageTitle = 'Scheduling — Church Facility Manager';
require_once __DIR__ . '/../includes/nav.php';
require_once __DIR__ . '/../config/database.php';

$db        = getDB();
$buildings = $db->query("SELECT id, name FROM buildings ORDER BY name")->fetchAll();

// Check if auto-generate is enabled
$_autoGen = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'auto_generate_assignments'")->fetch();
$autoGenerateOn = ($_autoGen && $_autoGen['setting_value'] === '1');

// Floor plan picker config
$fp_id        = 'sched';
$fp_div_key   = 'fp_w_sched';
$fp_default_w = 500;
$fp_buildings = $buildings;
?>

<style>
html, body { margin: 0; padding: 0; overflow: hidden; height: 100%; }
#app {
    display: flex;
    height: calc(100vh - 56px);
    overflow: hidden;
}
/* ── Calendar grid ─────────────────────────────────────── */
.cal-day {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-start;
    padding-top: 6px;
    border-radius: 8px;
    cursor: pointer;
    transition: background .1s;
    min-height: 52px;
    user-select: none;
}
.cal-day:hover                   { background: #eff6ff; }
.cal-day.today                   { background: #dbeafe; }
.cal-day.selected                { background: #2563eb !important; color: #fff; }
.cal-day.selected .cal-dot       { background: #fff !important; }
.cal-day.other-month             { opacity: .3; pointer-events: none; }
.cal-dot {
    width: 5px; height: 5px;
    border-radius: 50%;
    background: #3b82f6;
    margin-top: 3px;
}
/* ── Schedule cards ────────────────────────────────────── */
.sched-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    margin-bottom: 8px;
    transition: border-color .15s, box-shadow .15s;
    overflow: hidden;
}
.sched-card:hover { border-color: #93c5fd; box-shadow: 0 2px 8px rgba(37,99,235,.08); }
.sched-card.bulk-selected { border-color: #3b82f6; background: #eff6ff; box-shadow: 0 0 0 2px rgba(59,130,246,.2); }
.sched-card.inactive { opacity: .55; }
.sched-card .bulk-check { width:16px; height:16px; accent-color:#2563eb; flex-shrink:0; cursor:pointer; }
#bulk-toolbar { transition: all .2s; }
.sched-card-hdr {
    display: flex; align-items: center; gap: 10px; padding: 14px 16px;
    cursor: pointer; user-select: none;
}
.sched-card-hdr:active { background: #f9fafb; }
.sched-expand-icon { transition: transform .2s; color: #9ca3af; flex-shrink: 0; }
.sched-card.open > .sched-card-hdr .sched-expand-icon { transform: rotate(90deg); }
.sched-card-body { display: none; padding: 0 16px 12px; border-top: 1px solid #f3f4f6; }
.sched-card.open > .sched-card-body { display: block; }
.sched-edit-btn {
    background: none; border: 1px solid #d1d5db; border-radius: 6px;
    padding: 3px 8px; font-size: 11px; font-weight: 600; color: #6b7280;
    cursor: pointer; transition: all .12s; flex-shrink: 0;
}
.sched-edit-btn:hover { background: #eff6ff; border-color: #93c5fd; color: #2563eb; }

/* ── Day view mode buttons ──────────────────────────────── */
.dv-mode-btn {
    display:flex; align-items:center; justify-content:center;
    width:28px; height:24px; border:none; background:transparent;
    border-radius:6px; cursor:pointer; color:#9ca3af; transition:all .12s;
}
.dv-mode-btn:hover { background:#e5e7eb; color:#374151; }
.dv-mode-btn.active { background:#fff; color:#2563eb; box-shadow:0 1px 3px rgba(0,0,0,.08); }

/* ── Drag reorder styles ────────────────────────────────── */
.drag-item {
    display:flex; align-items:center; gap:8px; padding:10px 12px;
    background:#fff; border:1px solid #e5e7eb; border-radius:10px;
    margin-bottom:6px; transition:border-color .15s, box-shadow .15s, opacity .15s;
    cursor:default;
}
.drag-item:hover { border-color:#93c5fd; box-shadow:0 2px 8px rgba(37,99,235,.08); }
.drag-handle {
    cursor:grab; color:#d1d5db; flex-shrink:0; padding:2px;
    transition:color .12s; touch-action:none;
}
.drag-handle:hover { color:#6b7280; }
.drag-handle:active { cursor:grabbing; }
.drag-item.dragging { opacity:.4; border-color:#3b82f6; }
.drag-placeholder {
    border:2px dashed #93c5fd; border-radius:10px; background:#eff6ff;
    margin-bottom:6px; transition:height .15s;
}
.sched-task-item {
    display: flex; align-items: center; justify-content: space-between;
    padding: 6px 0; border-bottom: 1px solid #f8fafc; font-size: 13px; color: #374151;
}
.sched-task-item:last-child { border-bottom: none; }
.sched-subgroup { margin-left: 16px; margin-top: 4px; }
.sched-subgroup-hdr {
    display: flex; align-items: center; gap: 8px; padding: 5px 0;
    cursor: pointer; user-select: none; font-size: 13px; font-weight: 600; color: #4b5563;
}
.sched-subgroup-hdr .sched-expand-icon { width: 14px; height: 14px; }
.sched-subgroup-body { display: none; margin-left: 22px; }
.sched-subgroup.open > .sched-subgroup-body { display: block; }
.sched-subgroup.open > .sched-subgroup-hdr .sched-expand-icon { transform: rotate(90deg); }
/* ── Freq badge ────────────────────────────────────────── */
.freq-badge {
    display: inline-block;
    font-size: 11px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 9999px;
    text-transform: uppercase;
    letter-spacing: .04em;
}
.freq-daily        { background: #dbeafe; color: #1e40af; }
.freq-weekdays     { background: #e0e7ff; color: #3730a3; }
.freq-specific_days{ background: #ede9fe; color: #5b21b6; }
.freq-weekly       { background: #d1fae5; color: #065f46; }
.freq-biweekly     { background: #ccfbf1; color: #0f766e; }
.freq-monthly      { background: #fef3c7; color: #92400e; }
.freq-yearly       { background: #fee2e2; color: #991b1b; }
</style>

<div id="app">

    <!-- ── Room Picker (left pane) ─────────────────────────── -->
    <?php require_once __DIR__ . '/../includes/floor_plan_picker.php'; ?>

    <!-- ── Main Content (center) + Task Library (right) ──────── -->
    <div style="flex:1; display:flex; overflow:hidden;">

    <div id="main-pane" style="flex:1; display:flex; flex-direction:column; overflow:hidden;">

        <!-- ── Top bar ─────────────────────────────────────── -->
        <div class="flex items-center justify-between px-4 py-2 bg-white border-b border-gray-200" style="flex-shrink:0;">
            <div class="flex items-center gap-3">
                <h1 class="text-lg font-bold text-gray-800">Cleaning Schedules</h1>
                <span id="room-count-badge" class="text-xs bg-blue-100 text-blue-700 font-semibold px-2 py-0.5 rounded-full hidden">0 rooms</span>
            </div>
            <div class="flex items-center gap-2">
                <?php if (!$autoGenerateOn): ?>
                <button onclick="generateAssignments()" class="border border-gray-300 hover:bg-gray-50 text-gray-600 rounded-lg px-3 py-1.5 text-sm transition flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Generate Assignments
                </button>
                <?php endif; ?>
                <button id="new-sched-btn" onclick="tryOpenScheduleModal()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg px-3 py-1.5 text-sm transition flex items-center gap-1 disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-blue-600" disabled>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    New Schedule
                </button>
            </div>
        </div>

        <!-- ── Calendar + Schedule List ────────────────────── -->
        <div style="flex:1; overflow-y:auto; padding:16px;">

            <!-- Calendar -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-4">
                <div class="flex items-center justify-between mb-3">
                    <button onclick="calNav(-1)" class="text-gray-400 hover:text-gray-700 p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <h2 id="cal-title" class="text-sm font-bold text-gray-700"></h2>
                    <button onclick="calNav(1)" class="text-gray-400 hover:text-gray-700 p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
                <div class="grid grid-cols-7 text-center text-xs font-bold text-gray-400 mb-1">
                    <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
                </div>
                <div id="cal-grid" class="grid grid-cols-7 gap-0.5"></div>
            </div>

            <!-- ── View Toggle ────────────────────────────────── -->
            <div class="flex items-center gap-1 mb-3 bg-gray-100 rounded-lg p-0.5" style="width:fit-content;">
                <button id="toggle-day-view" onclick="switchView('day')" class="px-3 py-1.5 text-xs font-bold rounded-md transition bg-white text-gray-800 shadow-sm">Day View</button>
                <button id="toggle-rule-view" onclick="switchView('rule')" class="px-3 py-1.5 text-xs font-bold rounded-md transition text-gray-500">Rule View</button>
            </div>

            <!-- ══════════ DAY VIEW ══════════ -->
            <div id="day-view-pane">
                <div class="flex items-center justify-between mb-3">
                    <h3 id="day-view-title" class="text-sm font-bold text-gray-700"></h3>
                    <div class="flex items-center gap-2">
                        <!-- View mode toolbar (matches PWA) -->
                        <div class="flex items-center gap-1 bg-gray-100 rounded-lg p-0.5">
                            <button id="dv-mode-default" onclick="setDayViewMode('default')" class="dv-mode-btn active" title="Default view (drag to reorder)">
                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                            </button>
                            <button id="dv-mode-rooms" onclick="setDayViewMode('rooms')" class="dv-mode-btn" title="Group by room">
                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v13a1 1 0 001 1h3m10-11l2 2m-2-2v13a1 1 0 01-1 1h-3m-4 0h4"/></svg>
                            </button>
                            <button id="dv-mode-task" onclick="setDayViewMode('task')" class="dv-mode-btn" title="Group by task type">
                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v13a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                            </button>
                        </div>
                        <select id="day-worker-filter" onchange="renderDayView()" class="border border-gray-300 rounded-lg px-2 py-1 text-xs text-gray-600">
                            <option value="">All Workers</option>
                        </select>
                    </div>
                </div>
                <div id="day-view-list" class="space-y-2 text-sm"></div>
                <div id="day-view-empty" class="text-center text-gray-400 text-sm py-8 hidden">
                    No assignments for this date. Select a date on the calendar, or generate assignments.
                </div>
            </div>

            <!-- ══════════ RULE VIEW ══════════ -->
            <div id="rule-view-pane" class="hidden">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">
                        Schedule Rules <span id="sched-filter-label" class="text-gray-300 normal-case"></span>
                    </h3>
                    <div class="flex items-center gap-3">
                        <select id="rule-worker-filter" onchange="renderSchedules()" class="border border-gray-300 rounded-lg px-2 py-1 text-xs text-gray-600">
                            <option value="">All Workers</option>
                        </select>
                        <label class="flex items-center gap-1 cursor-pointer text-xs text-gray-400 hover:text-gray-600">
                            <input type="checkbox" id="bulk-mode-toggle" onchange="toggleBulkMode(this.checked)" style="width:14px;height:14px;accent-color:#2563eb;">
                            Multi-select
                        </label>
                    </div>
                </div>
                <!-- Bulk toolbar (hidden until 2+ selected) -->
                <div id="bulk-toolbar" class="hidden mb-3 flex items-center gap-2 bg-blue-50 border border-blue-200 rounded-xl px-4 py-2">
                    <span id="bulk-count" class="text-sm font-bold text-blue-700">0 selected</span>
                    <div class="flex-1"></div>
                    <button onclick="bulkSelectAll()" class="text-xs text-blue-600 hover:text-blue-800 font-medium">Select All</button>
                    <button onclick="bulkDeselectAll()" class="text-xs text-gray-500 hover:text-gray-700 font-medium">Deselect All</button>
                    <button onclick="openBulkEditModal()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg px-3 py-1.5 text-xs transition">Edit Selected</button>
                    <button onclick="bulkDeleteSchedules()" class="bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 font-bold rounded-lg px-3 py-1.5 text-xs transition">Delete Selected</button>
                </div>
                <div id="schedule-list"></div>
                <div id="schedule-empty" class="text-center text-gray-400 text-sm py-8 hidden">
                    No schedules match current filters.
                </div>
            </div>
        </div>
    </div>

    <!-- ── Task Library (right panel, 400px) ──────────────── -->
    <div id="task-library" style="width:400px; flex-shrink:0; display:flex; flex-direction:column; border-left:1px solid #e5e7eb; background:#fff; overflow:hidden;">
        <div class="px-4 py-3 border-b border-gray-200" style="flex-shrink:0;">
            <div class="flex items-center justify-between mb-2">
                <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Task Library</h2>
                <button onclick="deselectAllLibrary()" class="text-xs text-blue-600 hover:text-blue-800 font-medium">Deselect All</button>
            </div>
            <input id="task-lib-search" type="text" placeholder="Search tasks…"
                   class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                   oninput="filterTaskLibrary()">
            <label class="flex items-center gap-1.5 mt-2 cursor-pointer text-xs text-gray-500">
                <input type="checkbox" id="task-lib-show-all" onchange="renderTaskLibrary()" style="width:14px;height:14px;accent-color:#2563eb;">
                Show non-reusable tasks
            </label>
            <label class="flex items-center gap-1.5 mt-1 cursor-pointer text-xs text-gray-500">
                <input type="checkbox" id="task-lib-auto-rooms" style="width:14px;height:14px;accent-color:#2563eb;">
                Auto-select rooms with tasks
            </label>
        </div>
        <div style="flex:1; overflow-y:auto; padding:12px 16px;">
            <!-- Task Groups section -->
            <div class="mb-4">
                <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2 flex items-center gap-2">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    Task Groups
                </h3>
                <div id="lib-task-groups" class="space-y-1"></div>
            </div>
            <!-- Individual Tasks section -->
            <div>
                <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2 flex items-center gap-2">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    Individual Tasks
                </h3>
                <div id="lib-tasks" class="space-y-1"></div>
            </div>
        </div>
        <!-- Selection summary -->
        <div id="lib-selection-summary" class="px-4 py-3 border-t border-gray-200 bg-gray-50 text-xs text-gray-500 hidden" style="flex-shrink:0;">
            <span id="lib-selection-count">0 items selected</span>
        </div>
    </div>

    </div><!-- /center+right flex wrapper -->
</div>

<!-- ══════════════════════════════════════════════════════════ -->
<!-- SCHEDULE MODAL                                            -->
<!-- ══════════════════════════════════════════════════════════ -->
<div id="sched-modal" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center hidden">
<div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
    <div class="p-6">
        <h2 id="sched-modal-title" class="text-lg font-bold text-gray-800 mb-4">New Schedule</h2>
        <input type="hidden" id="sf-id" value="0">

        <!-- Frequency -->
        <div class="mb-4">
            <label class="block text-xs font-bold text-gray-500 mb-1">Frequency</label>
            <select id="sf-frequency" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" onchange="handleFreqChange()">
                <option value="daily">Daily</option>
                <option value="weekdays">Weekdays (Mon–Fri)</option>
                <option value="specific_days">Specific Days of Week</option>
                <option value="weekly" selected>Weekly</option>
                <option value="biweekly">Biweekly</option>
                <option value="monthly">Monthly</option>
                <option value="yearly">Yearly</option>
            </select>
        </div>

        <!-- Frequency config (dynamic) -->
        <div id="sf-freq-config" class="mb-4 hidden"></div>

        <!-- Assignment -->
        <div class="mb-4">
            <label class="block text-xs font-bold text-gray-500 mb-1">Assign To</label>
            <div class="flex gap-2 mb-2">
                <label class="flex items-center gap-1 text-sm">
                    <input type="radio" name="sf-assign-type" value="user" checked onchange="handleAssignTypeChange()"> Specific Worker
                </label>
                <label class="flex items-center gap-1 text-sm">
                    <input type="radio" name="sf-assign-type" value="role" onchange="handleAssignTypeChange()"> User Class
                </label>
            </div>
            <div id="sf-assign-user-wrap">
                <select id="sf-assign-user" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">Select worker...</option>
                </select>
            </div>
            <div id="sf-assign-role-wrap" class="hidden">
                <select id="sf-assign-role" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="custodial">Custodial (all custodial users)</option>
                </select>
            </div>
        </div>

        <!-- Deadline time -->
        <div class="mb-4">
            <label class="block text-xs font-bold text-gray-500 mb-1">Deadline Time (optional)</label>
            <input id="sf-deadline" type="time" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
        </div>

        <!-- Task Groups -->
        <div class="mb-4">
            <label class="block text-xs font-bold text-gray-500 mb-1">Task Groups</label>
            <div id="sf-task-groups" class="max-h-32 overflow-y-auto border border-gray-200 rounded-lg p-2 space-y-1"></div>
        </div>

        <!-- Individual Tasks -->
        <div class="mb-4">
            <label class="block text-xs font-bold text-gray-500 mb-1">Individual Tasks</label>
            <div id="sf-tasks" class="max-h-32 overflow-y-auto border border-gray-200 rounded-lg p-2 space-y-1"></div>
        </div>

        <!-- Selected Rooms -->
        <div class="mb-4">
            <label class="block text-xs font-bold text-gray-500 mb-1">Rooms <span class="font-normal text-gray-400">(select from floor plan)</span></label>
            <div id="sf-rooms" class="text-sm text-gray-500 bg-gray-50 rounded-lg p-3 min-h-[40px]">
                No rooms selected — use the floor plan picker on the left
            </div>
        </div>

        <!-- Active toggle -->
        <div class="mb-6 flex items-center gap-2">
            <input id="sf-active" type="checkbox" checked class="rounded">
            <label for="sf-active" class="text-sm text-gray-600">Active (generates assignments)</label>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between">
            <button id="sched-delete-btn" onclick="deleteSchedule()" class="text-red-500 hover:text-red-700 text-sm hidden">Delete Schedule</button>
            <div class="flex gap-2 ml-auto">
                <button onclick="closeScheduleModal()" class="border border-gray-300 hover:bg-gray-50 text-gray-600 rounded-lg px-4 py-2 text-sm transition">Cancel</button>
                <button onclick="saveSchedule()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg px-4 py-2 text-sm transition">Save Schedule</button>
            </div>
        </div>
    </div>
</div>
</div>

<script>
// ═══════════════════════════════════════════════════════════
// STATE
// ═══════════════════════════════════════════════════════════
let selectedRoomIds = new Set();
let schedules = [];
let calendarAssignments = [];
let bulkSelectedIds = new Set(); // multi-select schedule IDs
let bulkEditMode = false; // true when editing multiple schedules via single modal
let bulkDirtyFields = new Set(); // tracks which fields user changed during bulk edit
let lookups = { task_groups: [], tasks: [], workers: [], roles: [] };
let calYear, calMonth; // current calendar view
let selectedDate = null; // currently highlighted calendar day

const today = new Date();
calYear  = today.getFullYear();
calMonth = today.getMonth();

// ═══════════════════════════════════════════════════════════
// INIT
// ═══════════════════════════════════════════════════════════
const picker = new FloorPlanPicker({
    paneId      : 'sched-fp-pane',
    dividerId   : 'sched-fp-divider',
    dividerKey  : 'fp_w_sched',
    defaultWidth: 500,
    linkedGroups: [],
    onChange    : rooms => {
        selectedRoomIds = new Set(Object.keys(rooms).map(Number));
        updateRoomBadge();
        updateModalRooms();
        loadSchedules();
        loadCalendar();
        updateNewScheduleBtn();
    }
});

// Load link groups for picker
fetch(BASE_PATH + '/api/links_api.php?action=get_links')
    .then(r => r.json())
    .then(links => picker.setLinkGroups(links))
    .catch(() => {});

// Auto-select today
selectedDate = `${today.getFullYear()}-${String(today.getMonth()+1).padStart(2,'0')}-${String(today.getDate()).padStart(2,'0')}`;

fetchLookups();
loadCalendar();
loadSchedules();

async function fetchLookups() {
    const r = await fetch(BASE_PATH + '/api/scheduling_api.php?action=get_lookups');
    lookups = await r.json();
    populateWorkerDropdown();
    populateTaskGroupCheckboxes();
    populateTaskCheckboxes();
    renderTaskLibrary();
}

// ═══════════════════════════════════════════════════════════
// ROOM BADGE
// ═══════════════════════════════════════════════════════════
function updateRoomBadge() {
    const badge = document.getElementById('room-count-badge');
    const n = selectedRoomIds.size;
    if (n > 0) {
        badge.textContent = n + ' room' + (n > 1 ? 's' : '');
        badge.classList.remove('hidden');
    } else {
        badge.classList.add('hidden');
    }
    // Filter label is now updated in renderSchedules()
}

// ═══════════════════════════════════════════════════════════
// CALENDAR
// ═══════════════════════════════════════════════════════════
function calNav(dir) {
    calMonth += dir;
    if (calMonth < 0) { calMonth = 11; calYear--; }
    if (calMonth > 11) { calMonth = 0; calYear++; }
    loadCalendar();
}

async function loadCalendar() {
    const startDate = `${calYear}-${String(calMonth+1).padStart(2,'0')}-01`;
    const lastDay = new Date(calYear, calMonth+1, 0).getDate();
    const endDate = `${calYear}-${String(calMonth+1).padStart(2,'0')}-${String(lastDay).padStart(2,'0')}`;

    const roomParam = selectedRoomIds.size > 0 ? `&room_ids=${[...selectedRoomIds].join(',')}` : '';
    const r = await fetch(`${BASE_PATH}/api/scheduling_api.php?action=get_calendar&start_date=${startDate}&end_date=${endDate}${roomParam}`);
    calendarAssignments = await r.json();

    renderCalendar();
    if (currentView === 'day') renderDayView();
}

function renderCalendar() {
    const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    document.getElementById('cal-title').textContent = `${months[calMonth]} ${calYear}`;

    const grid = document.getElementById('cal-grid');
    grid.innerHTML = '';

    const firstDay = new Date(calYear, calMonth, 1).getDay();
    const daysInMonth = new Date(calYear, calMonth+1, 0).getDate();

    // Build date → count map
    const dateCounts = {};
    calendarAssignments.forEach(a => {
        dateCounts[a.assigned_date] = (dateCounts[a.assigned_date] || 0) + 1;
    });

    const todayStr = `${today.getFullYear()}-${String(today.getMonth()+1).padStart(2,'0')}-${String(today.getDate()).padStart(2,'0')}`;

    // Previous month fill
    for (let i = 0; i < firstDay; i++) {
        grid.innerHTML += '<div class="cal-day other-month"></div>';
    }

    for (let d = 1; d <= daysInMonth; d++) {
        const dateStr = `${calYear}-${String(calMonth+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
        const isToday = dateStr === todayStr;
        const isSelected = dateStr === selectedDate;
        const count = dateCounts[dateStr] || 0;

        const div = document.createElement('div');
        div.className = 'cal-day' + (isToday ? ' today' : '') + (isSelected ? ' selected' : '');
        div.setAttribute('data-date', dateStr);
        div.onclick = () => showDayDetail(dateStr, d);
        div.innerHTML = `<span class="text-sm font-medium">${d}</span>${count > 0 ? '<div class="cal-dot"></div>' : ''}`;
        grid.appendChild(div);
    }
}

// ═══════════════════════════════════════════════════════════
// VIEW TOGGLE (Day View / Rule View)
// ═══════════════════════════════════════════════════════════
let currentView = 'day'; // 'day' or 'rule'

function switchView(view) {
    currentView = view;
    const dayBtn  = document.getElementById('toggle-day-view');
    const ruleBtn = document.getElementById('toggle-rule-view');
    const dayPane  = document.getElementById('day-view-pane');
    const rulePane = document.getElementById('rule-view-pane');

    if (view === 'day') {
        dayBtn.className  = 'px-3 py-1.5 text-xs font-bold rounded-md transition bg-white text-gray-800 shadow-sm';
        ruleBtn.className = 'px-3 py-1.5 text-xs font-bold rounded-md transition text-gray-500';
        dayPane.classList.remove('hidden');
        rulePane.classList.add('hidden');
        renderDayView();
    } else {
        ruleBtn.className = 'px-3 py-1.5 text-xs font-bold rounded-md transition bg-white text-gray-800 shadow-sm';
        dayBtn.className  = 'px-3 py-1.5 text-xs font-bold rounded-md transition text-gray-500';
        rulePane.classList.remove('hidden');
        dayPane.classList.add('hidden');
        renderSchedules();
    }
}

// ═══════════════════════════════════════════════════════════
// DAY VIEW
// ═══════════════════════════════════════════════════════════
function showDayDetail(dateStr, dayNum) {
    // Toggle: click same date to deselect
    if (selectedDate === dateStr) {
        selectedDate = null;
        document.querySelectorAll('.cal-day.selected').forEach(el => el.classList.remove('selected'));
        updateNewScheduleBtn();
        if (currentView === 'day') renderDayView();
        if (currentView === 'rule') renderSchedules();
        return;
    }

    selectedDate = dateStr;
    document.querySelectorAll('.cal-day.selected').forEach(el => el.classList.remove('selected'));
    const clicked = document.querySelector(`.cal-day[data-date="${dateStr}"]`);
    if (clicked) clicked.classList.add('selected');
    updateNewScheduleBtn();

    // Stay in current view — just re-render
    if (currentView === 'day') renderDayView();
    if (currentView === 'rule') renderSchedules();
}

let dayViewMode = 'default';

function setDayViewMode(mode) {
    dayViewMode = mode;
    document.querySelectorAll('.dv-mode-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('dv-mode-' + mode).classList.add('active');
    renderDayView();
}

function renderDayView() {
    const list  = document.getElementById('day-view-list');
    const empty = document.getElementById('day-view-empty');
    const title = document.getElementById('day-view-title');

    if (!selectedDate) {
        title.textContent = 'Select a date';
        list.innerHTML = '';
        empty.classList.remove('hidden');
        return;
    }

    const [yr, mo, dy] = selectedDate.split('-').map(Number);
    const monthsShort = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    title.textContent = `${monthsShort[mo-1]} ${dy}, ${yr}`;

    let dayItems = calendarAssignments.filter(a => a.assigned_date === selectedDate);

    // Populate worker filter
    const workerFilter = document.getElementById('day-worker-filter');
    const currentFilterVal = workerFilter.value;
    const workers = {};
    dayItems.forEach(a => {
        const wId = a.worker_id || '0';
        const wName = a.worker_name || 'Unassigned';
        if (!workers[wId]) workers[wId] = wName;
    });
    workerFilter.innerHTML = '<option value="">All Workers</option>';
    Object.entries(workers).sort((a,b) => a[1].localeCompare(b[1])).forEach(([id, name]) => {
        workerFilter.innerHTML += `<option value="${id}" ${id === currentFilterVal ? 'selected' : ''}>${esc(name)}</option>`;
    });
    const filterWorker = workerFilter.value;
    if (filterWorker) {
        dayItems = dayItems.filter(a => String(a.worker_id || '0') === filterWorker);
    }

    if (dayItems.length === 0) {
        list.innerHTML = '';
        empty.classList.remove('hidden');
        return;
    }
    empty.classList.add('hidden');

    if (dayViewMode === 'rooms') return renderDayViewRooms(dayItems);
    if (dayViewMode === 'task') return renderDayViewTask(dayItems);
    renderDayViewDefault(dayItems);
}

// ── Status indicator HTML ──────────────────────────────────
function statusDot(status) {
    if (status === 'completed') return `<span class="w-5 h-5 flex items-center justify-center flex-shrink-0 rounded-full bg-green-100 text-green-600"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></span>`;
    if (status === 'in_progress') return `<span class="w-5 h-5 flex items-center justify-center flex-shrink-0 rounded-full bg-yellow-100 text-yellow-600"><svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="5"/></svg></span>`;
    return `<span class="w-5 h-5 flex items-center justify-center flex-shrink-0 rounded-full bg-gray-100 text-gray-400"><span class="w-2 h-2 rounded-full bg-gray-300"></span></span>`;
}

function assignmentRow(a, roomLabel) {
    return `
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2 min-w-0">
                ${statusDot(a.status)}
                <div class="min-w-0">
                    <span class="text-sm ${a.status === 'completed' ? 'text-gray-400 line-through' : 'text-gray-700'}">${esc(a.task_group_name)}</span>
                    ${roomLabel ? `<div class="text-xs text-gray-400">${esc(roomLabel)}</div>` : ''}
                </div>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <button onclick="event.stopPropagation(); toggleAssignDetail(${a.assignment_id})" class="text-gray-400 hover:text-blue-600 transition text-xs font-medium px-1.5 py-0.5 rounded hover:bg-blue-50" title="View details">View</button>
                <button onclick="event.stopPropagation(); deleteAssignment(${a.assignment_id})" class="text-gray-300 hover:text-red-500 transition" title="Delete assignment">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
            </div>
        </div>
        <div id="assign-detail-${a.assignment_id}" class="hidden mt-2 ml-7 bg-gray-50 rounded-lg p-3 text-xs text-gray-600 space-y-1.5">
            <div class="flex items-center gap-2"><svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                <span class="font-semibold text-gray-500">Room:</span> ${esc(a.room_name)}${a.room_number ? ' #'+esc(a.room_number) : ''}</div>
            <div class="flex items-center gap-2"><svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                <span class="font-semibold text-gray-500">Task:</span> ${esc(a.task_group_name)}</div>
            <div class="flex items-center gap-2"><svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                <span class="font-semibold text-gray-500">Worker:</span> ${esc(a.worker_name || 'Unassigned')}</div>
            <div class="flex items-center gap-2"><svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="font-semibold text-gray-500">Status:</span>
                <span class="font-semibold px-1.5 py-0.5 rounded-full ${a.status==='completed'?'bg-green-100 text-green-700':a.status==='in_progress'?'bg-yellow-100 text-yellow-700':'bg-gray-100 text-gray-500'}">${a.status.replace('_',' ')}</span></div>
        </div>`;
}

// ═══════════════════════════════════════════════════════════
// DEFAULT VIEW — Flat list with drag reorder
// ═══════════════════════════════════════════════════════════
function renderDayViewDefault(dayItems) {
    const list = document.getElementById('day-view-list');
    list.innerHTML = dayItems.map(a => `
        <div class="drag-item" data-assign-id="${a.assignment_id}">
            <div class="drag-handle" title="Drag to reorder">
                <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M7 2a2 2 0 10.001 4.001A2 2 0 007 2zm0 6a2 2 0 10.001 4.001A2 2 0 007 8zm0 6a2 2 0 10.001 4.001A2 2 0 007 14zm6-8a2 2 0 10-.001-4.001A2 2 0 0013 6zm0 2a2 2 0 10.001 4.001A2 2 0 0013 8zm0 6a2 2 0 10.001 4.001A2 2 0 0013 14z"/>
                </svg>
            </div>
            <div style="flex:1;min-width:0;">
                ${assignmentRow(a, a.room_name + (a.room_number ? ' #'+a.room_number : ''))}
            </div>
        </div>
    `).join('');

    initDragReorder(list);
}

// ═══════════════════════════════════════════════════════════
// ROOMS VIEW — Grouped by room (original layout)
// ═══════════════════════════════════════════════════════════
function renderDayViewRooms(dayItems) {
    const list = document.getElementById('day-view-list');
    const byRoom = {};
    dayItems.forEach(a => {
        const key = a.room_id;
        if (!byRoom[key]) byRoom[key] = { room_name: a.room_name, room_number: a.room_number, items: [] };
        byRoom[key].items.push(a);
    });

    list.innerHTML = Object.values(byRoom).map(room => {
        const roomDone = room.items.filter(a => a.status === 'completed').length;
        const roomTotal = room.items.length;
        const allDone = roomDone === roomTotal;
        return `
        <div class="sched-card ${allDone ? 'opacity-60' : ''}" data-room-card>
            <div class="sched-card-hdr" onclick="this.parentElement.classList.toggle('open')">
                <svg class="sched-expand-icon" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <div style="flex:1;min-width:0;">
                    <div class="font-bold text-gray-800 text-sm">${esc(room.room_name)}${room.room_number ? ' <span class="text-gray-400 font-normal">#'+esc(room.room_number)+'</span>' : ''}</div>
                    <div class="text-xs text-gray-400 mt-0.5">${roomDone}/${roomTotal} tasks done</div>
                </div>
                <div class="flex items-center gap-2" style="flex-shrink:0;">
                    ${allDone
                        ? '<span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-green-100 text-green-700">Complete</span>'
                        : `<span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-blue-50 text-blue-600">${roomDone}/${roomTotal}</span>`
                    }
                </div>
            </div>
            <div class="sched-card-body" style="padding:0 16px 12px;">
                ${room.items.map(a => `<div class="py-2 border-b border-gray-50 last:border-0">${assignmentRow(a, null)}</div>`).join('')}
            </div>
        </div>`;
    }).join('');
}

// ═══════════════════════════════════════════════════════════
// TASK TYPE VIEW — Grouped by task type
// ═══════════════════════════════════════════════════════════
function renderDayViewTask(dayItems) {
    const list = document.getElementById('day-view-list');
    const byType = {};
    dayItems.forEach(a => {
        const key = a.task_group_name || 'Untitled';
        if (!byType[key]) byType[key] = [];
        byType[key].push(a);
    });

    list.innerHTML = Object.entries(byType).sort((a,b) => a[0].localeCompare(b[0])).map(([typeName, items]) => {
        const done = items.filter(a => a.status === 'completed').length;
        const total = items.length;
        const allDone = done === total;
        return `
        <div class="sched-card ${allDone ? 'opacity-60' : ''}" data-type-card>
            <div class="sched-card-hdr" onclick="this.parentElement.classList.toggle('open')">
                <svg class="sched-expand-icon" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <div style="flex:1;min-width:0;">
                    <div class="font-bold text-gray-800 text-sm">${esc(typeName)}</div>
                    <div class="text-xs text-gray-400 mt-0.5">${done}/${total} tasks done</div>
                </div>
                <div class="flex items-center gap-2" style="flex-shrink:0;">
                    ${allDone
                        ? '<span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-green-100 text-green-700">Complete</span>'
                        : `<span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-blue-50 text-blue-600">${done}/${total}</span>`
                    }
                </div>
            </div>
            <div class="sched-card-body" style="padding:0 16px 12px;">
                ${items.map(a => `<div class="py-2 border-b border-gray-50 last:border-0">${assignmentRow(a, a.room_name + (a.room_number ? ' #'+a.room_number : ''))}</div>`).join('')}
            </div>
        </div>`;
    }).join('');
}

// ═══════════════════════════════════════════════════════════
// DRAG REORDER ENGINE
// ═══════════════════════════════════════════════════════════
function initDragReorder(container) {
    let dragEl = null;
    let placeholder = null;

    container.querySelectorAll('.drag-handle').forEach(handle => {
        handle.addEventListener('mousedown', startDrag);
        handle.addEventListener('touchstart', startDrag, { passive: false });
    });

    function startDrag(e) {
        e.preventDefault();
        dragEl = e.target.closest('.drag-item');
        if (!dragEl) return;

        const rect = dragEl.getBoundingClientRect();
        dragEl.classList.add('dragging');

        placeholder = document.createElement('div');
        placeholder.className = 'drag-placeholder';
        placeholder.style.height = rect.height + 'px';
        dragEl.parentNode.insertBefore(placeholder, dragEl.nextSibling);

        // Make drag element float
        dragEl.style.position = 'fixed';
        dragEl.style.width = rect.width + 'px';
        dragEl.style.left = rect.left + 'px';
        dragEl.style.top = rect.top + 'px';
        dragEl.style.zIndex = '1000';
        dragEl.style.pointerEvents = 'none';

        const onMove = e.type === 'touchstart' ? 'touchmove' : 'mousemove';
        const onEnd = e.type === 'touchstart' ? 'touchend' : 'mouseup';

        const startY = (e.touches ? e.touches[0] : e).clientY;
        const startTop = rect.top;

        function handleMove(ev) {
            const clientY = (ev.touches ? ev.touches[0] : ev).clientY;
            dragEl.style.top = (startTop + clientY - startY) + 'px';

            // Find the item we're hovering over
            const siblings = [...container.querySelectorAll('.drag-item:not(.dragging), .drag-placeholder')];
            for (const sib of siblings) {
                if (sib === placeholder) continue;
                const sibRect = sib.getBoundingClientRect();
                const mid = sibRect.top + sibRect.height / 2;
                if (clientY < mid) {
                    container.insertBefore(placeholder, sib);
                    return;
                }
            }
            // Past all items → append
            container.appendChild(placeholder);
        }

        function handleEnd() {
            document.removeEventListener(onMove, handleMove);
            document.removeEventListener(onEnd, handleEnd);

            // Insert dragEl where placeholder is
            container.insertBefore(dragEl, placeholder);
            placeholder.remove();

            // Reset styles
            dragEl.classList.remove('dragging');
            dragEl.style.position = '';
            dragEl.style.width = '';
            dragEl.style.left = '';
            dragEl.style.top = '';
            dragEl.style.zIndex = '';
            dragEl.style.pointerEvents = '';

            dragEl = null;
            placeholder = null;

            // Save new order
            saveDragOrder(container);
        }

        document.addEventListener(onMove, handleMove, { passive: false });
        document.addEventListener(onEnd, handleEnd);
    }
}

async function saveDragOrder(container) {
    const items = container.querySelectorAll('.drag-item');
    const orders = [];
    items.forEach((el, idx) => {
        const id = el.getAttribute('data-assign-id');
        orders.push({ id: parseInt(id), sort_order: idx });
    });

    // Update local data so re-renders keep the new order
    const orderMap = {};
    orders.forEach(o => orderMap[o.id] = o.sort_order);
    calendarAssignments.forEach(a => {
        if (orderMap[a.assignment_id] !== undefined) {
            a.sort_order = orderMap[a.assignment_id];
        }
    });
    // Re-sort the local array by the new sort_order for this date
    calendarAssignments.sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0) || a.assigned_date.localeCompare(b.assigned_date));

    try {
        await fetch(BASE_PATH + '/api/tasks_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'reorder_assignments', orders })
        });
    } catch (e) {
        console.error('Failed to save order:', e);
    }
}

function toggleAssignDetail(id) {
    const el = document.getElementById('assign-detail-' + id);
    if (el) el.classList.toggle('hidden');
}

async function deleteAssignment(id) {
    if (!confirm('Delete this assignment?')) return;
    const r = await fetch(BASE_PATH + '/api/scheduling_api.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ action: 'delete_assignment', id })
    });
    const result = await r.json();
    if (result.error) { alert(result.error); return; }
    // Remove from local data and re-render
    calendarAssignments = calendarAssignments.filter(a => a.assignment_id != id);
    renderDayView();
    // Refresh calendar to update dot indicators
    loadCalendar();
}

// ═══════════════════════════════════════════════════════════
// SCHEDULE LIST
// ═══════════════════════════════════════════════════════════
async function loadSchedules() {
    let url;
    if (selectedRoomIds.size > 0) {
        url = `${BASE_PATH}/api/scheduling_api.php?action=get_schedules_for_rooms&room_ids=${[...selectedRoomIds].join(',')}`;
    } else {
        url = BASE_PATH + '/api/scheduling_api.php?action=get_schedules';
    }
    const r = await fetch(url);
    schedules = await r.json();
    renderSchedules();
}

const freqLabels = {
    daily: 'Daily', weekdays: 'Weekdays', specific_days: 'Specific Days',
    weekly: 'Weekly', biweekly: 'Biweekly', monthly: 'Monthly', yearly: 'Yearly'
};
const dayNames = ['','Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
const monthNames = ['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

function freqDetail(s) {
    const cfg = s.frequency_config || {};
    switch (s.frequency) {
        case 'specific_days':
            return (cfg.days || []).map(d => dayNames[d]).join(', ');
        case 'weekly':
        case 'biweekly':
            return dayNames[cfg.day_of_week || 1] + 's';
        case 'monthly':
            return 'Day ' + (cfg.day_of_month || 1);
        case 'yearly':
            return monthNames[cfg.month || 1] + ' ' + (cfg.day || 1);
        default:
            return '';
    }
}

function renderSchedules() {
    const container = document.getElementById('schedule-list');
    const empty = document.getElementById('schedule-empty');

    // Populate rule worker filter dropdown
    const ruleWorkerFilter = document.getElementById('rule-worker-filter');
    const ruleWorkerVal = ruleWorkerFilter.value;
    const ruleWorkers = {};
    schedules.forEach(s => {
        if (s.assign_to_type === 'user' && s.assign_to_user_id) {
            ruleWorkers[s.assign_to_user_id] = s.assigned_user_name || 'Unknown';
        } else if (s.assign_to_type === 'role') {
            ruleWorkers['role:' + s.assign_to_role] = s.assign_to_role + ' (role)';
        } else {
            ruleWorkers['0'] = 'Unassigned';
        }
    });
    ruleWorkerFilter.innerHTML = '<option value="">All Workers</option>';
    Object.entries(ruleWorkers).sort((a,b) => a[1].localeCompare(b[1])).forEach(([id, name]) => {
        ruleWorkerFilter.innerHTML += `<option value="${id}" ${id === ruleWorkerVal ? 'selected' : ''}>${esc(name)}</option>`;
    });

    // Apply filters: task library + calendar date + worker
    let filtered = schedules;

    // Filter by worker
    const ruleFilterWorker = ruleWorkerFilter.value;
    if (ruleFilterWorker) {
        filtered = filtered.filter(s => {
            if (ruleFilterWorker.startsWith('role:')) {
                return s.assign_to_type === 'role' && s.assign_to_role === ruleFilterWorker.replace('role:', '');
            } else if (ruleFilterWorker === '0') {
                return !s.assign_to_user_id && s.assign_to_type !== 'role';
            } else {
                return String(s.assign_to_user_id) === ruleFilterWorker;
            }
        });
    }

    // Filter by task library selections
    if (libSelectedGroups.size > 0 || libSelectedTasks.size > 0) {
        filtered = filtered.filter(s => {
            const sGroupIds = (s.task_groups || []).map(tg => tg.id);
            const sTaskIds  = (s.tasks || []).map(t => t.id);
            const matchesGroup = sGroupIds.some(id => libSelectedGroups.has(id));
            const matchesTask  = sTaskIds.some(id => libSelectedTasks.has(id));
            return matchesGroup || matchesTask;
        });
    }

    // Filter by selected calendar date (match rules whose frequency covers that day)
    if (selectedDate) {
        const dt = new Date(selectedDate + 'T12:00:00');
        const jsDay = dt.getDay(); // 0=Sun..6=Sat
        const isoDay = jsDay === 0 ? 7 : jsDay; // 1=Mon..7=Sun
        const dayOfMonth = dt.getDate();
        const month = dt.getMonth() + 1;

        filtered = filtered.filter(s => {
            const cfg = s.frequency_config || {};
            switch (s.frequency) {
                case 'daily': return true;
                case 'weekdays': return isoDay >= 1 && isoDay <= 5;
                case 'specific_days': return (cfg.days || []).includes(isoDay);
                case 'weekly': return (cfg.day_of_week || 1) === isoDay;
                case 'biweekly': return (cfg.day_of_week || 1) === isoDay; // approximate — can't know week parity
                case 'monthly': return (cfg.day_of_month || 1) === dayOfMonth;
                case 'yearly': return (cfg.month || 1) === month && (cfg.day || 1) === dayOfMonth;
                default: return true;
            }
        });
    }

    // Update filter label
    const filterParts = [];
    if (selectedRoomIds.size > 0) filterParts.push(`${selectedRoomIds.size} room${selectedRoomIds.size>1?'s':''}`);
    if (libSelectedGroups.size > 0 || libSelectedTasks.size > 0) {
        const n = libSelectedGroups.size + libSelectedTasks.size;
        filterParts.push(`${n} task${n>1?'s':''}`);
    }
    if (selectedDate) filterParts.push(selectedDate);
    document.getElementById('sched-filter-label').textContent = filterParts.length > 0 ? `(${filterParts.join(', ')})` : '';

    if (filtered.length === 0) {
        container.innerHTML = '';
        empty.classList.remove('hidden');
        return;
    }
    empty.classList.add('hidden');

    container.innerHTML = filtered.map(s => {
        const detail = freqDetail(s);
        const roomNames = (s.rooms || []).map(r => esc(r.name)).join(', ');
        const tgNames = (s.task_groups || []).map(t => esc(t.name)).join(', ');
        const tNames  = (s.tasks || []).map(t => esc(t.name)).join(', ');
        const allTaskNames = [tgNames, tNames].filter(Boolean).join(', ');
        const assignee = s.assign_to_type === 'role'
            ? `<span class="bg-green-100 text-green-700 text-xs font-semibold px-2 py-0.5 rounded-full capitalize">${esc(s.assign_to_role)}</span>`
            : esc(s.assigned_user_name || 'Unassigned');

        // Does this rule have expandable content?
        const hasGroups = (s.task_groups || []).length > 0;
        const hasTasks  = (s.tasks || []).length > 0;
        const expandable = hasGroups || hasTasks;
        const chevron = expandable ? `<svg class="sched-expand-icon" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>` : '<div style="width:16px;"></div>';

        // Store group/task IDs as data attributes for lazy loading
        const tgIds = (s.task_groups || []).map(tg => tg.id).join(',');
        const tIds  = (s.tasks || []).map(t => t.id).join(',');

        const bulkOn = document.getElementById('bulk-mode-toggle')?.checked;
        return `
        <div class="sched-card ${s.is_active ? '' : 'inactive'} ${bulkSelectedIds.has(s.id) ? 'bulk-selected' : ''}" data-sched-id="${s.id}" data-tg-ids="${tgIds}" data-t-ids="${tIds}">
            <div class="sched-card-hdr" onclick="${expandable ? `toggleSchedCard(this.parentElement)` : `editSchedule(${s.id})`}">
                <input type="checkbox" class="bulk-check ${bulkOn ? '' : 'hidden'}" ${bulkSelectedIds.has(s.id) ? 'checked' : ''} onclick="event.stopPropagation(); toggleBulkSelect(${s.id}, this.checked)">
                ${chevron}
                <div style="flex:1;min-width:0;">
                    <div class="font-bold text-gray-800 text-sm" style="line-height:1.3;">${allTaskNames || '<em class="text-gray-400">No tasks</em>'}</div>
                    <div class="flex items-center gap-3 mt-1 text-xs text-gray-500">
                        <span>${roomNames || '<em>No rooms</em>'}</span>
                        <span>${assignee}</span>
                        ${s.deadline_time ? `<span>by ${s.deadline_time.substring(0,5)}</span>` : ''}
                    </div>
                </div>
                <div class="flex items-center gap-2" style="flex-shrink:0;">
                    <span class="freq-badge freq-${s.frequency}">${freqLabels[s.frequency]}${detail ? ' · '+detail : ''}</span>
                    ${!s.is_active ? '<span class="text-xs text-gray-400 font-semibold">PAUSED</span>' : ''}
                    <button class="sched-edit-btn" onclick="event.stopPropagation(); editSchedule(${s.id})">Edit</button>
                </div>
            </div>
            ${expandable ? `<div class="sched-card-body"><div class="sched-card-body-inner" style="padding:8px 0;"><span class="text-xs text-gray-400">Loading...</span></div></div>` : ''}
        </div>`;
    }).join('');
}

function toggleSchedCard(el) {
    const wasOpen = el.classList.contains('open');
    el.classList.toggle('open');

    // Lazy-load content on first expand
    if (!wasOpen && !el.dataset.loaded) {
        el.dataset.loaded = '1';
        const inner = el.querySelector('.sched-card-body-inner');
        const tgIds = (el.dataset.tgIds || '').split(',').filter(Boolean);
        const tIds  = (el.dataset.tIds || '').split(',').filter(Boolean);

        // Fetch group trees and individual task names
        const promises = [];
        tgIds.forEach(id => {
            promises.push(
                fetch(`${BASE_PATH}/api/tasks_api.php?action=get_group_tree&group_id=${id}`)
                    .then(r => r.json())
                    .then(tree => ({ type: 'group', data: tree }))
            );
        });
        tIds.forEach(id => {
            promises.push(
                fetch(`${BASE_PATH}/api/tasks_api.php?action=get_task&id=${id}`)
                    .then(r => r.json())
                    .then(task => ({ type: 'task', data: task }))
            );
        });

        Promise.all(promises).then(results => {
            let html = '';
            results.forEach(r => {
                if (r.type === 'group' && r.data && !r.data.error) {
                    html += renderGroupTree(r.data, 0);
                } else if (r.type === 'task' && r.data && !r.data.error) {
                    html += `<div class="sched-task-item">
                        <span>${esc(r.data.name)}</span>
                    </div>`;
                }
            });
            inner.innerHTML = html || '<span class="text-xs text-gray-400">No content</span>';
        });
    }
}

function renderGroupTree(group, depth) {
    const indent = depth * 16;
    const hasChildren = (group.children || []).length > 0;
    const hasTasks = (group.tasks || []).length > 0;
    const expandable = hasChildren || hasTasks;
    const chevronSm = `<svg class="sched-expand-icon" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>`;

    let html = '';
    if (expandable) {
        html += `<div class="sched-subgroup" style="margin-left:${indent}px;">`;
        html += `<div class="sched-subgroup-hdr" onclick="this.parentElement.classList.toggle('open')">`;
        html += `${chevronSm} <span style="flex:1;">${esc(group.name)}</span>`;
        const totalTasks = (group.tasks || []).length;
        const totalChildren = (group.children || []).length;
        const meta = [];
        if (totalTasks) meta.push(`${totalTasks} task${totalTasks !== 1 ? 's' : ''}`);
        if (totalChildren) meta.push(`${totalChildren} sub-group${totalChildren !== 1 ? 's' : ''}`);
        html += `<span style="font-size:11px;color:#9ca3af;">${meta.join(', ')}</span>`;
        html += `</div>`;
        html += `<div class="sched-subgroup-body">`;

        // Child groups first
        (group.children || []).forEach(child => {
            html += renderGroupTree(child, 0);
        });

        // Then tasks
        (group.tasks || []).forEach(t => {
            html += `<div class="sched-task-item"><span>${esc(t.name)}</span></div>`;
        });

        html += `</div></div>`;
    } else {
        // Leaf group with no tasks or children — just show the name
        html += `<div class="sched-task-item" style="margin-left:${indent}px;font-weight:600;color:#4b5563;">
            <span>${esc(group.name)}</span>
        </div>`;
    }
    return html;
}

// ═══════════════════════════════════════════════════════════
// TASK LIBRARY (right panel)
// ═══════════════════════════════════════════════════════════
let libSelectedGroups = new Set();
let libSelectedTasks  = new Set();

function renderTaskLibrary() {
    const grpDiv = document.getElementById('lib-task-groups');
    const taskDiv = document.getElementById('lib-tasks');
    const showAll = document.getElementById('task-lib-show-all').checked;

    if (lookups.task_groups.length === 0) {
        grpDiv.innerHTML = '<p class="text-gray-400 text-xs py-2">No task groups defined.</p>';
    } else {
        grpDiv.innerHTML = lookups.task_groups.map(tg => `
            <label class="lib-item flex items-center gap-2 p-2 rounded-lg hover:bg-blue-50 cursor-pointer transition" data-name="${esc(tg.name).toLowerCase()}">
                <input type="checkbox" class="lib-tg-check rounded text-blue-600" value="${tg.id}"
                       onchange="toggleLibGroup(${tg.id}, this.checked)" ${libSelectedGroups.has(tg.id)?'checked':''}>
                <div class="flex-1 min-w-0">
                    <span class="text-sm font-medium text-gray-700 block truncate">${esc(tg.name)}</span>
                    <span class="text-xs text-gray-400">${tg.task_count || 0} tasks · ${tg.estimated_minutes}min</span>
                </div>
            </label>
        `).join('');
    }

    // Filter tasks by reusable flag unless "Show non-reusable" is checked
    const filteredTasks = (lookups.tasks || []).filter(t => showAll || t.reusable == 1);

    if (filteredTasks.length === 0) {
        taskDiv.innerHTML = '<p class="text-gray-400 text-xs py-2">No individual tasks defined.</p>';
    } else {
        taskDiv.innerHTML = filteredTasks.map(t => {
            const reusableTag = t.reusable == 0 ? ' <span style="font-size:9px;color:#9ca3af;font-style:italic;">(non-reusable)</span>' : '';
            return `
            <label class="lib-item flex items-center gap-2 p-2 rounded-lg hover:bg-blue-50 cursor-pointer transition" data-name="${esc(t.name).toLowerCase()}">
                <input type="checkbox" class="lib-task-check rounded text-blue-600" value="${t.id}"
                       onchange="toggleLibTask(${t.id}, this.checked)" ${libSelectedTasks.has(t.id)?'checked':''}>
                <div class="flex-1 min-w-0">
                    <span class="text-sm font-medium text-gray-700 block truncate">${esc(t.name)}${reusableTag}</span>
                    <span class="text-xs text-gray-400">${esc(t.type_name || '')}</span>
                </div>
            </label>`;
        }).join('');
    }

    updateLibSummary();
}

function isAutoRoomsEnabled() {
    return document.getElementById('task-lib-auto-rooms')?.checked ?? true;
}

function toggleLibGroup(id, checked) {
    if (checked) {
        libSelectedGroups.add(id);
        // Auto-select rooms associated with this task group
        if (isAutoRoomsEnabled()) {
            const tg = lookups.task_groups.find(g => g.id == id);
            if (tg && tg.room_ids && tg.room_ids.length > 0) {
                autoSelectRooms(tg.room_ids);
            }
        }
    } else {
        libSelectedGroups.delete(id);
    }
    updateLibSummary();
    updateNewScheduleBtn();
    if (currentView === 'rule') renderSchedules();
}

function toggleLibTask(id, checked) {
    if (checked) {
        libSelectedTasks.add(id);
        // Auto-select rooms associated with this task
        if (isAutoRoomsEnabled()) {
            const t = (lookups.tasks || []).find(t => t.id == id);
            if (t && t.room_ids && t.room_ids.length > 0) {
                autoSelectRooms(t.room_ids);
            }
        }
    } else {
        libSelectedTasks.delete(id);
    }
    updateLibSummary();
    updateNewScheduleBtn();
    if (currentView === 'rule') renderSchedules();
}

function autoSelectRooms(roomIds) {
    // Add to current selection without removing existing ones
    let changed = false;
    roomIds.forEach(rid => {
        if (!selectedRoomIds.has(rid)) {
            selectedRoomIds.add(rid);
            changed = true;
        }
    });
    if (changed) {
        picker.selectRooms([...selectedRoomIds]);
    }
}

function updateLibSummary() {
    const total = libSelectedGroups.size + libSelectedTasks.size;
    const summary = document.getElementById('lib-selection-summary');
    const count = document.getElementById('lib-selection-count');
    if (total > 0) {
        const parts = [];
        if (libSelectedGroups.size) parts.push(`${libSelectedGroups.size} group${libSelectedGroups.size>1?'s':''}`);
        if (libSelectedTasks.size) parts.push(`${libSelectedTasks.size} task${libSelectedTasks.size>1?'s':''}`);
        count.textContent = parts.join(', ') + ' selected';
        summary.classList.remove('hidden');
    } else {
        summary.classList.add('hidden');
    }
}

function deselectAllLibrary() {
    libSelectedGroups.clear();
    libSelectedTasks.clear();
    document.querySelectorAll('.lib-tg-check, .lib-task-check').forEach(cb => cb.checked = false);
    updateLibSummary();
    updateNewScheduleBtn();
    if (currentView === 'rule') renderSchedules();
}

function filterTaskLibrary() {
    const q = document.getElementById('task-lib-search').value.toLowerCase();
    document.querySelectorAll('.lib-item').forEach(el => {
        el.style.display = el.dataset.name.includes(q) ? '' : 'none';
    });
}

// ═══════════════════════════════════════════════════════════
// MODAL — POPULATE DROPDOWNS
// ═══════════════════════════════════════════════════════════
function populateWorkerDropdown() {
    const sel = document.getElementById('sf-assign-user');
    sel.innerHTML = '<option value="">Select worker...</option>';
    lookups.workers.forEach(w => {
        sel.innerHTML += `<option value="${w.id}">${esc(w.name)} (${w.role})</option>`;
    });
}

function populateTaskGroupCheckboxes() {
    const div = document.getElementById('sf-task-groups');
    if (lookups.task_groups.length === 0) {
        div.innerHTML = '<p class="text-gray-400 text-xs">No task groups defined. Create them on the Tasks page first.</p>';
        return;
    }
    div.innerHTML = lookups.task_groups.map(tg => `
        <label class="flex items-center gap-2 p-1 rounded hover:bg-gray-50 cursor-pointer">
            <input type="checkbox" class="tg-check rounded" value="${tg.id}">
            <span class="text-sm text-gray-700">${esc(tg.name)}</span>
            <span class="text-xs text-gray-400 ml-auto">${tg.estimated_minutes}min</span>
        </label>
    `).join('');
}

function populateTaskCheckboxes() {
    const div = document.getElementById('sf-tasks');
    if ((lookups.tasks || []).length === 0) {
        div.innerHTML = '<p class="text-gray-400 text-xs">No individual tasks defined.</p>';
        return;
    }
    div.innerHTML = lookups.tasks.map(t => `
        <label class="flex items-center gap-2 p-1 rounded hover:bg-gray-50 cursor-pointer">
            <input type="checkbox" class="task-check rounded" value="${t.id}">
            <span class="text-sm text-gray-700">${esc(t.name)}</span>
            <span class="text-xs text-gray-400 ml-auto">${esc(t.type_name || '')}</span>
        </label>
    `).join('');
}

// ═══════════════════════════════════════════════════════════
// MODAL — OPEN / CLOSE / POPULATE
// ═══════════════════════════════════════════════════════════
// ═══════════════════════════════════════════════════════════
// NEW SCHEDULE BUTTON GATING
// ═══════════════════════════════════════════════════════════
function updateNewScheduleBtn() {
    const btn = document.getElementById('new-sched-btn');
    const hasRooms = selectedRoomIds.size > 0;
    const hasDate  = !!selectedDate;
    const hasTasks = libSelectedGroups.size > 0 || libSelectedTasks.size > 0;
    const ready = hasRooms && hasDate && hasTasks;
    btn.disabled = !ready;

    // Build tooltip showing what's missing
    if (ready) {
        btn.title = '';
    } else {
        const missing = [];
        if (!hasRooms) missing.push('a room');
        if (!hasDate)  missing.push('a date');
        if (!hasTasks) missing.push('a task or task group');
        btn.title = 'Select ' + missing.join(', ') + ' first';
    }
}

function tryOpenScheduleModal() {
    const hasRooms = selectedRoomIds.size > 0;
    const hasDate  = !!selectedDate;
    const hasTasks = libSelectedGroups.size > 0 || libSelectedTasks.size > 0;
    if (!hasRooms || !hasDate || !hasTasks) {
        const missing = [];
        if (!hasRooms) missing.push('at least one room');
        if (!hasDate)  missing.push('a date on the calendar');
        if (!hasTasks) missing.push('at least one task or task group');
        alert('Please select ' + missing.join(', ') + ' before creating a schedule.');
        return;
    }
    openScheduleModal();
}

function openScheduleModal(data) {
    document.getElementById('sched-modal').classList.remove('hidden');

    if (bulkEditMode) {
        // ── Bulk edit: all fields blank, only apply what user changes ──
        const n = bulkSelectedIds.size;
        document.getElementById('sched-modal-title').textContent = `Edit ${n} Schedule${n > 1 ? 's' : ''}`;
        document.getElementById('sched-delete-btn').classList.add('hidden');
        document.getElementById('sf-id').value = 0;

        // Track which fields are touched
        bulkDirtyFields = new Set();

        // Frequency — add "No change" placeholder
        const sel = document.getElementById('sf-frequency');
        if (!sel.querySelector('option[value="__nochange__"]')) {
            const opt = document.createElement('option');
            opt.value = '__nochange__';
            opt.textContent = '— No change —';
            sel.insertBefore(opt, sel.firstChild);
        }
        sel.value = '__nochange__';
        sel.addEventListener('change', () => bulkDirtyFields.add('frequency'), { once: false });

        // Frequency config — hide
        document.getElementById('sf-freq-config').innerHTML = '';
        document.getElementById('sf-freq-config').classList.add('hidden');

        // Assignment — blank
        document.querySelectorAll('[name="sf-assign-type"]').forEach(r => {
            r.checked = r.value === 'user';
            r.addEventListener('change', () => bulkDirtyFields.add('assign'));
        });
        handleAssignTypeChange();
        const userSel = document.getElementById('sf-assign-user');
        userSel.value = '';
        userSel.addEventListener('change', () => bulkDirtyFields.add('assign'));
        const roleSel = document.getElementById('sf-assign-role');
        roleSel.value = 'custodial';
        roleSel.addEventListener('change', () => bulkDirtyFields.add('assign'));

        // Deadline — blank
        const deadlineEl = document.getElementById('sf-deadline');
        deadlineEl.value = '';
        deadlineEl.addEventListener('change', () => bulkDirtyFields.add('deadline'));

        // Active — checked by default, track
        const activeEl = document.getElementById('sf-active');
        activeEl.checked = true;
        activeEl.addEventListener('change', () => bulkDirtyFields.add('active'));

        // Task groups — all unchecked
        document.querySelectorAll('.tg-check').forEach(cb => {
            cb.checked = false;
            cb.indeterminate = false;
            cb.addEventListener('change', () => bulkDirtyFields.add('tasks'));
        });

        // Individual tasks — all unchecked
        document.querySelectorAll('.task-check').forEach(cb => {
            cb.checked = false;
            cb.indeterminate = false;
            cb.addEventListener('change', () => bulkDirtyFields.add('tasks'));
        });

        updateModalRooms();
        return;
    }

    // ── Single edit / new ──
    document.getElementById('sched-modal-title').textContent = data ? 'Edit Schedule' : 'New Schedule';
    document.getElementById('sched-delete-btn').classList.toggle('hidden', !data);

    // Remove "Multiple" option if it exists from a prior bulk edit
    const multiOpt = document.getElementById('sf-frequency').querySelector('option[value="__multiple__"]');
    if (multiOpt) multiOpt.remove();

    // Reset
    document.getElementById('sf-id').value = data ? data.id : 0;
    document.getElementById('sf-frequency').value = data ? data.frequency : 'weekly';
    document.getElementById('sf-deadline').value = data && data.deadline_time ? data.deadline_time.substring(0,5) : '';
    document.getElementById('sf-active').checked = data ? !!parseInt(data.is_active) : true;

    // Assignment
    const assignType = data ? data.assign_to_type : 'user';
    document.querySelectorAll('[name="sf-assign-type"]').forEach(r => r.checked = r.value === assignType);
    handleAssignTypeChange();
    document.getElementById('sf-assign-user').value = data && data.assign_to_user_id ? data.assign_to_user_id : '';
    document.getElementById('sf-assign-role').value = data && data.assign_to_role ? data.assign_to_role : 'custodial';

    // Task groups — from editing data OR from library selection
    document.querySelectorAll('.tg-check').forEach(cb => {
        cb.indeterminate = false;
        if (data) {
            cb.checked = (data.task_groups || []).some(tg => tg.id == cb.value);
        } else {
            cb.checked = libSelectedGroups.has(parseInt(cb.value));
        }
    });

    // Individual tasks — from editing data OR from library selection
    document.querySelectorAll('.task-check').forEach(cb => {
        cb.indeterminate = false;
        if (data) {
            cb.checked = (data.tasks || []).some(t => t.id == cb.value);
        } else {
            cb.checked = libSelectedTasks.has(parseInt(cb.value));
        }
    });

    // Frequency config
    handleFreqChange(data);

    // Rooms — use current selection, or if editing, select the schedule's rooms
    if (data && data.rooms) {
        // Optionally highlight that rooms come from the picker
    }
    updateModalRooms();
}

function closeScheduleModal() {
    document.getElementById('sched-modal').classList.add('hidden');
    bulkEditMode = false;
    bulkDirtyFields = new Set();
    // Remove temporary placeholder options if present
    const multiOpt = document.getElementById('sf-frequency').querySelector('option[value="__multiple__"]');
    if (multiOpt) multiOpt.remove();
    const noChangeOpt = document.getElementById('sf-frequency').querySelector('option[value="__nochange__"]');
    if (noChangeOpt) noChangeOpt.remove();
}

function updateModalRooms() {
    const div = document.getElementById('sf-rooms');
    if (selectedRoomIds.size === 0) {
        div.innerHTML = '<span class="text-gray-400">No rooms selected — use the floor plan picker on the left</span>';
        return;
    }
    // We need room names — pull from picker state
    const roomMap = picker.getSelection();
    const names = Object.values(roomMap).map(r => `<span class="inline-block bg-blue-50 text-blue-700 text-xs font-medium px-2 py-1 rounded-full mr-1 mb-1">${esc(r.name || r.room_number || 'Room '+r.id)}</span>`);
    div.innerHTML = names.join('') || `<span class="text-blue-600 font-medium">${selectedRoomIds.size} room(s) selected</span>`;
}

function editSchedule(id) {
    const s = schedules.find(x => x.id === id);
    if (!s) return;

    // Select the schedule's rooms in the picker
    if (s.rooms && s.rooms.length > 0) {
        const roomIds = s.rooms.map(r => r.id);
        picker.selectRooms(roomIds);
        selectedRoomIds = new Set(roomIds);
        updateRoomBadge();
    }

    openScheduleModal(s);
}

// ═══════════════════════════════════════════════════════════
// MODAL — FREQUENCY CONFIG
// ═══════════════════════════════════════════════════════════
function handleFreqChange(data) {
    const freq = document.getElementById('sf-frequency').value;
    const div = document.getElementById('sf-freq-config');
    const cfg = data && data.frequency_config ? data.frequency_config : {};

    switch (freq) {
        case 'specific_days': {
            const days = cfg.days || [];
            div.innerHTML = `
                <label class="block text-xs font-bold text-gray-500 mb-1">Select Days</label>
                <div class="flex gap-2 flex-wrap">
                    ${[['1','Mon'],['2','Tue'],['3','Wed'],['4','Thu'],['5','Fri'],['6','Sat'],['7','Sun']].map(([v,l]) =>
                        `<label class="flex items-center gap-1 text-sm"><input type="checkbox" class="dow-check rounded" value="${v}" ${days.includes(parseInt(v))?'checked':''}> ${l}</label>`
                    ).join('')}
                </div>`;
            div.classList.remove('hidden');
            break;
        }
        case 'weekly':
        case 'biweekly': {
            const val = cfg.day_of_week || 1;
            div.innerHTML = `
                <label class="block text-xs font-bold text-gray-500 mb-1">Day of Week</label>
                <select id="sf-dow" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    ${[['1','Monday'],['2','Tuesday'],['3','Wednesday'],['4','Thursday'],['5','Friday'],['6','Saturday'],['7','Sunday']].map(([v,l]) =>
                        `<option value="${v}" ${parseInt(v)===val?'selected':''}>${l}</option>`
                    ).join('')}
                </select>`;
            div.classList.remove('hidden');
            break;
        }
        case 'monthly': {
            const val = cfg.day_of_month || 1;
            div.innerHTML = `
                <label class="block text-xs font-bold text-gray-500 mb-1">Day of Month</label>
                <input id="sf-dom" type="number" min="1" max="28" value="${val}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-24">`;
            div.classList.remove('hidden');
            break;
        }
        case 'yearly': {
            const m = cfg.month || 1;
            const d = cfg.day || 1;
            div.innerHTML = `
                <label class="block text-xs font-bold text-gray-500 mb-1">Month & Day</label>
                <div class="flex gap-2">
                    <select id="sf-month" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        ${[1,2,3,4,5,6,7,8,9,10,11,12].map(i =>
                            `<option value="${i}" ${i===m?'selected':''}>${monthNames[i]}</option>`
                        ).join('')}
                    </select>
                    <input id="sf-year-day" type="number" min="1" max="31" value="${d}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-20">
                </div>`;
            div.classList.remove('hidden');
            break;
        }
        default:
            div.innerHTML = '';
            div.classList.add('hidden');
    }
}

function handleAssignTypeChange() {
    const type = document.querySelector('[name="sf-assign-type"]:checked').value;
    document.getElementById('sf-assign-user-wrap').classList.toggle('hidden', type !== 'user');
    document.getElementById('sf-assign-role-wrap').classList.toggle('hidden', type !== 'role');
}

// ═══════════════════════════════════════════════════════════
// MODAL — SAVE
// ═══════════════════════════════════════════════════════════
async function saveSchedule() {
    const freq = document.getElementById('sf-frequency').value;
    let freqConfig = null;

    // Don't collect freq config if "Multiple" placeholder is still selected
    if (freq !== '__multiple__') {
        switch (freq) {
            case 'specific_days':
                freqConfig = { days: [...document.querySelectorAll('.dow-check:checked')].map(c => parseInt(c.value)) };
                break;
            case 'weekly':
            case 'biweekly':
                freqConfig = { day_of_week: parseInt(document.getElementById('sf-dow')?.value || 1) };
                break;
            case 'monthly':
                freqConfig = { day_of_month: parseInt(document.getElementById('sf-dom')?.value || 1) };
                break;
            case 'yearly':
                freqConfig = { month: parseInt(document.getElementById('sf-month')?.value || 1), day: parseInt(document.getElementById('sf-year-day')?.value || 1) };
                break;
        }
    }

    const assignType = document.querySelector('[name="sf-assign-type"]:checked').value;
    const taskGroupIds = [...document.querySelectorAll('.tg-check:checked')].map(c => parseInt(c.value));
    const taskIds = [...document.querySelectorAll('.task-check:checked')].map(c => parseInt(c.value));

    if (!bulkEditMode && taskGroupIds.length === 0 && taskIds.length === 0) {
        alert('Select at least one task group or individual task.');
        return;
    }

    // ── Bulk edit mode: call bulk_update API with only changed fields ──
    if (bulkEditMode) {
        const ids = [...bulkSelectedIds];
        const updates = {};

        if (bulkDirtyFields.size === 0) {
            alert('No changes made. Modify at least one field to apply.');
            return;
        }

        if (bulkDirtyFields.has('frequency') && freq !== '__nochange__') {
            updates.frequency = freq;
            updates.frequency_config = freqConfig;
        }

        if (bulkDirtyFields.has('assign')) {
            updates.assign_to_type = assignType;
            updates.assign_to_user_id = assignType === 'user' ? (document.getElementById('sf-assign-user').value || null) : null;
            updates.assign_to_role = assignType === 'role' ? document.getElementById('sf-assign-role').value : null;
        }

        if (bulkDirtyFields.has('deadline')) {
            updates.deadline_time = document.getElementById('sf-deadline').value || null;
        }

        if (bulkDirtyFields.has('active')) {
            updates.is_active = document.getElementById('sf-active').checked ? 1 : 0;
        }

        if (bulkDirtyFields.has('tasks')) {
            updates.task_group_ids = taskGroupIds;
            updates.task_ids = taskIds;
        }

        const r = await fetch(BASE_PATH + '/api/scheduling_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ action: 'bulk_update', ids, updates })
        });
        const result = await r.json();
        if (result.error) { alert(result.error); return; }

        closeScheduleModal();
        bulkDeselectAll();
        loadSchedules();
        loadCalendar();
        return;
    }

    // ── Single save ──
    const body = {
        action: 'save_schedule',
        id: parseInt(document.getElementById('sf-id').value),
        frequency: freq,
        frequency_config: freqConfig,
        assign_to_type: assignType,
        assign_to_user_id: assignType === 'user' ? (document.getElementById('sf-assign-user').value || null) : null,
        assign_to_role: assignType === 'role' ? document.getElementById('sf-assign-role').value : null,
        deadline_time: document.getElementById('sf-deadline').value || null,
        is_active: document.getElementById('sf-active').checked ? 1 : 0,
        room_ids: [...selectedRoomIds],
        task_group_ids: taskGroupIds,
        task_ids: taskIds,
    };

    const r = await fetch(BASE_PATH + '/api/scheduling_api.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(body)
    });
    const result = await r.json();
    if (result.error) { alert(result.error); return; }

    closeScheduleModal();
    loadSchedules();
    loadCalendar();
}

// ═══════════════════════════════════════════════════════════
// DELETE SCHEDULE
// ═══════════════════════════════════════════════════════════
async function deleteSchedule() {
    if (!confirm('Delete this schedule? Pending future assignments will be removed. Completed or in-progress work is preserved.')) return;
    const id = parseInt(document.getElementById('sf-id').value);
    await fetch(BASE_PATH + '/api/scheduling_api.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ action: 'delete_schedule', id })
    });
    closeScheduleModal();
    loadSchedules();
    loadCalendar();
}

// ═══════════════════════════════════════════════════════════
// GENERATE ASSIGNMENTS
// ═══════════════════════════════════════════════════════════
async function generateAssignments() {
    const btn = event.target.closest('button');
    btn.disabled = true;
    btn.textContent = 'Generating...';

    const r = await fetch(BASE_PATH + '/api/scheduling_api.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ action: 'generate_assignments' })
    });
    const result = await r.json();
    btn.disabled = false;
    btn.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Generate Assignments`;

    if (result.error) { alert(result.error); return; }
    alert(`Generated ${result.created} new assignments (${result.skipped} already existed) for the next ${result.days_ahead} days.`);
    loadCalendar();
}

// ═══════════════════════════════════════════════════════════
// BULK MULTI-SELECT
// ═══════════════════════════════════════════════════════════

function toggleBulkMode(on) {
    document.querySelectorAll('.bulk-check').forEach(cb => {
        cb.classList.toggle('hidden', !on);
    });
    if (!on) {
        bulkSelectedIds.clear();
        updateBulkUI();
        document.querySelectorAll('.sched-card').forEach(c => c.classList.remove('bulk-selected'));
        document.querySelectorAll('.bulk-check').forEach(cb => cb.checked = false);
    }
}

function toggleBulkSelect(id, checked) {
    if (checked) bulkSelectedIds.add(id);
    else bulkSelectedIds.delete(id);

    const card = document.querySelector(`.sched-card[data-sched-id="${id}"]`);
    if (card) card.classList.toggle('bulk-selected', checked);
    updateBulkUI();
}

function bulkSelectAll() {
    schedules.forEach(s => bulkSelectedIds.add(s.id));
    document.querySelectorAll('.bulk-check').forEach(cb => { cb.checked = true; });
    document.querySelectorAll('.sched-card').forEach(c => c.classList.add('bulk-selected'));
    updateBulkUI();
}

function bulkDeselectAll() {
    bulkSelectedIds.clear();
    document.querySelectorAll('.bulk-check').forEach(cb => { cb.checked = false; });
    document.querySelectorAll('.sched-card').forEach(c => c.classList.remove('bulk-selected'));
    updateBulkUI();
}

function updateBulkUI() {
    const n = bulkSelectedIds.size;
    const toolbar = document.getElementById('bulk-toolbar');
    toolbar.classList.toggle('hidden', n === 0);
    document.getElementById('bulk-count').textContent = `${n} selected`;
}

function openBulkEditModal() {
    if (bulkSelectedIds.size === 0) return;
    bulkEditMode = true;
    openScheduleModal(null);
}

async function bulkDeleteSchedules() {
    if (bulkSelectedIds.size === 0) return;
    if (!confirm(`Delete ${bulkSelectedIds.size} schedule(s)? Pending future assignments will be removed.`)) return;

    const r = await fetch(BASE_PATH + '/api/scheduling_api.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ action: 'bulk_delete', ids: [...bulkSelectedIds] })
    });
    const result = await r.json();
    if (result.error) { alert(result.error); return; }

    bulkDeselectAll();
    loadSchedules();
    loadCalendar();
}

</script>
</body>
</html>
