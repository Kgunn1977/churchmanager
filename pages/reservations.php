<?php
$pageTitle = 'Reservations — Church Facility Manager';
require_once __DIR__ . '/../includes/nav.php';
require_once __DIR__ . '/../config/database.php';

$db        = getDB();
$buildings = $db->query("SELECT id, name FROM buildings ORDER BY name")->fetchAll();
?>

<style>
/* ── App shell ───────────────────────────────────────────── */
#app {
    display: flex;
    height: calc(100vh - 56px);
    overflow: hidden;
    font-family: inherit;
}

/* ── Nav buttons (buildings / floors / rooms) ────────────── */
.nav-btn {
    padding: 5px 3px;
    border-radius: 6px;
    font-size: 10px;
    font-weight: 600;
    text-align: center;
    cursor: pointer;
    transition: background .12s, color .12s, border-color .12s;
    line-height: 1.25;
    word-break: break-word;
    background: #f1f5f9;
    color: #475569;
    border: 1.5px solid transparent;
    width: 100%;
    display: block;
}
.nav-btn:hover              { background: #dbeafe; color: #1d4ed8; }
.nav-btn.active             { background: #2563eb; color: #fff; border-color: #1d4ed8; }
.nav-btn.room-selected      { background: #dcfce7; color: #166534; border-color: #86efac; }
.nav-btn.room-selected:hover{ background: #bbf7d0; }

/* ── Selected-room chip ──────────────────────────────────── */
.room-chip {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 3px;
    background: #dbeafe;
    border: 1px solid #93c5fd;
    border-radius: 6px;
    padding: 4px 6px;
    font-size: 10px;
    font-weight: 600;
    color: #1e40af;
    cursor: pointer;
    transition: background .1s;
    width: 100%;
    text-align: left;
}
.room-chip:hover { background: #bfdbfe; }
.room-chip .chip-x { flex-shrink: 0; opacity: .6; }

/* ── Calendar day ────────────────────────────────────────── */
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

/* ── Reservation block ───────────────────────────────────── */
.res-block {
    position: absolute;
    border-radius: 5px;
    padding: 3px 6px;
    font-size: 11px;
    line-height: 1.3;
    overflow: hidden;
    cursor: pointer;
    border-left: 3px solid;
    box-sizing: border-box;
    transition: opacity .15s, box-shadow .15s;
}
.res-block:hover { opacity: .85; box-shadow: 0 2px 8px rgba(0,0,0,.15); }

/* ── Now line ────────────────────────────────────────────── */
#now-line {
    position: absolute;
    left: 0; right: 0;
    height: 2px;
    background: #ef4444;
    z-index: 20;
    pointer-events: none;
}
#now-line::before {
    content: '';
    position: absolute;
    left: -5px; top: -4px;
    width: 10px; height: 10px;
    border-radius: 50%;
    background: #ef4444;
}

/* ── Modal ───────────────────────────────────────────────── */
#modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.45);
    z-index: 200;
    align-items: center;
    justify-content: center;
    padding: 16px;
}
#modal-overlay.modal-open {
    display: flex;
}
#modal-box {
    background: #fff;
    border-radius: 16px;
    width: 100%;
    max-width: 540px;
    max-height: 92vh;
    overflow-y: auto;
    box-shadow: 0 24px 64px rgba(0,0,0,.3);
}

