<?php
$pageTitle = 'Reservations — Church Facility Manager';
require_once __DIR__ . '/../includes/nav.php';
require_once __DIR__ . '/../config/database.php';

$db        = getDB();
$buildings = $db->query("SELECT id, name FROM buildings ORDER BY name")->fetchAll();

// Floor plan picker config
$fp_id        = 'res';
$fp_div_key   = 'fp_w_res';
$fp_default_w = 600;
$fp_buildings = $buildings;
?>

<style>
/* ── App shell ───────────────────────────────────────────── */
#app {
    display: flex;
    height: calc(100vh - 56px);
    overflow: hidden;
    font-family: inherit;
}

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
    border: 1px solid;
    border-left-width: 4px;
    box-sizing: border-box;
    transition: opacity .15s, box-shadow .15s;
}
.res-block:hover { opacity: .85; box-shadow: 0 2px 8px rgba(0,0,0,.15); }
.res-block.editing { box-shadow: 0 0 0 2px #2563eb; opacity: 1; }

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

/* ── Timeline selector ───────────────────────────────────── */
#tl-selector {
    position: absolute;
    left: 44px; right: 6px;
    height: 2px;
    background: #8b5cf6;
    z-index: 25;
    cursor: ns-resize;
    touch-action: none;
}
#tl-selector::before {
    content: '';
    position: absolute;
    left: -5px; top: -4px;
    width: 10px; height: 10px;
    border-radius: 50%;
    background: #8b5cf6;
}
#tl-selector::after {
    content: '';
    position: absolute;
    left: 0; right: 0;
    top: -8px; bottom: -8px;
}

/* ── Floor plans pane — styles provided by floor_plan_picker.php module ── */