/* ── Creatable combobox ──────────────────────────────────── */
#org-dropdown {
    position: absolute;
    z-index: 300;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    box-shadow: 0 8px 24px rgba(0,0,0,.12);
    max-height: 192px;
    overflow-y: auto;
    width: 100%;
    top: calc(100% + 2px);
    left: 0;
}
.org-opt {
    padding: 8px 12px;
    font-size: 13px;
    cursor: pointer;
    transition: background .1s;
}
.org-opt:hover   { background: #f0f9ff; }
.org-opt.create  { color: #2563eb; font-weight: 600; }
</style>

<div id="app">

    <!-- ══════════════════════════════════════════════════════ -->
    <!--  COL 1 — Selected rooms tray                          -->
    <!-- ══════════════════════════════════════════════════════ -->
    <div style="width:148px;flex-shrink:0;" class="bg-white border-r border-gray-200 flex flex-col">

        <div class="p-2 border-b border-gray-200">
            <button onclick="deselectAllRooms()"
                    class="w-full text-xs font-semibold rounded-lg py-1.5 transition bg-gray-100 hover:bg-red-100 hover:text-red-700 text-gray-500">
                Deselect All
            </button>
        </div>

        <div id="tray" class="flex-1 overflow-y-auto p-2 space-y-1">
            <p id="tray-empty" class="text-xs text-gray-400 text-center mt-3">No rooms<br>selected</p>
        </div>

    </div>

    <!-- ══════════════════════════════════════════════════════ -->
    <!--  COL 2 — Building / Floor / Room navigation           -->
    <!-- ══════════════════════════════════════════════════════ -->
    <div style="width:252px;flex-shrink:0;" class="bg-white border-r border-gray-200 flex flex-col overflow-y-auto">
        <div class="p-3">

            <!-- Buildings -->
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Buildings</p>
            <?php if (empty($buildings)): ?>
                <p class="text-xs text-gray-400">No buildings yet. <a href="/pages/facilities.php" class="text-blue-500 underline">Add one</a>.</p>
            <?php else: ?>
            <div class="grid grid-cols-4 gap-1 mb-4">
                <?php foreach ($buildings as $b): ?>
                <button class="nav-btn building-btn"
                        data-id="<?= $b['id'] ?>"
                        onclick="selectBuilding(<?= $b['id'] ?>, this)"
                        title="<?= htmlspecialchars($b['name']) ?>">
                    <?= htmlspecialchars($b['name']) ?>
                </button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Floors (hidden until building selected) -->
            <div id="floors-section" class="hidden">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Floors</p>
                <div id="floors-grid" class="grid grid-cols-4 gap-1 mb-4"></div>
            </div>

            <!-- Rooms (hidden until floor selected) -->
            <div id="rooms-section" class="hidden">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Rooms</p>
                <div id="rooms-grid" class="grid grid-cols-4 gap-1"></div>
            </div>

        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════ -->
    <!--  COL 3 — Monthly calendar                             -->
    <!-- ══════════════════════════════════════════════════════ -->
    <div class="flex-1 bg-gray-50 flex flex-col overflow-hidden">
        <div class="p-5 flex-1 overflow-y-auto">

            <!-- Month nav -->
            <div class="flex items-center justify-between mb-4">
                <button onclick="prevMonth()" class="p-2 rounded-lg hover:bg-gray-200 transition text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
                <h2 id="cal-title" class="text-base font-bold text-gray-800 select-none"></h2>
                <div class="flex items-center gap-2">
                    <button onclick="openModal()" class="text-sm font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-lg py-1.5 px-4 transition">
                        + New Reservation
                    </button>
                    <button onclick="nextMonth()" class="p-2 rounded-lg hover:bg-gray-200 transition text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Weekday headers -->
            <div class="grid grid-cols-7 mb-1">
                <?php foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d): ?>
                <div class="text-center text-xs font-semibold text-gray-400 py-1 select-none"><?= $d ?></div>
                <?php endforeach; ?>
            </div>

            <!-- Day grid -->
            <div id="cal-grid" class="grid grid-cols-7 gap-1"></div>

        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════ -->
    <!--  COL 4 — Daily timeline                               -->
    <!-- ══════════════════════════════════════════════════════ -->
    <div style="width:296px;flex-shrink:0;" class="bg-white border-l border-gray-200 flex flex-col">

        <!-- Header -->
        <div class="p-3 border-b border-gray-200 flex items-start justify-between gap-2">
            <div class="min-w-0">
                <p id="tl-date" class="text-sm font-bold text-gray-800">Select a date</p>
                <p id="tl-rooms" class="text-xs text-gray-400 mt-0.5">showing all rooms</p>
            </div>
            <button id="new-btn" onclick="openModal()" class="hidden flex-shrink-0 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-lg py-1.5 px-3 transition">
                + New
            </button>
        </div>

        <!-- Timeline scroll area -->
        <div class="flex-1 overflow-y-auto relative">

            <p id="tl-placeholder" class="absolute inset-0 flex items-center justify-center text-gray-400 text-sm text-center px-4">
                Select a date<br>to view the schedule
            </p>

            <!-- Timeline canvas: 17 hours × 60px = 1020px tall -->
            <div id="tl-body" class="hidden relative" style="height:1020px;padding-left:44px;padding-right:6px;">

                <?php for ($h = 6; $h <= 22; $h++):
                    $top   = ($h - 6) * 60;
                    $label = $h < 12 ? "{$h}:00 AM" : ($h === 12 ? '12:00 PM' : ($h-12).':00 PM');
                ?>
                <div style="position:absolute;top:<?= $top ?>px;left:0;right:0;pointer-events:none;">
                    <span style="position:absolute;left:2px;top:-9px;font-size:9px;color:#94a3b8;width:38px;text-align:right;line-height:1;">
                        <?= $label ?>
                    </span>
                    <div style="position:absolute;left:44px;right:0;border-top:1px solid #f1f5f9;"></div>
                </div>
                <?php endfor; ?>

                <!-- Half-hour tick marks -->
                <?php for ($h = 6; $h <= 22; $h++):
                    $top = ($h - 6) * 60 + 30;
                ?>
                <div style="position:absolute;top:<?= $top ?>px;left:44px;right:0;border-top:1px dashed #f8fafc;pointer-events:none;"></div>
                <?php endfor; ?>

                <!-- Reservation blocks rendered by JS -->
                <div id="res-blocks" style="position:absolute;top:0;left:44px;right:6px;bottom:0;"></div>

                <!-- "Now" indicator -->
                <div id="now-line" class="hidden" style="left:44px;right:6px;position:absolute;"></div>

            </div>
        </div>
    </div>

</div><!-- #app -->


<!-- ═══════════════════════════════════════════════════════════ -->
<!--  RESERVATION MODAL                                         -->
<!-- ═══════════════════════════════════════════════════════════ -->
<div id="modal-overlay" onclick="if(event.target===this)closeModal()">
    <div id="modal-box">
        <div class="p-5">

            <div class="flex items-center justify-between mb-4">
                <h3 id="modal-title" class="text-lg font-bold text-gray-800">New Reservation</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form id="res-form" onsubmit="saveReservation(event)">
                <input type="hidden" id="f-id" name="id">

                <!-- Title -->
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        Event Title <span class="font-normal text-gray-400">(optional)</span>
                    </label>
                    <input type="text" id="f-title" name="title"
                           placeholder="e.g. Sunday Service, Youth Meeting..."
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Organization combobox -->
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Organization / Ministry</label>
                    <div class="relative" id="org-wrapper">
                        <input type="text" id="f-org-text" autocomplete="off"
                               placeholder="Type to search or create..."
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <input type="hidden" id="f-org-id" name="organization_id">
                        <div id="org-dropdown" class="hidden"></div>
                    </div>
                </div>

                <!-- Date / Start / End -->
                <div class="grid grid-cols-3 gap-3 mb-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Date</label>
                        <input type="date" id="f-date" name="date" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Start</label>
                        <select id="f-start" name="start_time" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                            <option value="">-- time --</option>
                            <?php
                            for ($h = 0; $h <= 23; $h++) {
                                foreach ([0, 15, 30, 45] as $m) {
                                    $val      = sprintf('%02d:%02d', $h, $m);
                                    $display  = ($h === 0 ? 12 : ($h <= 12 ? $h : $h - 12));
                                    $label    = $display . ':' . sprintf('%02d', $m) . ($h < 12 ? ' AM' : ' PM');
                                    $selected = ($h === 12 && $m === 0) ? ' selected' : '';
                                    echo "<option value=\"$val\"$selected>$label</option>\n";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">End</label>
                        <select id="f-end" name="end_time" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                            <option value="">-- time --</option>
                            <?php
                            for ($h = 0; $h <= 23; $h++) {
                                foreach ([0, 15, 30, 45] as $m) {
                                    $val      = sprintf('%02d:%02d', $h, $m);
                                    $display  = ($h === 0 ? 12 : ($h <= 12 ? $h : $h - 12));
                                    $label    = $display . ':' . sprintf('%02d', $m) . ($h < 12 ? ' AM' : ' PM');
                                    $selected = ($h === 12 && $m === 0) ? ' selected' : '';
                                    echo "<option value=\"$val\"$selected>$label</option>\n";
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <!-- Rooms -->
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Rooms</label>
                    <div id="f-rooms-display"
                         class="min-h-8 border border-gray-200 rounded-lg px-3 py-2 bg-gray-50 text-sm text-gray-500">
                        No rooms selected
                    </div>
                    <input type="hidden" id="f-room-ids" name="room_ids">
                </div>

                <!-- Notes -->
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        Notes <span class="font-normal text-gray-400">(optional)</span>
                    </label>
                    <textarea id="f-notes" name="notes" rows="2" resize="none"
                              placeholder="Any additional details..."
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                </div>

                <!-- Recurring -->
                <div class="mb-5 bg-gray-50 rounded-xl p-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="f-recurring" value="1"
                               onchange="toggleRecurring(this.checked)"
                               class="w-4 h-4 rounded text-blue-600 accent-blue-600">
                        <span class="text-sm font-semibold text-gray-700">Recurring event</span>
                    </label>
                    <div id="recur-opts" class="hidden grid grid-cols-2 gap-3 mt-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Frequency</label>
                            <select id="f-recur-rule" name="recurrence_rule"
                                    class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="weekly">Weekly</option>
                                <option value="biweekly">Bi-weekly</option>
                                <option value="monthly">Monthly</option>
                                <option value="daily">Daily</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Ends on</label>
                            <input type="date" id="f-recur-end" name="recurrence_end_date"
                                   class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-between">
                    <button type="button" id="del-btn" onclick="deleteReservation()"
                            class="hidden text-sm text-red-500 hover:text-red-700 font-medium transition">
                        Delete reservation
                    </button>
                    <div class="flex gap-2 ml-auto">
                        <button type="button" onclick="closeModal()"
                                class="text-sm px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-50 text-gray-600 font-medium transition">
                            Cancel
                        </button>
                        <button type="submit"
                                class="text-sm px-5 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold transition">
                            Save
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div><!-- #modal-overlay -->


<script>
// ═══════════════════════════════════════════════════════════
// STATE
// ═══════════════════════════════════════════════════════════
const S = {
    building: null,
    floor:    null,
    rooms:    {},          // { id: { id, name, floor, building } }
    year:     new Date().getFullYear(),
    month:    new Date().getMonth() + 1,   // 1-based
    date:     null,
    dots:     new Set(),
    res:      [],
};

const MONTHS = ['January','February','March','April','May','June',
                'July','August','September','October','November','December'];

// Color palette for reservation blocks (assigned by first room id)
const PALETTE = [
    { bg:'#dbeafe', bd:'#3b82f6', tx:'#1e40af' },
    { bg:'#dcfce7', bd:'#22c55e', tx:'#166534' },
    { bg:'#fef3c7', bd:'#f59e0b', tx:'#92400e' },
    { bg:'#fce7f3', bd:'#ec4899', tx:'#9d174d' },
    { bg:'#ede9fe', bd:'#8b5cf6', tx:'#5b21b6' },
    { bg:'#ffedd5', bd:'#f97316', tx:'#9a3412' },
    { bg:'#cffafe', bd:'#06b6d4', tx:'#155e75' },
    { bg:'#fef9c3', bd:'#eab308', tx:'#854d0e' },
];
const color = id => PALETTE[Math.abs(id) % PALETTE.length];

// ═══════════════════════════════════════════════════════════
// NAVIGATION  —  Buildings / Floors / Rooms
// ═══════════════════════════════════════════════════════════

function selectBuilding(id, el) {
    S.building = id;
    S.floor    = null;

    document.querySelectorAll('.building-btn').forEach(b => b.classList.remove('active'));
    el.classList.add('active');

    document.getElementById('rooms-section').classList.add('hidden');
    document.getElementById('rooms-grid').innerHTML = '';
    document.getElementById('floors-grid').innerHTML = '';

    api('get_floors', { building_id: id }).then(floors => {
        const grid = document.getElementById('floors-grid');
        const sec  = document.getElementById('floors-section');
        if (!floors.length) { sec.classList.add('hidden'); return; }
        floors.forEach(f => {
            const btn = mkBtn(f.name, 'floor-btn');
            btn.dataset.id = f.id;
            btn.onclick = () => selectFloor(f.id, btn);
            grid.appendChild(btn);
        });
        sec.classList.remove('hidden');
    });
}

function selectFloor(id, el) {
    S.floor = id;
    document.querySelectorAll('.floor-btn').forEach(b => b.classList.remove('active'));
    el.classList.add('active');

    const bName = document.querySelector('.building-btn.active')?.title || '';
    const fName = el.title || el.textContent.trim();

    api('get_rooms', { floor_id: id }).then(rooms => {
        const grid = document.getElementById('rooms-grid');
        const sec  = document.getElementById('rooms-section');
        grid.innerHTML = '';
        if (!rooms.length) { sec.classList.add('hidden'); return; }
        rooms.forEach(rm => {
            const label = rm.room_number ? `${rm.name}` : rm.name;
            const btn   = mkBtn(label, 'room-btn' + (S.rooms[rm.id] ? ' room-selected' : ''));
            btn.dataset.id = rm.id;
            btn.title  = rm.name + (rm.room_number ? ` (${rm.room_number})` : '');
            btn.onclick = () => toggleRoom(rm.id, rm.name, fName, bName, btn);
            grid.appendChild(btn);
        });
        sec.classList.remove('hidden');
    });
}

function toggleRoom(id, name, floor, building, btn) {
    if (S.rooms[id]) {
        delete S.rooms[id];
        btn.classList.remove('room-selected');
    } else {
        S.rooms[id] = { id, name, floor, building };
        btn.classList.add('room-selected');
    }
    renderTray();
    refreshAfterRoomChange();
}

function deselectAllRooms() {
    S.rooms = {};
    document.querySelectorAll('.room-btn').forEach(b => b.classList.remove('room-selected'));
    renderTray();
    refreshAfterRoomChange();
}

function removeRoomById(id) {
    delete S.rooms[id];
    const btn = document.querySelector(`.room-btn[data-id="${id}"]`);
    if (btn) btn.classList.remove('room-selected');
    renderTray();
    refreshAfterRoomChange();
}

function refreshAfterRoomChange() {
    loadDots();
    if (S.date) loadRes(S.date);
}

// Build a nav button element
function mkBtn(text, cls) {
    const btn = document.createElement('button');
    btn.className = 'nav-btn ' + cls;
    btn.title = text;
    btn.textContent = text;
    return btn;
}

// ═══════════════════════════════════════════════════════════
// TRAY  —  selected rooms column
// ═══════════════════════════════════════════════════════════

function renderTray() {
    const tray  = document.getElementById('tray');
    const empty = document.getElementById('tray-empty');
    const rooms = Object.values(S.rooms);
    const tlRooms = document.getElementById('tl-rooms');

    if (!rooms.length) {
        tray.querySelectorAll('.room-chip').forEach(c => c.remove());
        empty.classList.remove('hidden');
        tlRooms.textContent = 'showing all rooms';
        return;
    }

    empty.classList.add('hidden');

    // Sync chips: remove chips for deselected rooms
    tray.querySelectorAll('.room-chip').forEach(c => {
        if (!S.rooms[c.dataset.id]) c.remove();
    });

    // Add chips for newly selected rooms
    rooms.forEach(r => {
        if (tray.querySelector(`.room-chip[data-id="${r.id}"]`)) return;
        const chip = document.createElement('div');
        chip.className = 'room-chip';
        chip.dataset.id = r.id;
        chip.title = `${r.name} · ${r.floor} · ${r.building}`;
        chip.innerHTML = `
            <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${r.name}</span>
            <svg class="chip-x" style="width:10px;height:10px;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
            </svg>`;
        chip.onclick = () => removeRoomById(r.id);
        tray.appendChild(chip);
    });

    tlRooms.textContent = rooms.length === 1
        ? rooms[0].name
        : `${rooms.length} rooms selected`;
}

// ═══════════════════════════════════════════════════════════
// CALENDAR
// ═══════════════════════════════════════════════════════════

function renderCalendar() {
    document.getElementById('cal-title').textContent = `${MONTHS[S.month - 1]} ${S.year}`;
    const grid = document.getElementById('cal-grid');
    grid.innerHTML = '';

    const firstDow    = new Date(S.year, S.month - 1, 1).getDay();
    const daysInMonth = new Date(S.year, S.month, 0).getDate();
    const daysInPrev  = new Date(S.year, S.month - 1, 0).getDate();
    const todayStr    = fmtDate(new Date());

    // Build 42-cell grid (6 rows × 7 cols)
    const cells = [];
    for (let i = firstDow - 1; i >= 0; i--)
        cells.push({ d: daysInPrev - i, m: S.month - 1, y: S.month === 1 ? S.year - 1 : S.year, other: true });
    for (let d = 1; d <= daysInMonth; d++)
        cells.push({ d, m: S.month, y: S.year, other: false });
    while (cells.length < 42)
        cells.push({ d: cells.length - firstDow - daysInMonth + 1, m: S.month + 1, y: S.month === 12 ? S.year + 1 : S.year, other: true });

    cells.forEach(cell => {
        const cm = cell.m > 12 ? 1 : (cell.m < 1 ? 12 : cell.m);
        const cy = cell.m > 12 ? cell.y + 1 : (cell.m < 1 ? cell.y - 1 : cell.y);
        const ds = `${cy}-${String(cm).padStart(2,'0')}-${String(cell.d).padStart(2,'0')}`;

        const div = document.createElement('div');
        div.className = 'cal-day'
            + (cell.other          ? ' other-month' : '')
            + (ds === todayStr     ? ' today'       : '')
            + (ds === S.date       ? ' selected'    : '');
        div.onclick = () => selectDate(ds);

        let html = `<span class="text-sm font-semibold">${cell.d}</span>`;
        if (!cell.other && S.dots.has(ds)) html += '<div class="cal-dot"></div>';
        div.innerHTML = html;
        grid.appendChild(div);
    });
}

function prevMonth() {
    if (S.month === 1) { S.month = 12; S.year--; } else { S.month--; }
    loadDots();
}
function nextMonth() {
    if (S.month === 12) { S.month = 1; S.year++; } else { S.month++; }
    loadDots();
}

function loadDots() {
    const roomIds = Object.keys(S.rooms).join(',');
    api('get_calendar_dots', { year: S.year, month: S.month, room_ids: roomIds })
        .then(dates => { S.dots = new Set(dates); renderCalendar(); });
}

function selectDate(ds) {
    S.date = ds;
    renderCalendar();

    const d = new Date(ds + 'T00:00:00');
    document.getElementById('tl-date').textContent =
        d.toLocaleDateString('en-US', { weekday:'short', month:'short', day:'numeric', year:'numeric' });
    document.getElementById('new-btn').classList.remove('hidden');

    loadRes(ds);
}

// ═══════════════════════════════════════════════════════════
// TIMELINE
// ═══════════════════════════════════════════════════════════
const TL_START = 6;   // 6 AM
const PX_PER_H = 60;  // 1 px per minute

function toMin(timeStr) {   // "HH:MM:SS"
    const [h, m] = timeStr.split(':').map(Number);
    return h * 60 + m;
}
function minToTop(min) {
    return (min - TL_START * 60) * (PX_PER_H / 60);
}

function loadRes(ds) {
    const roomIds = Object.keys(S.rooms).join(',');
    api('get_reservations', { date: ds, room_ids: roomIds })
        .then(rows => { S.res = rows; renderTimeline(rows); });
}

function renderTimeline(rows) {
    document.getElementById('tl-placeholder').classList.add('hidden');
    document.getElementById('tl-body').classList.remove('hidden');

    const container = document.getElementById('res-blocks');
    container.innerHTML = '';

    // Enrich with minute offsets
    const evs = rows.map(r => ({
        ...r,
        _s: toMin(r.start_datetime.split(' ')[1]),
        _e: toMin(r.end_datetime.split(' ')[1]),
    })).sort((a, b) => a._s - b._s);

    // Simple column-layout for overlapping events
    const cols = [];
    evs.forEach(ev => {
        let c = 0;
        while (cols[c] && cols[c].some(x => x._s < ev._e && x._e > ev._s)) c++;
        ev._col = c;
        if (!cols[c]) cols[c] = [];
        cols[c].push(ev);
    });
    const nCols = cols.length || 1;
    evs.forEach(ev => ev._nCols = nCols);

    evs.forEach(ev => {
        const top    = minToTop(ev._s);
        const height = Math.max(22, (ev._e - ev._s) * (PX_PER_H / 60));
        const pct    = 100 / ev._nCols;
        const rooms  = (ev.rooms || []).map(r => r.name).join(', ');
        const c      = color(ev.rooms?.[0]?.id ?? 0);
        const label  = ev.title || ev.organization_name || rooms || 'Reservation';
        const sub    = (ev.title && ev.organization_name) ? ev.organization_name : rooms;

        const el = document.createElement('div');
        el.className = 'res-block';
        el.style.cssText = `top:${top}px;height:${height}px;` +
            `left:calc(${ev._col * pct}%);width:calc(${pct}% - 2px);` +
            `background:${c.bg};border-left-color:${c.bd};color:${c.tx};`;
        el.innerHTML =
            `<div style="font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${esc(label)}</div>` +
            (sub ? `<div style="opacity:.75;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:10px;">${esc(sub)}</div>` : '');
        el.onclick = () => openModal(ev.id);
        container.appendChild(el);
    });

    // Now-line
    const now    = new Date();
    const nowMin = now.getHours() * 60 + now.getMinutes();
    const nowLine = document.getElementById('now-line');
    if (S.date === fmtDate(now) && nowMin >= TL_START * 60 && nowMin <= 23 * 60) {
        nowLine.style.top = minToTop(nowMin) + 'px';
        nowLine.classList.remove('hidden');
    } else {
        nowLine.classList.add('hidden');
    }
}

// ═══════════════════════════════════════════════════════════
// MODAL
// ═══════════════════════════════════════════════════════════

function openModal(resId = null) {
    resetForm();
    if (resId) {
        document.getElementById('modal-title').textContent = 'Edit Reservation';
        document.getElementById('del-btn').classList.remove('hidden');
        document.getElementById('f-id').value = resId;
        api('get_reservation', { id: resId }).then(r => {
            if (r.error) { alert(r.error); return; }
            document.getElementById('f-title').value = r.title || '';
            document.getElementById('f-date').value  = r.start_datetime.split(' ')[0];
            document.getElementById('f-start').value = r.start_datetime.split(' ')[1].slice(0,5);
            document.getElementById('f-end').value   = r.end_datetime.split(' ')[1].slice(0,5);
            document.getElementById('f-notes').value = r.notes || '';
            if (r.organization_id) {
                document.getElementById('f-org-id').value   = r.organization_id;
                document.getElementById('f-org-text').value = r.organization_name || '';
            }
            if (r.is_recurring == 1) {
                document.getElementById('f-recurring').checked = true;
                toggleRecurring(true);
                document.getElementById('f-recur-rule').value = r.recurrence_rule || 'weekly';
                document.getElementById('f-recur-end').value  = r.recurrence_end_date || '';
            }
            const map = {};
            (r.rooms || []).forEach(rm => map[rm.id] = rm.name);
            setModalRooms(map);
        });
    } else {
        document.getElementById('modal-title').textContent = 'New Reservation';
        document.getElementById('del-btn').classList.add('hidden');
        document.getElementById('f-id').value = '';
        if (S.date) document.getElementById('f-date').value = S.date;
        const map = {};
        Object.values(S.rooms).forEach(r => map[r.id] = r.name);
        setModalRooms(map);
    }
    document.getElementById('modal-overlay').classList.add('modal-open');
}

function setModalRooms(map) {
    const ids = Object.keys(map);
    document.getElementById('f-room-ids').value = ids.join(',');
    const el = document.getElementById('f-rooms-display');
    el.innerHTML = ids.length
        ? ids.map(id => `<span style="display:inline-flex;align-items:center;gap:3px;background:#dbeafe;color:#1e40af;font-size:11px;font-weight:600;border-radius:20px;padding:2px 8px;margin:1px;">${esc(map[id])}</span>`).join('')
        : '<span style="color:#9ca3af;font-size:12px;">No rooms — add from the panel first</span>';
}

function resetForm() {
    document.getElementById('res-form').reset();
    document.getElementById('f-id').value       = '';
    document.getElementById('f-org-id').value   = '';
    document.getElementById('f-org-text').value = '';
    document.getElementById('recur-opts').classList.add('hidden');
    document.getElementById('f-rooms-display').innerHTML =
        '<span style="color:#9ca3af;font-size:12px;">No rooms selected</span>';
    document.getElementById('org-dropdown').classList.add('hidden');
}

function closeModal() {
    document.getElementById('modal-overlay').classList.remove('modal-open');
}

function toggleRecurring(on) {
    document.getElementById('recur-opts').classList.toggle('hidden', !on);
}

function saveReservation(e) {
    e.preventDefault();
    const date  = document.getElementById('f-date').value;
    const start = document.getElementById('f-start').value;
    const end   = document.getElementById('f-end').value;

    const data = new FormData(document.getElementById('res-form'));
    data.set('action', 'save_reservation');
    data.set('start_datetime', `${date} ${start}:00`);
    data.set('end_datetime',   `${date} ${end}:00`);
    data.set('is_recurring', document.getElementById('f-recurring').checked ? 1 : 0);

    postApi(data).then(r => {
        if (r.error) { alert(r.error); return; }
        closeModal();
        loadDots();
        if (S.date) loadRes(S.date);
    });
}

function deleteReservation() {
    const id = document.getElementById('f-id').value;
    if (!id || !confirm('Delete this reservation?')) return;
    const d = new FormData();
    d.set('action', 'delete_reservation');
    d.set('id', id);
    postApi(d).then(r => {
        if (r.success) { closeModal(); loadDots(); if (S.date) loadRes(S.date); }
    });
}

// ═══════════════════════════════════════════════════════════
// CREATABLE COMBOBOX  —  Organizations
// ═══════════════════════════════════════════════════════════

let orgTimer = null;

document.getElementById('f-org-text').addEventListener('input', function () {
    clearTimeout(orgTimer);
    document.getElementById('f-org-id').value = '';
    orgTimer = setTimeout(() => searchOrgs(this.value), 200);
});

document.getElementById('f-org-text').addEventListener('focus', function () {
    searchOrgs(this.value);
});

document.addEventListener('click', e => {
    if (!document.getElementById('org-wrapper').contains(e.target))
        document.getElementById('org-dropdown').classList.add('hidden');
});

function searchOrgs(q) {
    api('get_organizations', { q }).then(orgs => {
        const dd      = document.getElementById('org-dropdown');
        const trimmed = q.trim();
        const exact   = orgs.some(o => o.name.toLowerCase() === trimmed.toLowerCase());
        dd.innerHTML  = '';

        orgs.forEach(org => {
            const opt = document.createElement('div');
            opt.className = 'org-opt';
            opt.textContent = org.name;
            opt.onmousedown = ev => {
                ev.preventDefault();
                document.getElementById('f-org-text').value = org.name;
                document.getElementById('f-org-id').value   = org.id;
                dd.classList.add('hidden');
            };
            dd.appendChild(opt);
        });

        if (trimmed && !exact) {
            const create = document.createElement('div');
            create.className = 'org-opt create';
            create.textContent = `+ Create "${trimmed}"`;
            create.onmousedown = ev => { ev.preventDefault(); createOrg(trimmed); };
            dd.appendChild(create);
        }

        dd.classList.toggle('hidden', !dd.children.length);
    });
}

function createOrg(name) {
    const d = new FormData();
    d.set('action', 'create_organization');
    d.set('name', name);
    postApi(d).then(org => {
        if (org.id) {
            document.getElementById('f-org-text').value = org.name;
            document.getElementById('f-org-id').value   = org.id;
            document.getElementById('org-dropdown').classList.add('hidden');
        }
    });
}

// ═══════════════════════════════════════════════════════════
// HELPERS
// ═══════════════════════════════════════════════════════════

function api(action, params = {}) {
    const qs = new URLSearchParams({ action, ...params }).toString();
    return fetch(`/api/reservations_api.php?${qs}`).then(r => r.json());
}
function postApi(formData) {
    return fetch('/api/reservations_api.php', { method: 'POST', body: formData }).then(r => r.json());
}
function fmtDate(d) {
    return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
}
function esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ═══════════════════════════════════════════════════════════
// INIT
// ═══════════════════════════════════════════════════════════
renderTray();
loadDots();
</script>

</body>
</html>