/* ── Calendar month/year picker ──────────────────────────── */
#cal-title { cursor:pointer; transition:color .12s; border-radius:6px; padding:3px 8px; }
#cal-title:hover { color:#2563eb; background:#eff6ff; }
#cal-picker {
    display: none;
    position: absolute;
    left: 50%; transform: translateX(-50%);
    top: calc(100% + 6px);
    z-index: 300;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0,0,0,.14);
    padding: 12px;
    width: 220px;
    font-family: ui-sans-serif, system-ui, sans-serif;
}
#cal-picker.open { display: block; }
.cp-year-row { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; }
.cp-year-btn { background:none; border:1px solid #e2e8f0; border-radius:6px; padding:3px 10px; cursor:pointer; font-size:14px; color:#374151; transition:background .1s; }
.cp-year-btn:hover { background:#f3f4f6; }
#cp-year { font-size:15px; font-weight:700; color:#111827; min-width:48px; text-align:center; }
.cp-month-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:4px; }
.cp-month-btn { background:none; border:1px solid transparent; border-radius:6px; padding:6px 2px; font-size:12px; font-weight:600; cursor:pointer; text-align:center; color:#374151; transition:background .1s, border-color .1s; }
.cp-month-btn:hover { background:#eff6ff; border-color:#bfdbfe; }
.cp-month-btn.active { background:#2563eb; color:white; border-color:#2563eb; }

/* ── Reservation editor pane ─────────────────────────────── */
#res-editor { width:400px; flex-shrink:0; background:#f8fafc; border-left:1px solid #e2e8f0; display:flex; flex-direction:column; overflow:hidden; }
#editor-new-row { padding:10px 12px; border-bottom:1px solid #e2e8f0; background:#fff; flex-shrink:0; }
#editor-new-btn { width:100%; background:#2563eb; color:white; border:none; border-radius:8px; padding:8px; font-size:13px; font-weight:700; cursor:pointer; transition:background .12s; font-family:ui-sans-serif,system-ui,sans-serif; }
#editor-new-btn:hover { background:#1d4ed8; }
#editor-empty { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; color:#9ca3af; padding:32px 24px; text-align:center; }
#editor-form-wrap { display:none; flex-direction:column; flex:1; min-height:0; overflow:hidden; }
#editor-form-wrap.open { display:flex; }
#editor-hdr { padding:13px 16px; border-bottom:1px solid #e2e8f0; background:#fff; flex-shrink:0; display:flex; align-items:center; justify-content:space-between; gap:8px; }
#editor-title { font-size:15px; font-weight:700; color:#111827; margin:0; }
#editor-close { background:none; border:none; cursor:pointer; color:#9ca3af; padding:3px; border-radius:6px; display:flex; align-items:center; justify-content:center; transition:color .12s; }
#editor-close:hover { color:#374151; }
#editor-body { flex:1; overflow-y:auto; padding:16px; }
.ef-label { display:block; font-size:12px; font-weight:600; color:#374151; margin-bottom:4px; }
.ef-label span { font-weight:400; color:#9ca3af; }
.ef-input { width:100%; border:1px solid #d1d5db; border-radius:8px; padding:8px 11px; font-size:13px; outline:none; font-family:ui-sans-serif,system-ui,sans-serif; color:#111827; background:white; box-sizing:border-box; }
.ef-input:focus { border-color:#3b82f6; box-shadow:0 0 0 3px rgba(59,130,246,0.12); }

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
.org-opt { padding: 8px 12px; font-size: 13px; cursor: pointer; transition: background .1s; }
.org-opt:hover  { background: #f0f9ff; }
.org-opt.create { color: #2563eb; font-weight: 600; }

/* ── Delete recurring modal ─────────────────────────────── */
#delete-recur-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.35);
    z-index: 500;
    align-items: center;
    justify-content: center;
}
#delete-recur-overlay.open { display: flex; }
#delete-recur-modal {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 12px 40px rgba(0,0,0,.2);
    padding: 28px 32px 24px;
    max-width: 360px;
    width: 90%;
    text-align: center;
    font-family: ui-sans-serif, system-ui, sans-serif;
}
#delete-recur-modal h3 { font-size: 16px; font-weight: 700; color: #111827; margin: 0 0 6px; }
#delete-recur-modal p  { font-size: 13px; color: #6b7280; margin: 0 0 20px; }
.dr-btn {
    display: block;
    width: 100%;
    border: none;
    border-radius: 8px;
    padding: 10px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: background .12s;
    font-family: inherit;
    margin-bottom: 8px;
}
.dr-btn-this { background: #fef3c7; color: #92400e; }
.dr-btn-this:hover { background: #fde68a; }
.dr-btn-all  { background: #fee2e2; color: #991b1b; }
.dr-btn-all:hover  { background: #fecaca; }
.dr-btn-cancel { background: #f3f4f6; color: #374151; margin-bottom: 0; }
.dr-btn-cancel:hover { background: #e5e7eb; }
</style>

<div id="app">

    <!-- ══════════════════════════════════════════════════════ -->
    <!--  COL 1 — Floor Plans room selector (reusable module)  -->
    <!-- ══════════════════════════════════════════════════════ -->
    <?php require_once __DIR__ . '/../includes/floor_plan_picker.php'; ?>

    <!-- ══════════════════════════════════════════════════════ -->
    <!--  COL 2 — Monthly calendar                             -->
    <!-- ══════════════════════════════════════════════════════ -->
    <div class="flex-1 bg-gray-50 flex flex-col overflow-hidden" style="min-width:300px;">
        <div class="p-5 flex-1 overflow-y-auto">

            <!-- Month nav -->
            <div class="flex items-center justify-between mb-4" style="position:relative;">
                <button onclick="prevMonth()" class="p-2 rounded-lg hover:bg-gray-200 transition text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
                <h2 id="cal-title" class="text-base font-bold text-gray-800 select-none"
                    onclick="toggleCalPicker(event)"></h2>
                <div id="cal-picker">
                    <div class="cp-year-row">
                        <button class="cp-year-btn" type="button" onclick="calPickerYearAdj(-1)">‹</button>
                        <span id="cp-year"></span>
                        <button class="cp-year-btn" type="button" onclick="calPickerYearAdj(1)">›</button>
                    </div>
                    <div class="cp-month-grid" id="cp-months"></div>
                </div>
                <button onclick="nextMonth()" class="p-2 rounded-lg hover:bg-gray-200 transition text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
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
    <!--  COL 3 — Daily timeline                               -->
    <!-- ══════════════════════════════════════════════════════ -->
    <div style="width:296px;flex-shrink:0;" class="bg-white border-l border-gray-200 flex flex-col">

        <!-- Header: selected time + scale slider -->
        <div style="padding:8px 12px;border-bottom:1px solid #e2e8f0;flex-shrink:0;background:#fff;">
            <div id="tl-sel-time" style="font-size:22px;font-weight:700;color:#111827;text-align:center;font-family:ui-sans-serif,system-ui,sans-serif;margin-bottom:6px;line-height:1;">—</div>
            <div style="display:flex;align-items:center;gap:8px;">
                <span style="font-size:10px;color:#9ca3af;font-family:ui-sans-serif,system-ui,sans-serif;white-space:nowrap;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;">Scale</span>
                <input id="tl-scale" type="range" min="30" max="120" step="10" value="60"
                       style="flex:1;accent-color:#2563eb;cursor:pointer;"
                       oninput="onScaleChange(+this.value)">
            </div>
        </div>

        <!-- Timeline scroll area -->
        <div id="tl-scroll" class="flex-1 overflow-y-auto relative" style="min-height:0;">

            <p id="tl-placeholder" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#9ca3af;font-size:13px;text-align:center;padding:16px;pointer-events:none;">
                Select a date<br>to view the schedule
            </p>

            <!-- Timeline canvas (height set dynamically by JS) -->
            <div id="tl-body" class="hidden relative" style="padding-left:44px;padding-right:6px;">
                <div id="tl-marks"></div>
                <div id="res-blocks" style="position:absolute;top:0;left:44px;right:6px;bottom:0;"></div>
                <div id="now-line" class="hidden" style="left:44px;right:6px;position:absolute;"></div>
                <div id="tl-selector"></div>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════ -->
    <!--  COL 4 — Reservation editor (400px)                   -->
    <!-- ══════════════════════════════════════════════════════ -->
    <div id="res-editor">

        <!-- Always-visible new button -->
        <div id="editor-new-row">
            <button id="editor-new-btn" onclick="openEditor()">+ New Reservation</button>
        </div>

        <!-- Empty state -->
        <div id="editor-empty">
            <svg style="width:48px;height:48px;margin-bottom:14px;opacity:0.3;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <p style="font-size:14px;font-weight:500;margin:0 0 5px;">No reservation selected</p>
            <small style="font-size:12px;">Click a reservation in the timeline<br>or use the floor plan to select rooms,<br>then press + New</small>
        </div>

        <!-- Form (shown when editing/creating) -->
        <div id="editor-form-wrap">
            <div id="editor-hdr">
                <h3 id="editor-title">New Reservation</h3>
                <button id="editor-close" onclick="closeEditor()" title="Close">
                    <svg style="width:18px;height:18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div id="editor-body">
                <form id="res-form" onsubmit="saveReservation(event)">
                    <input type="hidden" id="f-id" name="id">

                    <!-- Title -->
                    <div style="margin-bottom:14px;">
                        <label class="ef-label">Event Title <span>(optional)</span></label>
                        <input type="text" id="f-title" name="title" class="ef-input"
                               placeholder="e.g. Sunday Service, Youth Meeting...">
                    </div>

                    <!-- Organization combobox -->
                    <div style="margin-bottom:14px;">
                        <label class="ef-label">Organization / Ministry</label>
                        <div style="position:relative;" id="org-wrapper">
                            <input type="text" id="f-org-text" autocomplete="off" class="ef-input"
                                   placeholder="Type to search or create...">
                            <input type="hidden" id="f-org-id" name="organization_id">
                            <div id="org-dropdown" class="hidden"></div>
                        </div>
                    </div>

                    <!-- Date (row 1) -->
                    <div style="margin-bottom:10px;">
                        <label class="ef-label">Date</label>
                        <input type="date" id="f-date" name="date" required class="ef-input">
                    </div>

                    <!-- Start / End / Duration (row 2) -->
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:14px;">
                        <div>
                            <label class="ef-label">Start</label>
                            <select id="f-start" name="start_time" required class="ef-input" style="cursor:pointer;" onchange="onStartChange()">
                                <option value="">— time —</option>
                                <?php for ($h = 0; $h <= 23; $h++): foreach ([0,15,30,45] as $m):
                                    $val = sprintf('%02d:%02d', $h, $m);
                                    $d   = $h===0?12:($h<=12?$h:$h-12);
                                    $lbl = $d.':'.sprintf('%02d',$m).($h<12?' AM':' PM');
                                    $sel = ($h===9&&$m===0)?' selected':'';
                                    echo "<option value=\"$val\"$sel>$lbl</option>\n";
                                endforeach; endfor; ?>
                            </select>
                        </div>
                        <div>
                            <label class="ef-label">End</label>
                            <select id="f-end" name="end_time" required class="ef-input" style="cursor:pointer;" onchange="onEndChange()">
                                <option value="">— time —</option>
                                <?php for ($h = 0; $h <= 23; $h++): foreach ([0,15,30,45] as $m):
                                    $val = sprintf('%02d:%02d', $h, $m);
                                    $d   = $h===0?12:($h<=12?$h:$h-12);
                                    $lbl = $d.':'.sprintf('%02d',$m).($h<12?' AM':' PM');
                                    $sel = ($h===10&&$m===0)?' selected':'';
                                    echo "<option value=\"$val\"$sel>$lbl</option>\n";
                                endforeach; endfor; ?>
                            </select>
                        </div>
                        <div>
                            <label class="ef-label">Duration</label>
                            <select id="f-dur" class="ef-input" style="cursor:pointer;" onchange="onDurChange()">
                                <?php for ($mins = 15; $mins <= 720; $mins += 15):
                                    $h = intdiv($mins,60); $m = $mins%60;
                                    $lbl = $h>0 ? ($m>0?"{$h}h {$m}m":"{$h}h") : "{$m}m";
                                    $sel = ($mins===60)?' selected':'';
                                    echo "<option value=\"$mins\"$sel>$lbl</option>\n";
                                endfor; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Rooms -->
                    <div style="margin-bottom:14px;">
                        <label class="ef-label">Rooms <span>(select on floor plan)</span></label>
                        <div id="f-rooms-display"
                             style="min-height:32px;border:1px solid #e5e7eb;border-radius:8px;padding:6px 10px;background:#f9fafb;font-size:12px;color:#6b7280;">
                            No rooms selected
                        </div>
                        <input type="hidden" id="f-room-ids" name="room_ids">
                    </div>

                    <!-- Notes -->
                    <div style="margin-bottom:14px;">
                        <label class="ef-label">Notes <span>(optional)</span></label>
                        <textarea id="f-notes" name="notes" rows="2" class="ef-input"
                                  style="resize:none;" placeholder="Any additional details..."></textarea>
                    </div>

                    <!-- Recurring -->
                    <div style="margin-bottom:16px;background:#f3f4f6;border-radius:10px;padding:10px 12px;">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                            <input type="checkbox" id="f-recurring" value="1"
                                   onchange="toggleRecurring(this.checked)"
                                   style="width:14px;height:14px;accent-color:#2563eb;">
                            <span style="font-size:13px;font-weight:600;color:#374151;">Recurring event</span>
                        </label>
                        <div id="recur-opts" style="display:none;margin-top:10px;">

                            <!-- Row 1: Frequency + Ends On -->
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
                                <div>
                                    <label style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:3px;">Frequency</label>
                                    <select id="f-recur-rule" class="ef-input" style="font-size:12px;cursor:pointer;" onchange="onRecurRuleChange()">
                                        <option value="weekly">Weekly</option>
                                        <option value="biweekly">Bi-weekly</option>
                                        <option value="monthly">Monthly</option>
                                        <option value="daily">Daily</option>
                                    </select>
                                </div>
                                <div>
                                    <label style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:3px;">Ends on</label>
                                    <input type="date" id="f-recur-end" name="recurrence_end_date" class="ef-input" style="font-size:12px;">
                                </div>
                            </div>

                            <!-- Weekly / Bi-weekly: day-of-week checkboxes -->
                            <div id="recur-weekly-days" style="display:none;padding-top:8px;border-top:1px solid #e5e7eb;">
                                <label style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:6px;">Repeat on</label>
                                <div style="display:flex;gap:4px;">
                                    <?php foreach ([['SUN','S'],['MON','M'],['TUE','T'],['WED','W'],['THU','T'],['FRI','F'],['SAT','S']] as [$val,$lbl]): ?>
                                    <label style="display:flex;flex-direction:column;align-items:center;gap:3px;cursor:pointer;flex:1;">
                                        <span style="font-size:10px;font-weight:700;color:#6b7280;"><?= $lbl ?></span>
                                        <input type="checkbox" id="recur-day-<?= $val ?>" value="<?= $val ?>"
                                               style="width:15px;height:15px;accent-color:#2563eb;cursor:pointer;">
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Monthly: by day-of-month or by nth weekday -->
                            <div id="recur-monthly-opts" style="display:none;padding-top:8px;border-top:1px solid #e5e7eb;">
                                <label style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:6px;">Repeat by</label>
                                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-bottom:7px;">
                                    <input type="radio" name="monthly-type" value="day" id="monthly-by-day" checked
                                           style="accent-color:#2563eb;width:14px;height:14px;" onchange="onMonthlyTypeChange()">
                                    <span style="font-size:12px;color:#374151;">Day <strong id="monthly-day-num">1</strong> of the month</span>
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;flex-wrap:wrap;row-gap:4px;">
                                    <input type="radio" name="monthly-type" value="nth" id="monthly-by-nth"
                                           style="accent-color:#2563eb;width:14px;height:14px;" onchange="onMonthlyTypeChange()">
                                    <span style="font-size:12px;color:#374151;">The</span>
                                    <select id="f-monthly-nth" class="ef-input" disabled
                                            style="font-size:12px;width:auto;padding:3px 8px;cursor:pointer;">
                                        <option value="first">First</option>
                                        <option value="second">Second</option>
                                        <option value="third">Third</option>
                                        <option value="fourth">Fourth</option>
                                        <option value="last">Last</option>
                                    </select>
                                    <select id="f-monthly-day" class="ef-input" disabled
                                            style="font-size:12px;width:auto;padding:3px 8px;cursor:pointer;">
                                        <option value="SUN">Sunday</option>
                                        <option value="MON">Monday</option>
                                        <option value="TUE">Tuesday</option>
                                        <option value="WED">Wednesday</option>
                                        <option value="THU">Thursday</option>
                                        <option value="FRI">Friday</option>
                                        <option value="SAT">Saturday</option>
                                    </select>
                                    <span style="font-size:12px;color:#374151;">of the month</span>
                                </label>
                            </div>

                        </div>
                    </div>

                    <!-- Actions -->
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                        <button type="button" id="del-btn" onclick="deleteReservation()"
                                style="display:none;font-size:12px;color:#ef4444;background:none;border:none;cursor:pointer;font-weight:500;padding:0;transition:color .12s;"
                                onmouseover="this.style.color='#b91c1c'" onmouseout="this.style.color='#ef4444'">
                            Delete reservation
                        </button>
                        <div style="display:flex;gap:8px;margin-left:auto;">
                            <button type="button" onclick="closeEditor()"
                                    style="font-size:13px;padding:8px 16px;border-radius:8px;border:1px solid #d1d5db;background:white;color:#374151;cursor:pointer;font-weight:500;transition:background .12s;"
                                    onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='white'">
                                Cancel
                            </button>
                            <button type="submit"
                                    style="font-size:13px;padding:8px 20px;border-radius:8px;border:none;background:#2563eb;color:white;cursor:pointer;font-weight:700;transition:background .12s;"
                                    onmouseover="this.style.background='#1d4ed8'" onmouseout="this.style.background='#2563eb'">
                                Save
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <!-- Delete recurring modal -->
    <div id="delete-recur-overlay" onclick="if(event.target===this)closeDeleteRecurModal()">
        <div id="delete-recur-modal">
            <h3>Delete Recurring Event</h3>
            <p>Would you like to remove just this occurrence, or the entire series?</p>
            <button class="dr-btn dr-btn-this" onclick="confirmDeleteRecur('this')">Delete This Instance Only</button>
            <button class="dr-btn dr-btn-all"  onclick="confirmDeleteRecur('all')">Delete All Occurrences</button>
            <button class="dr-btn dr-btn-cancel" onclick="closeDeleteRecurModal()">Cancel</button>
        </div>
    </div>

</div><!-- #app -->


<script>
// ═══════════════════════════════════════════════════════════
// STATE
// ═══════════════════════════════════════════════════════════
const S = {
    rooms: {},   // { id: { id, name, floor, building } }
    year:  new Date().getFullYear(),
    month: new Date().getMonth() + 1,
    date:  null,
    dots:  new Set(),
    res:   [],
};

const MONTHS       = ['January','February','March','April','May','June','July','August','September','October','November','December'];
const MONTHS_SHORT = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

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
// FLOOR PLAN PICKER  (module instance)
// ═══════════════════════════════════════════════════════════

const picker = new FloorPlanPicker({
    paneId      : 'res-fp-pane',
    dividerId   : 'res-fp-divider',
    dividerKey  : 'fp_w_res',
    defaultWidth: 600,
    onChange    : rooms => {
        S.rooms = rooms;
        syncEditorRooms();
        refreshAfterRoomChange();
    },
});

// Load linked groups and pass to picker
fetch(BASE_PATH + '/api/room_links_api.php?action=get_links')
    .then(r => r.json())
    .then(links => picker.setLinkedGroups(links));

// Load H-Link groups and pass to picker
fetch(BASE_PATH + '/api/h_link_api.php?action=get_groups')
    .then(r => r.json())
    .then(groups => picker.setHLinkGroups(groups))
    .catch(() => {});

// Convenience wrappers used elsewhere in the page
function removeRoomById(id) {
    const sel = picker.getSelection();
    delete sel[id];
    // Rebuild via selectRooms (clears first, then selects remaining)
    picker.clearSelection();
    picker.selectRooms(Object.keys(sel).map(Number));
}

// Push S.rooms into the editor's room display whenever the form is open
function syncEditorRooms() {
    if (!document.getElementById('editor-form-wrap').classList.contains('open')) return;
    const map = {};
    Object.values(S.rooms).forEach(r => { map[r.id] = r.name; });
    setEditorRooms(map);
}

function refreshAfterRoomChange() {
    loadDots();
    if (S.date) loadRes(S.date);
}

// ═══════════════════════════════════════════════════════════
// CALENDAR
// ═══════════════════════════════════════════════════════════

function renderCalendar() {
    document.getElementById('cal-title').textContent = `${MONTHS[S.month-1]} ${S.year}`;
    const grid = document.getElementById('cal-grid');
    grid.innerHTML = '';
    const firstDow    = new Date(S.year, S.month-1, 1).getDay();
    const daysInMonth = new Date(S.year, S.month, 0).getDate();
    const daysInPrev  = new Date(S.year, S.month-1, 0).getDate();
    const todayStr    = fmtDate(new Date());

    const cells = [];
    for (let i = firstDow-1; i >= 0; i--)
        cells.push({ d: daysInPrev-i, m: S.month-1, y: S.month===1?S.year-1:S.year, other:true });
    for (let d = 1; d <= daysInMonth; d++)
        cells.push({ d, m: S.month, y: S.year, other:false });
    while (cells.length < 42)
        cells.push({ d: cells.length-firstDow-daysInMonth+1, m: S.month+1, y: S.month===12?S.year+1:S.year, other:true });

    cells.forEach(cell => {
        const cm = cell.m>12?1:(cell.m<1?12:cell.m);
        const cy = cell.m>12?cell.y+1:(cell.m<1?cell.y-1:cell.y);
        const ds = `${cy}-${String(cm).padStart(2,'0')}-${String(cell.d).padStart(2,'0')}`;
        const div = document.createElement('div');
        div.className = 'cal-day'
            + (cell.other      ? ' other-month' : '')
            + (ds===todayStr   ? ' today'       : '')
            + (ds===S.date     ? ' selected'    : '');
        div.onclick = () => selectDate(ds);
        let html = `<span class="text-sm font-semibold">${cell.d}</span>`;
        if (!cell.other && S.dots.has(ds)) html += '<div class="cal-dot"></div>';
        div.innerHTML = html;
        grid.appendChild(div);
    });
    renderCalPicker();
}

function prevMonth() { if(S.month===1){S.month=12;S.year--;}else{S.month--;} loadDots(); }
function nextMonth() { if(S.month===12){S.month=1;S.year++;}else{S.month++;} loadDots(); }

function loadDots() {
    api('get_calendar_dots', { year:S.year, month:S.month })
        .then(dates => { S.dots = new Set(dates); renderCalendar(); });
}

function selectDate(ds) {
    S.date = ds;
    renderCalendar();
    loadRes(ds);
}

// ── Month/year picker ────────────────────────────────────
let calPickerYear = new Date().getFullYear();

function toggleCalPicker(e) {
    if (e) e.stopPropagation();
    calPickerYear = S.year;
    const picker = document.getElementById('cal-picker');
    picker.classList.toggle('open');
    renderCalPicker();
}

function renderCalPicker() {
    const picker = document.getElementById('cal-picker');
    if (!picker.classList.contains('open')) return;
    document.getElementById('cp-year').textContent = calPickerYear;
    const grid = document.getElementById('cp-months');
    grid.innerHTML = '';
    MONTHS_SHORT.forEach((name, i) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'cp-month-btn' + (calPickerYear===S.year && i+1===S.month ? ' active' : '');
        btn.textContent = name;
        btn.onclick = e => {
            e.stopPropagation();
            S.month = i+1; S.year = calPickerYear;
            document.getElementById('cal-picker').classList.remove('open');
            loadDots();
        };
        grid.appendChild(btn);
    });
}

function calPickerYearAdj(delta) {
    calPickerYear += delta;
    renderCalPicker();
}

document.addEventListener('click', e => {
    const picker = document.getElementById('cal-picker');
    const title  = document.getElementById('cal-title');
    if (picker.classList.contains('open') && !picker.contains(e.target) && e.target !== title)
        picker.classList.remove('open');
});

// ═══════════════════════════════════════════════════════════
// TIMELINE
// ═══════════════════════════════════════════════════════════
const TL_START = 0;
const TL_END   = 24;
let   PX_PER_H = 60;

function toMin(t) { const [h,m]=t.split(':').map(Number); return h*60+m; }
function minToTop(min) { return (min - TL_START*60) * (PX_PER_H/60); }
function topToMin(px)  { return Math.round((px / (PX_PER_H/60) + TL_START*60) / 15) * 15; }
function fmtTime(min)  {
    const wrapped = min % 1440;
    const h=Math.floor(wrapped/60), m=wrapped%60, d=h===0?12:h>12?h-12:h;
    return `${d}:${String(m).padStart(2,'0')} ${h<12?'AM':'PM'}`;
}

function buildTimelineMarks() {
    const bodyH = (TL_END - TL_START) * PX_PER_H;
    document.getElementById('tl-body').style.height = bodyH + 'px';
    const marks = document.getElementById('tl-marks');
    marks.innerHTML = '';
    for (let h = TL_START; h <= TL_END; h++) {
        const top  = (h - TL_START) * PX_PER_H;
        const disp = h===0||h===24 ? '12:00 AM' : h<12 ? `${h}:00 AM` : h===12 ? '12:00 PM' : `${h-12}:00 PM`;
        const wrap = document.createElement('div');
        wrap.style.cssText = `position:absolute;top:${top}px;left:0;right:0;pointer-events:none;`;
        wrap.innerHTML =
            `<span style="position:absolute;left:2px;top:-9px;font-size:9px;color:#94a3b8;width:38px;text-align:right;line-height:1;">${disp}</span>` +
            `<div style="position:absolute;left:44px;right:0;border-top:1px solid #f1f5f9;"></div>`;
        marks.appendChild(wrap);
        if (h < TL_END) {
            const tick = document.createElement('div');
            tick.style.cssText = `position:absolute;top:${top+PX_PER_H/2}px;left:44px;right:0;border-top:1px dashed #f8fafc;pointer-events:none;`;
            marks.appendChild(tick);
        }
    }
}

function onScaleChange(val) {
    PX_PER_H = val;
    buildTimelineMarks();
    if (S.res.length) renderTimeline(S.res);
    document.getElementById('tl-selector').style.top = minToTop(tlSelectorMin) + 'px';
}

// ── Time selector drag ───────────────────────────────────
let tlSelectorMin = (() => {
    const now = new Date();
    const m   = Math.round((now.getHours()*60 + now.getMinutes()) / 15) * 15;
    return Math.max(TL_START*60, Math.min(TL_END*60, m));
})();

function updateSelectorDisplay() {
    document.getElementById('tl-sel-time').textContent = fmtTime(tlSelectorMin);
    document.getElementById('tl-selector').style.top   = minToTop(tlSelectorMin) + 'px';
}

(function initSelectorDrag() {
    const sel    = document.getElementById('tl-selector');
    let dragging = false, startY = 0, startTop = 0;

    sel.addEventListener('mousedown', e => {
        e.preventDefault(); e.stopPropagation();
        dragging = true; startY = e.clientY; startTop = parseInt(sel.style.top)||0;
        document.body.style.cursor = 'ns-resize'; document.body.style.userSelect = 'none';
    });

    document.addEventListener('mousemove', e => {
        if (!dragging) return;
        const maxTop = (TL_END - TL_START) * PX_PER_H;
        const newTop = Math.max(0, Math.min(maxTop, startTop + e.clientY - startY));
        tlSelectorMin = Math.max(TL_START*60, Math.min(TL_END*60, topToMin(newTop)));
        updateSelectorDisplay();
        syncSelectorToEditor();
    });

    document.addEventListener('mouseup', () => {
        if (!dragging) return;
        dragging = false; document.body.style.cursor = ''; document.body.style.userSelect = '';
    });

    // Click anywhere on tl-body to jump selector (skip clicks on res-blocks or sel itself)
    document.getElementById('tl-body').addEventListener('click', e => {
        if (sel.contains(e.target)) return;
        if (document.getElementById('res-blocks').contains(e.target)) return;
        const rect = document.getElementById('tl-body').getBoundingClientRect();
        tlSelectorMin = Math.max(TL_START*60, Math.min(TL_END*60, topToMin(e.clientY - rect.top)));
        updateSelectorDisplay();
        syncSelectorToEditor();
    });
})();

function syncSelectorToEditor() {
    if (!document.getElementById('editor-form-wrap').classList.contains('open')) return;
    const hh = String(Math.floor(tlSelectorMin/60)).padStart(2,'0');
    const mm = String(tlSelectorMin%60).padStart(2,'0');
    document.getElementById('f-start').value = `${hh}:${mm}`;
    recalcEndFromDur();
}

function loadRes(ds) {
    api('get_reservations', { date:ds })
        .then(rows => { S.res = rows; renderTimeline(rows); });
}

function renderTimeline(rows) {
    document.getElementById('tl-placeholder').classList.add('hidden');
    document.getElementById('tl-body').classList.remove('hidden');

    const container = document.getElementById('res-blocks');
    container.innerHTML = '';

    const evs = rows.map(r => ({
        ...r,
        _s: toMin(r.start_datetime.split(' ')[1]),
        _e: toMin(r.end_datetime.split(' ')[1]),
    })).sort((a,b) => a._s - b._s);

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

    const editingId = parseInt(document.getElementById('f-id').value) || null;

    evs.forEach(ev => {
        const top    = minToTop(ev._s);
        const height = Math.max(22, (ev._e - ev._s) * (PX_PER_H/60));
        const pct    = 100 / ev._nCols;
        const rooms  = (ev.rooms||[]).map(r=>r.name).join(', ');
        const c      = color(ev.rooms?.[0]?.id ?? 0);
        const recurIcon = (ev.is_recurring == 1) ? '<span title="Recurring" style="opacity:.6;margin-left:3px;">&#x1F501;</span>' : '';
        const label  = ev.title || ev.organization_name || rooms || 'Reservation';
        const sub    = (ev.title && ev.organization_name) ? ev.organization_name : rooms;

        const el = document.createElement('div');
        el.className = 'res-block' + (ev.id === editingId ? ' editing' : '');
        el.style.cssText = `top:${top}px;height:${height}px;left:calc(${ev._col*pct}%);width:calc(${pct}% - 2px);background:${c.bg};border-color:${c.bd};color:${c.tx};`;
        el.innerHTML =
            `<div style="font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${esc(label)}${recurIcon}</div>` +
            (sub ? `<div style="opacity:.75;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:10px;">${esc(sub)}</div>` : '');
        el.onclick = e => { e.stopPropagation(); openEditor(ev.id); };
        container.appendChild(el);
    });

    const now = new Date(), nowMin = now.getHours()*60+now.getMinutes();
    const nowLine = document.getElementById('now-line');
    if (S.date === fmtDate(now) && nowMin >= TL_START*60 && nowMin <= TL_END*60) {
        nowLine.style.top = minToTop(nowMin) + 'px';
        nowLine.classList.remove('hidden');
    } else {
        nowLine.classList.add('hidden');
    }
}

// ═══════════════════════════════════════════════════════════
// RESERVATION EDITOR
// ═══════════════════════════════════════════════════════════

function openEditor(resId = null) {
    resetForm();
    if (resId) {
        document.getElementById('editor-title').textContent = 'Edit Reservation';
        document.getElementById('del-btn').style.display = '';
        document.getElementById('f-id').value = resId;
        api('get_reservation', { id: resId }).then(r => {
            if (r.error) { alert(r.error); return; }
            document.getElementById('f-title').value = r.title || '';
            document.getElementById('f-date').value  = r.start_datetime.split(' ')[0];
            const startStr = r.start_datetime.split(' ')[1].slice(0,5);
            const endStr   = r.end_datetime.split(' ')[1].slice(0,5);
            document.getElementById('f-start').value = startStr;
            document.getElementById('f-end').value   = endStr;
            syncDurFromStartEnd();
            // Move selector to reservation start time
            tlSelectorMin = toMin(startStr);
            updateSelectorDisplay();
            document.getElementById('f-notes').value = r.notes || '';
            if (r.organization_id) {
                document.getElementById('f-org-id').value   = r.organization_id;
                document.getElementById('f-org-text').value = r.organization_name || '';
            }
            if (r.is_recurring == 1) {
                document.getElementById('f-recurring').checked = true;
                document.getElementById('f-recur-end').value   = r.recurrence_end_date || '';
                document.getElementById('recur-opts').style.display = 'block';
                deserializeRecurRule(r.recurrence_rule);
            }
            // Sync reservation rooms → picker → S.rooms
            picker.clearSelection();
            picker.selectRooms((r.rooms||[]).map(rm => rm.id));
            S.rooms = picker.getSelection();
            const map = {};
            (r.rooms||[]).forEach(rm => { map[rm.id] = rm.name; });
            setEditorRooms(map);
        });
    } else {
        document.getElementById('editor-title').textContent = 'New Reservation';
        document.getElementById('del-btn').style.display = 'none';
        if (S.date) document.getElementById('f-date').value = S.date;
        // Default start = current selector position
        const hh = String(Math.floor(tlSelectorMin/60)).padStart(2,'0');
        const mm = String(tlSelectorMin%60).padStart(2,'0');
        document.getElementById('f-start').value = `${hh}:${mm}`;
        document.getElementById('f-dur').value   = '60';
        recalcEndFromDur();
        // Pre-fill rooms from current selection
        const map = {};
        Object.values(S.rooms).forEach(r => { map[r.id] = r.name; });
        setEditorRooms(map);
    }
    document.getElementById('editor-empty').style.display = 'none';
    document.getElementById('editor-form-wrap').classList.add('open');
    if (S.date) loadRes(S.date);
}

function closeEditor() {
    document.getElementById('editor-form-wrap').classList.remove('open');
    document.getElementById('editor-empty').style.display = '';
    resetForm();
    if (S.date) loadRes(S.date);
}

function setEditorRooms(map) {
    const ids = Object.keys(map);
    document.getElementById('f-room-ids').value = ids.join(',');
    document.getElementById('f-rooms-display').innerHTML = ids.length
        ? ids.map(id=>`<span style="display:inline-flex;align-items:center;gap:3px;background:#dbeafe;color:#1e40af;font-size:11px;font-weight:600;border-radius:20px;padding:2px 8px;margin:1px;">${esc(map[id])}</span>`).join('')
        : '<span style="color:#9ca3af;font-size:12px;">No rooms — select from floor plan</span>';
}

function resetForm() {
    document.getElementById('res-form').reset();
    document.getElementById('f-id').value       = '';
    document.getElementById('f-org-id').value   = '';
    document.getElementById('f-org-text').value = '';
    document.getElementById('recur-opts').style.display        = 'none';
    document.getElementById('recur-weekly-days').style.display  = 'none';
    document.getElementById('recur-monthly-opts').style.display = 'none';
    RECUR_DAYS.forEach(d => { const cb = document.getElementById('recur-day-' + d); if (cb) cb.checked = false; });
    document.getElementById('monthly-by-day').checked = true;
    onMonthlyTypeChange();
    document.getElementById('f-rooms-display').innerHTML = '<span style="color:#9ca3af;font-size:12px;">No rooms selected</span>';
    document.getElementById('org-dropdown').classList.add('hidden');
}

const RECUR_DAYS    = ['SUN','MON','TUE','WED','THU','FRI','SAT'];
const RECUR_NTH     = ['','first','second','third','fourth'];

function toggleRecurring(on) {
    document.getElementById('recur-opts').style.display = on ? 'block' : 'none';
    if (on) { onRecurRuleChange(); autoFillRecurDefaults(); }
}

/** Show/hide sub-panels based on the selected frequency. */
function onRecurRuleChange() {
    const freq = document.getElementById('f-recur-rule').value;
    document.getElementById('recur-weekly-days').style.display  =
        (freq === 'weekly' || freq === 'biweekly') ? 'block' : 'none';
    document.getElementById('recur-monthly-opts').style.display =
        freq === 'monthly' ? 'block' : 'none';
}

/** Enable/disable the nth-weekday selects when the radio changes. */
function onMonthlyTypeChange() {
    const byNth = document.getElementById('monthly-by-nth').checked;
    document.getElementById('f-monthly-nth').disabled = !byNth;
    document.getElementById('f-monthly-day').disabled = !byNth;
}

/** Pre-fill recurrence defaults from the currently selected calendar date. */
function autoFillRecurDefaults() {
    const d = S.date ? new Date(S.date + 'T12:00:00') : new Date();
    const dow       = d.getDay();           // 0 Sun … 6 Sat
    const dayOfMon  = d.getDate();
    const daysInMon = new Date(d.getFullYear(), d.getMonth() + 1, 0).getDate();
    const isLast    = dayOfMon + 7 > daysInMon;
    const nthIdx    = Math.ceil(dayOfMon / 7);

    // Weekly: tick just the start-date's weekday
    RECUR_DAYS.forEach((name, i) => {
        const cb = document.getElementById('recur-day-' + name);
        if (cb) cb.checked = (i === dow);
    });

    // Monthly by-day: show the actual date number
    document.getElementById('monthly-day-num').textContent = dayOfMon;

    // Monthly by-nth: derive "first/second/… Saturday" from the date
    document.getElementById('f-monthly-nth').value = isLast ? 'last' : (RECUR_NTH[nthIdx] || 'first');
    document.getElementById('f-monthly-day').value = RECUR_DAYS[dow];

    // Default monthly sub-type to "by day"
    document.getElementById('monthly-by-day').checked = true;
    onMonthlyTypeChange();
}

/** Build the recurrence_rule string from the current form state. */
function serializeRecurRule() {
    const freq = document.getElementById('f-recur-rule').value;
    if (freq === 'daily') return 'daily';
    if (freq === 'weekly' || freq === 'biweekly') {
        const days = RECUR_DAYS.filter(d => {
            const cb = document.getElementById('recur-day-' + d);
            return cb && cb.checked;
        });
        // Fall back to the start-date weekday if nothing checked
        const fallback = S.date ? RECUR_DAYS[new Date(S.date + 'T12:00:00').getDay()] : 'MON';
        return `${freq}:${days.length ? days.join(',') : fallback}`;
    }
    if (freq === 'monthly') {
        if (document.getElementById('monthly-by-nth').checked) {
            const nth = document.getElementById('f-monthly-nth').value;
            const day = document.getElementById('f-monthly-day').value;
            return `monthly:nth:${nth}:${day}`;
        } else {
            const num = document.getElementById('monthly-day-num').textContent;
            return `monthly:day:${num}`;
        }
    }
    return freq;
}

/** Restore form state from a saved recurrence_rule string. */
function deserializeRecurRule(rule) {
    if (!rule) { autoFillRecurDefaults(); return; }
    const parts = rule.split(':');
    const freq  = parts[0];

    document.getElementById('f-recur-rule').value = freq;

    if (freq === 'weekly' || freq === 'biweekly') {
        const days = parts[1] ? parts[1].split(',') : [];
        RECUR_DAYS.forEach(d => {
            const cb = document.getElementById('recur-day-' + d);
            if (cb) cb.checked = days.includes(d);
        });
    }
    if (freq === 'monthly') {
        if (parts[1] === 'nth') {
            document.getElementById('monthly-by-nth').checked    = true;
            document.getElementById('f-monthly-nth').value       = parts[2] || 'first';
            document.getElementById('f-monthly-day').value       = parts[3] || 'SUN';
        } else {
            document.getElementById('monthly-by-day').checked    = true;
            document.getElementById('monthly-day-num').textContent = parts[2] || '1';
        }
    }
    onRecurRuleChange();
    onMonthlyTypeChange();
}

// ── Start / End / Duration sync ──────────────────────────
function onStartChange() {
    const val = document.getElementById('f-start').value;
    if (val) { tlSelectorMin = toMin(val); updateSelectorDisplay(); }
    recalcEndFromDur();
}
function onEndChange()  { syncDurFromStartEnd(); }
function onDurChange()  { recalcEndFromDur(); }

function recalcEndFromDur() {
    const start = document.getElementById('f-start').value;
    const dur   = parseInt(document.getElementById('f-dur').value);
    if (!start || isNaN(dur)) return;
    const endMin = toMin(start) + dur;
    if (endMin > 24*60) return;
    document.getElementById('f-end').value =
        String(Math.floor(endMin/60)).padStart(2,'0') + ':' + String(endMin%60).padStart(2,'0');
}

function syncDurFromStartEnd() {
    const start = document.getElementById('f-start').value;
    const end   = document.getElementById('f-end').value;
    if (!start || !end) return;
    const dur = toMin(end) - toMin(start);
    if (dur > 0 && dur <= 720) document.getElementById('f-dur').value = String(dur);
}

function saveReservation(e) {
    e.preventDefault();
    const date  = document.getElementById('f-date').value;
    const start = document.getElementById('f-start').value;
    const end   = document.getElementById('f-end').value;
    const data  = new FormData(document.getElementById('res-form'));
    data.set('action', 'save_reservation');
    data.set('start_datetime', `${date} ${start}:00`);
    data.set('end_datetime',   `${date} ${end}:00`);
    const isRecurring = document.getElementById('f-recurring').checked;
    data.set('is_recurring', isRecurring ? 1 : 0);
    if (isRecurring) data.set('recurrence_rule', serializeRecurRule());
    postApi(data).then(r => {
        if (r.error) { alert(r.error); return; }
        closeEditor(); loadDots(); if (S.date) loadRes(S.date);
    });
}

function deleteReservation() {
    const id = document.getElementById('f-id').value;
    if (!id) return;

    const isRecurring = document.getElementById('f-recurring').checked;

    if (isRecurring) {
        // Show the two-button modal
        document.getElementById('delete-recur-overlay').classList.add('open');
    } else {
        if (!confirm('Delete this reservation?')) return;
        const d = new FormData();
        d.set('action','delete_reservation'); d.set('id',id);
        postApi(d).then(r => { if(r.success){ closeEditor(); loadDots(); if(S.date) loadRes(S.date); } });
    }
}

function closeDeleteRecurModal() {
    document.getElementById('delete-recur-overlay').classList.remove('open');
}

function confirmDeleteRecur(choice) {
    closeDeleteRecurModal();
    const id = document.getElementById('f-id').value;
    if (!id) return;

    if (choice === 'this') {
        const d = new FormData();
        d.set('action', 'delete_recurring_instance');
        d.set('id', id);
        d.set('date', S.date);
        postApi(d).then(r => {
            if (r.success) { closeEditor(); loadDots(); if (S.date) loadRes(S.date); }
        });
    } else {
        const d = new FormData();
        d.set('action', 'delete_reservation');
        d.set('id', id);
        postApi(d).then(r => {
            if (r.success) { closeEditor(); loadDots(); if (S.date) loadRes(S.date); }
        });
    }
}

// ═══════════════════════════════════════════════════════════
// CREATABLE COMBOBOX — Organizations
// ═══════════════════════════════════════════════════════════
let orgTimer = null;

document.getElementById('f-org-text').addEventListener('input', function() {
    clearTimeout(orgTimer);
    document.getElementById('f-org-id').value = '';
    orgTimer = setTimeout(() => searchOrgs(this.value), 200);
});
document.getElementById('f-org-text').addEventListener('focus', function() { searchOrgs(this.value); });
document.addEventListener('click', e => {
    if (!document.getElementById('org-wrapper').contains(e.target))
        document.getElementById('org-dropdown').classList.add('hidden');
});

function searchOrgs(q) {
    api('get_organizations', { q }).then(orgs => {
        const dd = document.getElementById('org-dropdown');
        const trimmed = q.trim();
        const exact   = orgs.some(o => o.name.toLowerCase() === trimmed.toLowerCase());
        dd.innerHTML  = '';
        orgs.forEach(org => {
            const opt = document.createElement('div');
            opt.className = 'org-opt'; opt.textContent = org.name;
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
            create.className = 'org-opt create'; create.textContent = `+ Create "${trimmed}"`;
            create.onmousedown = ev => { ev.preventDefault(); createOrg(trimmed); };
            dd.appendChild(create);
        }
        dd.classList.toggle('hidden', !dd.children.length);
    });
}

function createOrg(name) {
    const d = new FormData();
    d.set('action','create_organization'); d.set('name',name);
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
function api(action, params={}) {
    return fetch(`${BASE_PATH}/api/reservations_api.php?${new URLSearchParams({action,...params})}`).then(r=>r.json());
}
function postApi(formData) {
    return fetch(BASE_PATH + '/api/reservations_api.php',{method:'POST',body:formData}).then(r=>r.json());
}
function fmtDate(d) {
    return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
}

// ═══════════════════════════════════════════════════════════
// INIT
// ═══════════════════════════════════════════════════════════
buildTimelineMarks();
updateSelectorDisplay();
loadDots();
// Floor plan picker initializes itself via its constructor
</script>

</body>
</html>
