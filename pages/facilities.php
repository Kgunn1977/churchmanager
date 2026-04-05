<?php
$pageTitle = 'Facilities — Church Facility Manager';
ob_start(); ?>
<style>
    * { box-sizing: border-box; }

    /* ── Toolbar ─────────────────────────────────────────────── */
    #toolbar {
        height: 52px;
        background: #1e3a5f;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 0 12px;
        flex-shrink: 0;
        color: white;
        font-family: ui-sans-serif, system-ui, sans-serif;
        font-size: 13px;
        user-select: none;
    }
    #toolbar button {
        background: rgba(255,255,255,0.12);
        color: white;
        border: 1px solid rgba(255,255,255,0.25);
        border-radius: 6px;
        padding: 4px 10px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        white-space: nowrap;
        transition: background 0.15s;
    }
    #toolbar button:hover { background: rgba(255,255,255,0.22); }
    #toolbar button:disabled { opacity: 0.35; cursor: not-allowed; }
    #toolbar button.active { background: #2563eb; border-color: #3b82f6; }
    .tb-sep { width: 1px; height: 28px; background: rgba(255,255,255,0.18); margin: 0 4px; flex-shrink: 0; }

    /* ── Main Layout ─────────────────────────────────────────── */
    #main-row {
        flex: 1;
        display: flex;
        flex-direction: row;
        overflow: hidden;
        min-height: 0;
    }

    /* ── Canvas ──────────────────────────────────────────────── */
    #canvas-wrap {
        flex: 1;
        position: relative;
        overflow: hidden;
        background: #e8ecf0;
    }
    #floor-svg {
        position: absolute;
        top: 0; left: 0;
        width: 100%; height: 100%;
        cursor: crosshair;
    }
    #floor-svg.select-mode  { cursor: default; }
    #floor-svg.pan-mode     { cursor: grab; }
    #floor-svg.pan-mode.panning { cursor: grabbing; }
    #floor-svg.move-mode    { cursor: default; }

    .grid-minor { stroke: #d0d5db; stroke-width: 1; vector-effect: non-scaling-stroke; }
    .grid-major { stroke: #b0b8c4; stroke-width: 3; vector-effect: non-scaling-stroke; }

    .room-poly { fill: rgba(59,130,246,0.13); stroke: #1d4ed8; stroke-width: 0.5; cursor: pointer; }
    .room-poly:hover { fill: rgba(59,130,246,0.24); }
    .room-poly.selected { fill: rgba(59,130,246,0.32); stroke: #1e40af; stroke-width: 0.7; }
    .room-poly.hlink-member { fill: rgba(168,85,247,0.13); stroke: #7c3aed; }
    .room-poly.hlink-member:hover { fill: rgba(168,85,247,0.24); }
    .room-poly.hlink-member.selected { fill: rgba(168,85,247,0.32); stroke: #6d28d9; stroke-width: 0.7; }
    .hlink-divider { stroke: #7c3aed; stroke-width: 1.5; stroke-dasharray: 3 2; vector-effect: non-scaling-stroke; pointer-events: none; }
    #floor-svg.move-mode .room-poly { cursor: move; }
    #floor-svg.pan-mode .room-poly { cursor: grab; }

    .v-handle { fill: white; stroke: #1d4ed8; stroke-width: 1.5; vector-effect: non-scaling-stroke; cursor: move; }
    .v-handle:hover { fill: #3b82f6; }
    .edge-hit { stroke: transparent; stroke-width: 2; fill: none; cursor: crosshair; }
    #draw-rect { fill: rgba(59,130,246,0.15); stroke: #2563eb; stroke-width: 1.5; stroke-dasharray: 5 3; }
    .room-label { font-family: ui-sans-serif, system-ui, sans-serif; fill: #1e3a5f; text-anchor: middle; dominant-baseline: middle; pointer-events: none; user-select: none; }

    #ctx-menu {
        display: none; position: fixed; background: white; border: 1px solid #e2e8f0;
        border-radius: 10px; box-shadow: 0 8px 24px rgba(0,0,0,0.14); z-index: 1000;
        min-width: 150px; overflow: hidden; font-family: ui-sans-serif, system-ui, sans-serif;
    }
    #ctx-menu button { display: flex; align-items: center; gap: 8px; width: 100%; padding: 9px 14px; font-size: 13px; text-align: left; background: none; border: none; cursor: pointer; color: #374151; }
    #ctx-menu button:hover { background: #f1f5f9; }
    #ctx-menu button.danger { color: #dc2626; }
    #ctx-menu button.danger:hover { background: #fef2f2; }

    #dlg-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 2000; align-items: center; justify-content: center; }
    #dlg-overlay.open { display: flex; }
    #dlg-box { background: white; border-radius: 16px; padding: 28px; width: 360px; box-shadow: 0 20px 60px rgba(0,0,0,0.2); font-family: ui-sans-serif, system-ui, sans-serif; }
    #dlg-box h3 { margin: 0 0 16px; font-size: 16px; font-weight: 700; color: #111827; }
    #dlg-box label { display: block; font-size: 12px; font-weight: 600; color: #6b7280; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.05em; }
    #dlg-box input, #dlg-box select { width: 100%; border: 1px solid #d1d5db; border-radius: 8px; padding: 8px 12px; font-size: 14px; margin-bottom: 14px; outline: none; color: #111827; }
    #dlg-box input:focus, #dlg-box select:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.15); }
    .dlg-actions { display: flex; gap: 8px; margin-top: 4px; }
    .dlg-actions button { flex: 1; padding: 9px; border-radius: 8px; font-size: 13px; font-weight: 700; cursor: pointer; border: none; transition: background 0.15s; }
    .btn-primary { background: #2563eb; color: white; } .btn-primary:hover { background: #1d4ed8; }
    .btn-cancel { background: #f3f4f6; color: #374151; } .btn-cancel:hover { background: #e5e7eb; }

    #status-bar { position: absolute; bottom: 10px; left: 50%; transform: translateX(-50%); background: rgba(30,58,95,0.82); color: white; font-size: 11px; padding: 4px 12px; border-radius: 20px; pointer-events: none; white-space: nowrap; font-family: ui-sans-serif, system-ui, sans-serif; }
    #zoom-btns { position: absolute; bottom: 14px; right: 14px; display: flex; flex-direction: column; gap: 4px; }
    #zoom-btns button { width: 32px; height: 32px; background: white; border: 1px solid #d1d5db; border-radius: 8px; font-size: 18px; line-height: 1; cursor: pointer; box-shadow: 0 2px 6px rgba(0,0,0,0.1); display: flex; align-items: center; justify-content: center; }
    #zoom-btns button:hover { background: #f1f5f9; }
    #empty-state { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; pointer-events: none; color: #9ca3af; font-family: ui-sans-serif, system-ui, sans-serif; }
    #empty-state svg { width: 64px; height: 64px; margin-bottom: 12px; opacity: 0.4; }
    #empty-state p { font-size: 15px; font-weight: 500; }
    #empty-state small { font-size: 12px; margin-top: 4px; }

    /* ── Room Info Pane ──────────────────────────────────────── */
    #room-pane { width: 400px; flex-shrink: 0; background: #f8fafc; border-left: 1px solid #d1d5db; display: flex; flex-direction: column; overflow: hidden; }
    #room-pane-header { padding: 10px 14px 8px; border-bottom: 1px solid #d1d5db; background: #e8ecf0; flex-shrink: 0; display: flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.07em; color: #6b7280; font-family: ui-sans-serif, system-ui, sans-serif; }
    #room-pane-body { flex: 1; overflow-y: auto; }
    #room-pane-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: #9ca3af; font-family: ui-sans-serif, system-ui, sans-serif; text-align: center; padding: 40px 24px; }
    #room-pane-empty svg { width: 48px; height: 48px; margin-bottom: 12px; opacity: 0.35; }
    #room-pane-empty p { font-size: 14px; font-weight: 500; margin: 0 0 4px; }
    #room-pane-empty small { font-size: 12px; }
    .rp-field { margin-bottom: 16px; }
    .rp-label { display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.07em; color: #6b7280; margin: 0; font-family: ui-sans-serif, system-ui, sans-serif; }
    .rp-input { width: 100%; border: 1px solid #d1d5db; border-radius: 8px; padding: 8px 11px; font-size: 13px; outline: none; font-family: ui-sans-serif, system-ui, sans-serif; color: #111827; background: white; transition: border-color 0.15s; }
    .rp-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.12); }
</style>
<?php
$extraHead = ob_get_clean();
$bodyAttr  = 'style="display:flex;flex-direction:column;height:100vh;overflow:hidden;"';
require_once __DIR__ . '/../includes/nav.php';
require_once __DIR__ . '/../config/database.php';
$db = getDB();
$buildings = $db->query("SELECT id, name FROM buildings ORDER BY name")->fetchAll();

// Floor plan picker config
$fp_id        = 'fac';
$fp_div_key   = 'fp_w_fac';
$fp_default_w = 340;
$fp_buildings = $buildings;
?>

<!-- ═══════════════════ TOOLBAR ═══════════════════ -->
<div id="toolbar">
    <button onclick="openDlg('new-building')">+ Building</button>
    <button onclick="openDlg('new-floor')">+ Floor</button>
    <button id="btn-new-room" disabled onclick="openDlg('new-room')">+ Room</button>
    <div class="tb-sep"></div>
    <button id="btn-select" class="active" onclick="setMode('select')" title="Select rooms">
        <svg style="width:12px;height:12px;display:inline-block;vertical-align:middle;margin-right:3px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-5.5 5.5L4 10l6-6 10.5 4.5L15 15zm0 0l5 5"/>
        </svg>Select
    </button>
    <button id="btn-draw" onclick="setMode('draw')" title="Draw / Edit mode">✏️ Draw</button>
    <button id="btn-move" onclick="setMode('move')" title="Move rooms">
        <svg style="width:13px;height:13px;display:inline-block;vertical-align:middle;margin-right:3px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4M8 15l4 4 4-4M4 12h16"/>
        </svg>Move
    </button>
    <button id="btn-pan" onclick="setMode('pan')" title="Pan mode">✋ Pan</button>
    <div class="tb-sep"></div>
    <button id="btn-link"   disabled onclick="doLink()"   title="Link selected rooms into a bookable group" style="background:rgba(245,158,11,0.2);border-color:rgba(245,158,11,0.5);">🔗 Link</button>
    <button id="btn-unlink" disabled onclick="doUnlink()" title="Remove link group for selected room" style="background:rgba(239,68,68,0.15);border-color:rgba(239,68,68,0.4);">✂ Unlink</button>
    <div class="tb-sep"></div>
    <button id="btn-copy" disabled onclick="doCopy()" title="Copy selected room">📋 Copy</button>
    <button id="btn-paste" disabled onclick="doPaste()" title="Paste room onto current floor">📌 Paste</button>
    <div style="flex:1"></div>
    <span id="floor-label" style="font-size:12px;color:rgba(255,255,255,0.6);font-style:italic;"></span>
</div>


<!-- Link modal -->
<div id="link-modal-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:3000;align-items:center;justify-content:center;">
    <div style="background:white;border-radius:16px;padding:28px;width:360px;box-shadow:0 20px 60px rgba(0,0,0,0.2);font-family:ui-sans-serif,system-ui,sans-serif;">
        <h3 style="margin:0 0 6px;font-size:16px;font-weight:700;color:#111827;">🔗 Link Rooms</h3>
        <p id="link-modal-desc" style="font-size:12px;color:#6b7280;margin:0 0 16px;"></p>
        <label style="display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:4px;">Link Name</label>
        <input id="link-name-input" type="text" placeholder="e.g. Fellowship Hall"
               style="width:100%;border:1px solid #d1d5db;border-radius:8px;padding:8px 12px;font-size:14px;margin-bottom:16px;outline:none;color:#111827;box-sizing:border-box;"
               onkeydown="if(event.key==='Enter')confirmLink()">
        <div style="display:flex;gap:8px;">
            <button onclick="confirmLink()" style="flex:1;padding:9px;background:#2563eb;color:white;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;">Link Rooms</button>
            <button onclick="closeLinkModal()" style="flex:1;padding:9px;background:#f3f4f6;color:#374151;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;">Cancel</button>
        </div>
    </div>
</div>

<!-- ═══════════════════ MAIN ROW ═══════════════════ -->
<div id="main-row">

<!-- ═══════════════════ CANVAS ═══════════════════ -->
<?php require_once __DIR__ . '/../includes/floor_plan_picker.php'; ?>
<div id="canvas-wrap">
    <div id="empty-state">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
            <polyline stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" points="9 22 9 12 15 12 15 22"/>
        </svg>
        <p>Click a room in the floor plan picker to begin</p>
        <small>Or use the toolbar to create a building and floor</small>
    </div>

    <svg id="floor-svg" xmlns="http://www.w3.org/2000/svg" style="display:none;">
        <defs>
            <pattern id="grid-minor" width="1" height="1" patternUnits="userSpaceOnUse">
                <path d="M 1 0 L 0 0 0 1" fill="none" class="grid-minor" vector-effect="non-scaling-stroke"/>
            </pattern>
            <pattern id="grid-major" width="10" height="10" patternUnits="userSpaceOnUse">
                <rect width="10" height="10" fill="url(#grid-minor)"/>
                <path d="M 10 0 L 0 0 0 10" fill="none" class="grid-major" vector-effect="non-scaling-stroke"/>
            </pattern>
        </defs>
        <rect id="grid-bg" fill="url(#grid-major)" />
        <!-- Origin marker at (0,0) -->
        <g id="origin-marker" pointer-events="none">
            <!-- Axis lines -->
            <line x1="-2" y1="0" x2="2" y2="0" stroke="#ef4444" stroke-width="0.25" vector-effect="non-scaling-stroke"/>
            <line x1="0" y1="-2" x2="0" y2="2" stroke="#ef4444" stroke-width="0.25" vector-effect="non-scaling-stroke"/>
            <!-- Dot at origin -->
            <circle cx="0" cy="0" r="0.6" fill="#ef4444" vector-effect="non-scaling-stroke"/>
            <!-- "0,0" label -->
            <text x="0.8" y="-0.4" font-size="1.8" fill="#ef4444" font-family="ui-sans-serif,system-ui,sans-serif" font-weight="700" vector-effect="non-scaling-stroke">0,0</text>
        </g>
        <g id="rooms-layer"></g>
        <rect id="draw-rect" style="display:none;" />
        <g id="handles-layer"></g>
    </svg>

    <div id="zoom-btns">
        <button onclick="zoomBy(1.25)" title="Zoom in">+</button>
        <button onclick="zoomBy(0.8)" title="Zoom out">−</button>
        <button onclick="resetView()" title="Fit to window" style="font-size:11px;font-weight:700;">FIT</button>
    </div>
    <div id="status-bar">Click a room in the floor plan picker to load that floor</div>
</div>

<!-- ═══════════════════ ROOM PANE ═══════════════════ -->
<div id="room-pane">
    <div id="room-pane-header">
        <svg style="width:12px;height:12px;opacity:0.5;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        Room Details
    </div>
    <div id="room-pane-body">
        <div id="room-pane-empty">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p>No room selected</p>
            <small>Click a room on the floor plan to view its details</small>
        </div>
    </div>
</div>


</div><!-- end main-row -->

<!-- ═══════════════════ CONTEXT MENU ═══════════════════ -->
<div id="ctx-menu">
    <button onclick="ctxRename()">
        <svg style="width:14px;height:14px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
        </svg>
        Rename Room
    </button>
    <button class="danger" onclick="ctxDelete()">
        <svg style="width:14px;height:14px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
        </svg>
        Delete Room
    </button>
</div>

<!-- ═══════════════════ DIALOG ═══════════════════ -->
<div id="dlg-overlay">
    <div id="dlg-box">
        <h3 id="dlg-title">Dialog</h3>
        <div id="dlg-body"></div>
        <div class="dlg-actions">
            <button class="btn-cancel" onclick="closeDlg()">Cancel</button>
            <button class="btn-primary" id="dlg-ok" onclick="dlgSubmit()">OK</button>
        </div>
    </div>
</div>

<!-- ═══════════════════ JAVASCRIPT ═══════════════════ -->
<script>
// ─────────────────────────────────────────────────────────────────────────────
// STATE
// ─────────────────────────────────────────────────────────────────────────────
const API = BASE_PATH + '/api/floor_editor_api.php';

// Buildings list (PHP-injected, kept in sync as buildings are created)
let facBuildings = <?= json_encode(array_map(fn($b) => ['id' => (int)$b['id'], 'name' => $b['name']], $buildings)) ?>;

let state = {
    buildingId:     null,
    floorId:        null,
    rooms:          [],
    selectedRoomIds: new Set(),   // multi-select
    activeRoomId:   null,         // room with vertex handles in draw mode
    mode:           'select',     // 'select' | 'draw' | 'move' | 'pan'
    vx: 0, vy: 0, vw: 100, vh: 100,
    dragging:       null,
    drawStart:      null,
    panStart:       null,
    panVStart:      null,
    movingRoomIds:  [],
    movingOffset:   null,
    dlgType:        null,
    pendingPolygon: null,
};

// Clipboard for room copy/paste
let _clipboardRoom = null; // { name, abbreviation, capacity, map_points }

// ─────────────────────────────────────────────────────────────────────────────
// SVG HELPERS
// ─────────────────────────────────────────────────────────────────────────────
const svg      = document.getElementById('floor-svg');
const roomsL   = document.getElementById('rooms-layer');
const handlesL = document.getElementById('handles-layer');
const gridBg   = document.getElementById('grid-bg');
const drawRect = document.getElementById('draw-rect');
const statusEl = document.getElementById('status-bar');
const emptyEl  = document.getElementById('empty-state');

function svgPt(clientX, clientY) {
    const pt = svg.createSVGPoint();
    pt.x = clientX; pt.y = clientY;
    return pt.matrixTransform(svg.getScreenCTM().inverse());
}
function snap(v) { return Math.round(v); }

function setViewBox(x, y, w, h) {
    state.vx = x; state.vy = y; state.vw = w; state.vh = h;
    svg.setAttribute('viewBox', `${x} ${y} ${w} ${h}`);
    document.getElementById('grid-minor').setAttribute('x', 0);
    document.getElementById('grid-minor').setAttribute('y', 0);
    document.getElementById('grid-major').setAttribute('x', 0);
    document.getElementById('grid-major').setAttribute('y', 0);
    gridBg.setAttribute('x', x - 10); gridBg.setAttribute('y', y - 10);
    gridBg.setAttribute('width', w + 20); gridBg.setAttribute('height', h + 20);
}
function resetView() {
    if (!state.rooms.length) { setViewBox(0, 0, 80, 60); return; }
    let minX=Infinity, minY=Infinity, maxX=-Infinity, maxY=-Infinity;
    for (const r of state.rooms) {
        if (!r.map_points) continue;
        for (const [x,y] of r.map_points) { minX=Math.min(minX,x); minY=Math.min(minY,y); maxX=Math.max(maxX,x); maxY=Math.max(maxY,y); }
    }
    if (!isFinite(minX)) { setViewBox(0,0,80,60); return; }
    const pad = 8;
    setViewBox(minX-pad, minY-pad, (maxX-minX)+pad*2, (maxY-minY)+pad*2);
}
function zoomBy(factor) {
    const cx = state.vx + state.vw/2, cy = state.vy + state.vh/2;
    setViewBox(cx - state.vw*factor/2, cy - state.vh*factor/2, state.vw*factor, state.vh*factor);
}
function getRoomBounds(pts) {
    let minX=Infinity, minY=Infinity, maxX=-Infinity, maxY=-Infinity;
    for (const [x,y] of pts) { minX=Math.min(minX,x); minY=Math.min(minY,y); maxX=Math.max(maxX,x); maxY=Math.max(maxY,y); }
    return {minX, minY, maxX, maxY};
}

// ─────────────────────────────────────────────────────────────────────────────
// TEXT WRAPPING
// ─────────────────────────────────────────────────────────────────────────────
function wrapRoomLabel(text, pts) {
    const b = getRoomBounds(pts);
    const roomW = b.maxX - b.minX;
    const roomH = b.maxY - b.minY;
    const fontSize = 2;            // 2 ft
    const charW    = fontSize * 0.58;
    const maxChars = Math.max(3, Math.floor(roomW / charW));
    const words    = text.split(' ');
    const lines    = [];
    let cur        = '';
    for (const word of words) {
        const test = cur ? cur + ' ' + word : word;
        if (test.length <= maxChars) { cur = test; }
        else {
            if (cur) lines.push(cur);
            cur = word.length > maxChars ? word.substring(0, maxChars - 1) + '…' : word;
        }
    }
    if (cur) lines.push(cur);
    const maxLines = Math.max(1, Math.floor(roomH / (fontSize * 1.4)));
    return lines.slice(0, maxLines);
}

// ─────────────────────────────────────────────────────────────────────────────
// RENDER
// ─────────────────────────────────────────────────────────────────────────────
function render() {
    roomsL.innerHTML = '';
    handlesL.innerHTML = '';
    for (const room of state.rooms) {
        if (!room.map_points || room.map_points.length < 3) continue;
        renderRoom(room);
    }
    if (state.activeRoomId !== null) {
        const room = state.rooms.find(r => r.id === state.activeRoomId);
        if (room && room.map_points) renderHandles(room);
    }
}

function pointsAttr(pts) { return pts.map(([x,y]) => `${x},${y}`).join(' '); }
// polyCenter — uses _fpCenter from floor_plan_picker.php (polylabel algorithm)
function polyCenter(pts) { return _fpCenter(pts); }

function renderRoom(room) {
    const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
    const isSelected = state.selectedRoomIds.has(room.id);

    const poly = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
    poly.setAttribute('points', pointsAttr(room.map_points));
    poly.setAttribute('class', 'room-poly' + (isSelected ? ' selected' : ''));
    poly.dataset.roomId = room.id;

    poly.addEventListener('mousedown', e => {
        if (e.button !== 0) return;
        e.stopPropagation();

        if (state.mode === 'select') {
            if (isSelected) { state.selectedRoomIds.delete(room.id); }
            else            { state.selectedRoomIds.add(room.id); }
            updateRoomPane();
            render();
            return;
        }
        if (state.mode === 'move' && room.map_points) {
            // If clicked room isn't selected, replace selection with just this room
            if (!state.selectedRoomIds.has(room.id)) {
                state.selectedRoomIds = new Set([room.id]);
                updateRoomPane(); render();
            }
            const pt = svgPt(e.clientX, e.clientY);
            // Collect all selected rooms that have map_points
            const ids = [...state.selectedRoomIds].filter(id => {
                const r = state.rooms.find(r2 => r2.id === id);
                return r && r.map_points;
            });
            const origPtsByRoomId = {};
            ids.forEach(id => {
                const r = state.rooms.find(r2 => r2.id === id);
                origPtsByRoomId[id] = r.map_points.map(p => [...p]);
            });
            state.movingRoomIds = ids;
            state.movingOffset  = { ox: pt.x, oy: pt.y, origPtsByRoomId };
            return;
        }
        if (state.mode === 'draw') {
            if (state.activeRoomId !== room.id) {
                state.activeRoomId = room.id;
                state.selectedRoomIds = new Set([room.id]);
                updateRoomPane();
                render();
            }
        }
    });

    g.appendChild(poly);

    // Wrapped label
    const [cx, cy] = polyCenter(room.map_points);
    const lines    = wrapRoomLabel(room.name, room.map_points);
    const fontSize = 2;
    const lineH    = fontSize * 1.3;
    const startDy  = -((lines.length - 1) * lineH) / 2;

    const txt = document.createElementNS('http://www.w3.org/2000/svg', 'text');
    txt.setAttribute('x', cx);
    txt.setAttribute('y', cy);
    txt.setAttribute('class', 'room-label');
    txt.setAttribute('font-size', String(fontSize));
    lines.forEach((line, i) => {
        const ts = document.createElementNS('http://www.w3.org/2000/svg', 'tspan');
        ts.setAttribute('x', cx);
        ts.setAttribute('dy', i === 0 ? String(startDy) : String(lineH));
        ts.textContent = line;
        txt.appendChild(ts);
    });
    g.appendChild(txt);
    roomsL.appendChild(g);
}

function renderHandles(room) {
    const pts = room.map_points;
    for (let i = 0; i < pts.length; i++) {
        const j = (i + 1) % pts.length;
        const [x1,y1]=pts[i], [x2,y2]=pts[j];
        const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        line.setAttribute('x1',x1); line.setAttribute('y1',y1);
        line.setAttribute('x2',x2); line.setAttribute('y2',y2);
        line.setAttribute('class','edge-hit');
        line.dataset.edgeA = i; line.dataset.edgeB = j; line.dataset.roomId = room.id;
        line.addEventListener('mousedown', onEdgeMouseDown);
        handlesL.appendChild(line);
    }
    for (let i = 0; i < pts.length; i++) {
        const [x,y]=pts[i];
        const c = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        c.setAttribute('cx',x); c.setAttribute('cy',y); c.setAttribute('r','0.75');
        c.setAttribute('class','v-handle');
        c.dataset.vi = i; c.dataset.roomId = room.id;
        c.addEventListener('mousedown', onVertexMouseDown);
        handlesL.appendChild(c);
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// FLOOR SELECTION (from pane click)
// ─────────────────────────────────────────────────────────────────────────────
async function selectFloor(floor) {
    state.buildingId = floor.building_id || null;
    state.floorId    = floor.id;
    state.rooms      = [];
    state.selectedRoomIds.clear();
    state.activeRoomId = null;

    document.getElementById('btn-new-room').disabled = false;
    const label = (floor.building_name ? floor.building_name + ' › ' : '') + floor.name;
    document.getElementById('floor-label').textContent = label;

    showCanvas(true);
    await loadRooms();
    updateRoomPane();
}

async function loadRooms() {
    if (!state.floorId) return;
    const data = await apiFetch(`get_rooms&floor_id=${state.floorId}`);
    state.rooms = data;
    resetView();
    render();
    setStatus(`${state.rooms.length} room(s) — click to select`);
}

function showCanvas(show) {
    svg.style.display = show ? 'block' : 'none';
    emptyEl.style.display = show ? 'none' : 'flex';
    if (show) {
        const wrap = document.getElementById('canvas-wrap');
        setViewBox(0, 0, Math.max(60, wrap.clientWidth/96), Math.max(40, wrap.clientHeight/96));
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// MODE
// ─────────────────────────────────────────────────────────────────────────────
function setMode(m) {
    state.mode = m;
    state.movingRoomIds = []; state.movingOffset = null;
    if (m !== 'draw') state.activeRoomId = null;
    svg.classList.toggle('select-mode', m === 'select');
    svg.classList.toggle('pan-mode',    m === 'pan');
    svg.classList.toggle('move-mode',   m === 'move');
    document.getElementById('btn-select').classList.toggle('active', m === 'select');
    document.getElementById('btn-draw').classList.toggle('active',   m === 'draw');
    document.getElementById('btn-move').classList.toggle('active',   m === 'move');
    document.getElementById('btn-pan').classList.toggle('active',    m === 'pan');
    render();
}

// ─────────────────────────────────────────────────────────────────────────────
// SVG MOUSE EVENTS
// ─────────────────────────────────────────────────────────────────────────────
svg.addEventListener('mousedown', onSvgMouseDown);
svg.addEventListener('mousemove', onSvgMouseMove);
svg.addEventListener('mouseup',   onSvgMouseUp);
svg.addEventListener('contextmenu', onSvgContextMenu);
svg.addEventListener('wheel', onWheel, { passive: false });
document.addEventListener('mouseup',   onSvgMouseUp);
document.addEventListener('mousemove', onSvgMouseMove);
document.addEventListener('click', e => {
    if (!document.getElementById('ctx-menu').contains(e.target)) closeCtxMenu();
});

function onSvgContextMenu(e) {
    e.preventDefault();
    // Right-click on a room poly → context menu
    let el = e.target;
    while (el && el !== svg) {
        if (el.classList && el.classList.contains('room-poly')) {
            const rid = parseInt(el.dataset.roomId);
            showCtxMenu(rid, e.clientX, e.clientY);
            return;
        }
        el = el.parentNode;
    }
    closeCtxMenu();
}

function onSvgMouseDown(e) {
    if (e.button !== 0) return;
    closeCtxMenu();

    if (state.mode === 'pan') {
        state.panStart  = { cx: e.clientX, cy: e.clientY };
        state.panVStart = { vx: state.vx, vy: state.vy };
        svg.classList.add('panning');
        return;
    }
    if (state.mode === 'move') return;

    // Clicking blank space
    if (e.target === svg || e.target === gridBg) {
        state.selectedRoomIds.clear();
        state.activeRoomId = null;
        updateRoomPane();
        render();

        if (state.mode === 'draw') {
            const pt = svgPt(e.clientX, e.clientY);
            const sx = snap(pt.x), sy = snap(pt.y);
            state.drawStart = { x: sx, y: sy };
            drawRect.setAttribute('x', sx); drawRect.setAttribute('y', sy);
            drawRect.setAttribute('width', 0); drawRect.setAttribute('height', 0);
            drawRect.style.display = 'block';
        }
    }
}

function onSvgMouseMove(e) {
    const pt = svgPt(e.clientX, e.clientY);
    const sx = snap(pt.x), sy = snap(pt.y);
    setStatus(`x: ${sx} ft,  y: ${sy} ft${state.floorId ? '' : ' — no floor selected'}`);

    if (state.mode === 'pan' && state.panStart) {
        const dx = (e.clientX - state.panStart.cx) * state.vw / svg.clientWidth;
        const dy = (e.clientY - state.panStart.cy) * state.vh / svg.clientHeight;
        setViewBox(state.panVStart.vx - dx, state.panVStart.vy - dy, state.vw, state.vh);
        return;
    }
    if (state.drawStart) {
        const x=Math.min(sx,state.drawStart.x), y=Math.min(sy,state.drawStart.y);
        const w=Math.abs(sx-state.drawStart.x), h=Math.abs(sy-state.drawStart.y);
        drawRect.setAttribute('x',x); drawRect.setAttribute('y',y);
        drawRect.setAttribute('width',w); drawRect.setAttribute('height',h);
        return;
    }
    if (state.dragging?.type === 'vertex') {
        const d=state.dragging, room=state.rooms.find(r=>r.id===d.roomId);
        if (!room) return;
        const pts=room.map_points;
        pts[d.vi]=[sx,sy];
        const prev=(d.vi-1+pts.length)%pts.length, next=(d.vi+1)%pts.length;
        const dist=(idx)=>{ const [ox,oy]=pts[idx]; return Math.sqrt((sx-ox)**2+(sy-oy)**2); };
        if (pts.length > 3) {
            if (dist(prev)<1.5 && prev!==d.vi) { pts.splice(d.vi,1); d.vi=prev>d.vi?prev-1:prev; state.dragging.vi=d.vi; }
            else if (dist(next)<1.5 && next!==d.vi) { const ri=next; pts.splice(ri,1); if(ri<d.vi)d.vi--; state.dragging.vi=d.vi; }
        }
        render(); return;
    }
    if (state.dragging?.type === 'edge') {
        const d=state.dragging, room=state.rooms.find(r=>r.id===d.roomId);
        if (!room) return;
        room.map_points[d.vi]=[sx,sy]; render(); return;
    }
    if (state.movingRoomIds.length && state.movingOffset) {
        const rp=svgPt(e.clientX,e.clientY);
        const dx=snap(rp.x)-snap(state.movingOffset.ox), dy=snap(rp.y)-snap(state.movingOffset.oy);
        for (const id of state.movingRoomIds) {
            const r=state.rooms.find(r2=>r2.id===id);
            if (!r||!r.map_points) continue;
            r.map_points=state.movingOffset.origPtsByRoomId[id].map(([x,y])=>[x+dx,y+dy]);
        }
        render(); return;
    }
}

async function onSvgMouseUp(e) {
    if (e.button !== 0) return;
    svg.classList.remove('panning');
    state.panStart = null;

    if (state.drawStart) {
        const pt=svgPt(e.clientX,e.clientY);
        const ex=snap(pt.x), ey=snap(pt.y);
        const x=Math.min(ex,state.drawStart.x), y=Math.min(ey,state.drawStart.y);
        const w=Math.abs(ex-state.drawStart.x), h=Math.abs(ey-state.drawStart.y);
        state.drawStart=null; drawRect.style.display='none';
        if (w>=2 && h>=2 && state.floorId) {
            state.pendingPolygon=[[x,y],[x+w,y],[x+w,y+h],[x,y+h]];
            openDlg('new-room-draw');
        }
        return;
    }
    if (state.dragging) {
        const room=state.rooms.find(r=>r.id===state.dragging.roomId);
        if (room) await saveRoom(room);
        state.dragging=null; render(); return;
    }
    if (state.movingRoomIds.length) {
        await Promise.all(state.movingRoomIds.map(id => {
            const r=state.rooms.find(r2=>r2.id===id);
            return r ? saveRoom(r) : Promise.resolve();
        }));
        state.movingRoomIds=[]; state.movingOffset=null; svg.style.cursor=''; render(); return;
    }
}

function onWheel(e) {
    e.preventDefault();
    const factor=e.deltaY>0?1.12:0.89, pt=svgPt(e.clientX,e.clientY);
    const nw=state.vw*factor, nh=state.vh*factor;
    setViewBox(pt.x-(pt.x-state.vx)*factor, pt.y-(pt.y-state.vy)*factor, nw, nh);
}

// ─────────────────────────────────────────────────────────────────────────────
// VERTEX / EDGE DRAG
// ─────────────────────────────────────────────────────────────────────────────
function onVertexMouseDown(e) {
    if (e.button!==0) return; e.stopPropagation();
    state.dragging={type:'vertex', roomId:parseInt(e.target.dataset.roomId), vi:parseInt(e.target.dataset.vi)};
}
function onEdgeMouseDown(e) {
    if (e.button!==0) return; e.stopPropagation();
    const roomId=parseInt(e.target.dataset.roomId), iA=parseInt(e.target.dataset.edgeA), iB=parseInt(e.target.dataset.edgeB);
    const room=state.rooms.find(r=>r.id===roomId); if(!room) return;
    const [x1,y1]=room.map_points[iA], [x2,y2]=room.map_points[iB];
    const mx=snap((x1+x2)/2), my=snap((y1+y2)/2);
    const insertAt=iB===0?room.map_points.length:iB;
    room.map_points.splice(insertAt,0,[mx,my]);
    state.dragging={type:'edge', roomId, vi:insertAt}; render();
}

// ─────────────────────────────────────────────────────────────────────────────
// CONTEXT MENU
// ─────────────────────────────────────────────────────────────────────────────
const ctxMenu=document.getElementById('ctx-menu');
let ctxRoomId=null;

function showCtxMenu(roomId, cx, cy) {
    ctxRoomId=roomId;
    // ensure the right-clicked room is selected
    if (!state.selectedRoomIds.has(roomId)) {
        state.selectedRoomIds.add(roomId);
        updateRoomPane(); render();
    }
    ctxMenu.style.display='block'; ctxMenu.style.left=cx+'px'; ctxMenu.style.top=cy+'px';
    const rect=ctxMenu.getBoundingClientRect();
    if(rect.right>window.innerWidth) ctxMenu.style.left=(cx-rect.width)+'px';
    if(rect.bottom>window.innerHeight) ctxMenu.style.top=(cy-rect.height)+'px';
}
function closeCtxMenu() { ctxMenu.style.display='none'; ctxRoomId=null; }

function ctxRename() {
    const room=state.rooms.find(r=>r.id===ctxRoomId);
    closeCtxMenu(); if(!room) return;
    openDlg('rename-room', room);
}
async function ctxDelete() {
    const room=state.rooms.find(r=>r.id===ctxRoomId);
    closeCtxMenu(); if(!room) return;
    if(!confirm(`Delete room "${room.name}"? This cannot be undone.`)) return;
    await apiPost('delete_room',{id:room.id});
    state.rooms=state.rooms.filter(r=>r.id!==room.id);
    state.selectedRoomIds.delete(room.id);
    if(state.activeRoomId===room.id) state.activeRoomId=null;
    facPicker.load();
    render(); updateRoomPane();
    setStatus(`Room "${room.name}" deleted`);
}

// ─────────────────────────────────────────────────────────────────────────────
// DIALOG
// ─────────────────────────────────────────────────────────────────────────────
const dlgOverlay=document.getElementById('dlg-overlay');
const dlgTitle=document.getElementById('dlg-title');
const dlgBody=document.getElementById('dlg-body');
let dlgData={};

function openDlg(type, extra) {
    state.dlgType=type; dlgData=extra||{}; dlgBody.innerHTML='';
    if (type==='new-building') {
        dlgTitle.textContent='New Building';
        dlgBody.innerHTML=`<label>Building Name</label><input type="text" id="dlg-f1" placeholder="e.g. Main Sanctuary" autofocus />`;
    }
    else if (type==='new-floor') {
        dlgTitle.textContent='New Floor';
        let opts='';
        for (const b of facBuildings) {
            const sel=(b.id===state.buildingId)?' selected':'';
            opts+=`<option value="${b.id}"${sel}>${escHtml(b.name)}</option>`;
        }
        if (!opts) opts='<option value="">— Add a building first —</option>';
        dlgBody.innerHTML=`<label>Building</label><select id="dlg-f1">${opts}</select><label>Floor Name</label><input type="text" id="dlg-f2" placeholder="e.g. Ground Floor" />`;
    }
    else if (type==='new-room' || type==='new-room-draw') {
        dlgTitle.textContent='New Room';
        dlgBody.innerHTML=`<label>Room Name</label><input type="text" id="dlg-f1" placeholder="e.g. Sanctuary" autofocus /><label>Abbreviation (optional)</label><input type="text" id="dlg-f2" placeholder="e.g. SANC" maxlength="10" />`;
        if (type==='new-room') dlgBody.innerHTML+=`<p style="font-size:12px;color:#6b7280;margin:0 0 4px;">After naming, draw the room shape on the canvas.</p>`;
    }
    else if (type==='rename-room') {
        dlgTitle.textContent='Rename Room';
        dlgBody.innerHTML=`<label>Room Name</label><input type="text" id="dlg-f1" value="${escHtml(dlgData.name)}" autofocus />`;
    }
    dlgOverlay.classList.add('open');
    setTimeout(()=>{ const f=document.getElementById('dlg-f1'); if(f) f.focus(); },50);
}
function closeDlg() { dlgOverlay.classList.remove('open'); state.dlgType=null; state.pendingPolygon=null; }
document.getElementById('dlg-box').addEventListener('keydown', e=>{
    if(e.key==='Enter'){e.preventDefault();dlgSubmit();}
    if(e.key==='Escape') closeDlg();
});

async function dlgSubmit() {
    const type=state.dlgType; if(!type) return;

    if (type==='new-building') {
        const name=document.getElementById('dlg-f1').value.trim(); if(!name) return;
        closeDlg();
        const result=await apiPost('create_building',{name});
        if(result.error){alert(result.error);return;}
        facBuildings.push({id: result.id, name: result.name});
        facPicker.load();
        setStatus(`Building "${result.name}" created`);
    }
    else if (type==='new-floor') {
        const bid=parseInt(document.getElementById('dlg-f1').value)||0;
        const name=document.getElementById('dlg-f2').value.trim();
        if(!bid||!name) return;
        closeDlg();
        const result=await apiPost('create_floor',{building_id:bid,name});
        if(result.error){alert(result.error);return;}
        await facPicker.load();
        const bldName = facBuildings.find(b=>b.id===bid)?.name||'';
        await selectFloor({id: result.id, building_id: bid, name, building_name: bldName});
        setStatus(`Floor "${result.name}" created`);
    }
    else if (type==='new-room') {
        const name=document.getElementById('dlg-f1').value.trim();
        const abbr=document.getElementById('dlg-f2').value.trim();
        if(!name||!state.floorId) return;
        closeDlg();
        const result=await apiPost('create_room',{floor_id:state.floorId,name,abbreviation:abbr});
        if(result.error){alert(result.error);return;}
        state.rooms.push(result);
        state.selectedRoomIds=new Set([result.id]);
        state.activeRoomId=null;
        facPicker.load();
        render(); updateRoomPane();
        setStatus(`Room "${name}" created — draw its shape on the canvas`);
    }
    else if (type==='new-room-draw') {
        const name=document.getElementById('dlg-f1').value.trim();
        const abbr=document.getElementById('dlg-f2').value.trim();
        if(!name||!state.floorId||!state.pendingPolygon) return;
        const polygon=state.pendingPolygon; closeDlg();
        const result=await apiPost('create_room',{floor_id:state.floorId,name,abbreviation:abbr});
        if(result.error){alert(result.error);return;}
        result.map_points=polygon;
        await apiPost('save_room',{id:result.id,map_points:polygon});
        state.rooms.push(result);
        state.selectedRoomIds=new Set([result.id]);
        facPicker.load();
        render(); updateRoomPane();
        setStatus(`Room "${name}" created`);
    }
    else if (type==='rename-room') {
        const name=document.getElementById('dlg-f1').value.trim(); if(!name) return;
        closeDlg();
        const room=state.rooms.find(r=>r.id===dlgData.id); if(!room) return;
        room.name=name;
        await saveRoom(room);
        render(); updateRoomPane();
        setStatus(`Room renamed to "${name}"`);
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// API
// ─────────────────────────────────────────────────────────────────────────────
async function apiFetch(actionAndParams) {
    const res=await fetch(`${API}?action=${actionAndParams}`); return res.json();
}
async function apiPost(action, body) {
    const res=await fetch(`${API}?action=${action}`,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)});
    return res.json();
}
async function saveRoom(room) {
    const res=await apiPost('save_room',{id:room.id,name:room.name,map_points:room.map_points});
    facPicker.load();
    return res;
}

// ─────────────────────────────────────────────────────────────────────────────
// COPY / PASTE
// ─────────────────────────────────────────────────────────────────────────────
function doCopy() {
    const ids = [...state.selectedRoomIds];
    if (ids.length !== 1) return;
    const room = state.rooms.find(r => r.id === ids[0]);
    if (!room) return;
    _clipboardRoom = {
        name:         room.name,
        abbreviation: room.abbreviation || '',
        capacity:     room.capacity,
        map_points:   room.map_points ? JSON.parse(JSON.stringify(room.map_points)) : null
    };
    document.getElementById('btn-paste').disabled = false;
    setStatus(`Copied "${room.name}" — click Paste to place it on any floor`);
}

async function doPaste() {
    if (!_clipboardRoom || !state.floorId) return;
    const newName = prompt('Name for the new room:', _clipboardRoom.name + ' (copy)');
    if (!newName || !newName.trim()) return;
    const name = newName.trim();
    const abbr = autoAbbrev(name);

    // Create the room on the current floor
    const result = await apiPost('create_room', { floor_id: state.floorId, name, abbreviation: abbr });
    if (result.error) { alert(result.error); return; }

    // Copy map_points from clipboard to the new room
    if (_clipboardRoom.map_points) {
        result.map_points = JSON.parse(JSON.stringify(_clipboardRoom.map_points));
        await apiPost('save_room', { id: result.id, map_points: result.map_points });
    }
    // Copy capacity if set
    if (_clipboardRoom.capacity) {
        result.capacity = _clipboardRoom.capacity;
        await apiPost('save_room', { id: result.id, capacity: result.capacity });
    }

    state.rooms.push(result);
    state.selectedRoomIds = new Set([result.id]);
    state.activeRoomId = null;
    facPicker.load();
    render(); updateRoomPane();
    setStatus(`Pasted "${name}" onto current floor`);
}

// ─────────────────────────────────────────────────────────────────────────────
// UTILS
// ─────────────────────────────────────────────────────────────────────────────
function setStatus(msg) { statusEl.textContent=msg; }
function escHtml(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function autoAbbrev(name) {
    if (!name) return '';
    const w=name.trim().split(/\s+/);
    if (w.length===1) return name.substring(0,4).toUpperCase();
    return w.map(x=>x[0].toUpperCase()).join('').substring(0,5);
}

// ─────────────────────────────────────────────────────────────────────────────
// ROOM PANE
// ─────────────────────────────────────────────────────────────────────────────
function getFieldValue(rooms, field) {
    const vals=rooms.map(r=>r[field]!=null?String(r[field]):'');
    const unique=new Set(vals);
    if (unique.size===1) return {value:vals[0], multiple:false};
    return {value:'', multiple:true};
}

function updateRoomPane() {
    const body=document.getElementById('room-pane-body');
    const ids=[...state.selectedRoomIds];
    // Copy: enabled when exactly 1 room selected
    document.getElementById('btn-copy').disabled = ids.length !== 1;
    // Paste: enabled when clipboard has data and a floor is loaded
    document.getElementById('btn-paste').disabled = !_clipboardRoom || !state.floorId;
    if (ids.length===0) {
        body.innerHTML=`<div id="room-pane-empty">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:48px;height:48px;margin-bottom:12px;opacity:0.35;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p style="font-size:14px;font-weight:500;margin:0 0 4px;color:#9ca3af;font-family:ui-sans-serif,system-ui,sans-serif;">No room selected</p>
            <small style="font-size:12px;color:#9ca3af;font-family:ui-sans-serif,system-ui,sans-serif;">Click a room on the floor plan to view its details</small>
        </div>`;
        return;
    }
    const rooms=ids.map(id=>state.rooms.find(r=>r.id===id)).filter(Boolean);

    const fName=getFieldValue(rooms,'name');
    const fAbbr=getFieldValue(rooms,'abbreviation');
    const fCap =getFieldValue(rooms,'capacity');
    const fRes =getFieldValue(rooms,'is_reservable');
    const fSto =getFieldValue(rooms,'is_storage');

    let title, subtitle;
    if (ids.length === 1) {
        title    = escHtml(rooms[0].name);
        subtitle = 'Room';
    } else {
        title    = `${ids.length} Rooms Selected`;
        subtitle = 'Selection';
    }

    body.innerHTML=`<div style="padding:16px 20px;font-family:ui-sans-serif,system-ui,sans-serif;">
        <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:#9ca3af;margin:0 0 2px;">${subtitle}</p>
        <h2 style="font-size:18px;font-weight:700;color:#111827;margin:0 0 20px;">${title}</h2>

        <div style="display:grid;grid-template-columns:max-content 1fr;align-items:center;gap:8px 12px;">

            <label class="rp-label" style="text-align:right;white-space:nowrap;">Name</label>
            <input class="rp-input" id="rp-name" type="text"
                value="${escHtml(fName.value)}"
                placeholder="${fName.multiple?'Multiple values':'Room name'}"
                data-multiple="${fName.multiple}"
                onfocus="this.style.borderColor='#3b82f6';this.style.boxShadow='0 0 0 3px rgba(59,130,246,0.12)'"
                onblur="this.style.borderColor='#d1d5db';this.style.boxShadow='none';saveParam('name',this.value.trim(),this.dataset.multiple)"
            >

            <label class="rp-label" style="text-align:right;white-space:nowrap;">Abbreviation</label>
            <input class="rp-input" id="rp-abbr" type="text" maxlength="10"
                value="${escHtml(fAbbr.value)}"
                placeholder="${fAbbr.multiple?'Multiple':'e.g. SANC'}"
                data-multiple="${fAbbr.multiple}"
                onfocus="this.style.borderColor='#3b82f6';this.style.boxShadow='0 0 0 3px rgba(59,130,246,0.12)'"
                onblur="this.style.borderColor='#d1d5db';this.style.boxShadow='none';saveParam('abbreviation',this.value.trim(),this.dataset.multiple)"
            >

            <label class="rp-label" style="text-align:right;white-space:nowrap;">Capacity</label>
            <input class="rp-input" id="rp-cap" type="number" min="0"
                value="${escHtml(fCap.value)}"
                placeholder="${fCap.multiple?'Multiple':'e.g. 50'}"
                data-multiple="${fCap.multiple}"
                onfocus="this.style.borderColor='#3b82f6';this.style.boxShadow='0 0 0 3px rgba(59,130,246,0.12)'"
                onblur="this.style.borderColor='#d1d5db';this.style.boxShadow='none';saveParam('capacity',this.value,this.dataset.multiple)"
            >

            <label class="rp-label" style="text-align:right;white-space:nowrap;">Reservable</label>
            <div style="display:flex;align-items:center;gap:8px;">
                <input type="checkbox" id="rp-reservable"
                    ${fRes.multiple ? '' : (parseInt(fRes.value) ? 'checked' : '')}
                    ${fRes.multiple ? 'data-indeterminate="true"' : ''}
                    onchange="saveParam('is_reservable', this.checked ? 1 : 0, '${fRes.multiple}')"
                    style="width:16px;height:16px;accent-color:#2563eb;cursor:pointer;"
                >
                <span style="font-size:12px;color:#6b7280;">${fRes.multiple ? 'Mixed' : (parseInt(fRes.value) ? 'Yes' : 'No')}</span>
            </div>

            <label class="rp-label" style="text-align:right;white-space:nowrap;">Storage</label>
            <div style="display:flex;align-items:center;gap:8px;">
                <input type="checkbox" id="rp-storage"
                    ${fSto.multiple ? '' : (parseInt(fSto.value) ? 'checked' : '')}
                    ${fSto.multiple ? 'data-indeterminate="true"' : ''}
                    onchange="saveParam('is_storage', this.checked ? 1 : 0, '${fSto.multiple}')"
                    style="width:16px;height:16px;accent-color:#2563eb;cursor:pointer;"
                >
                <span style="font-size:12px;color:#6b7280;">${fSto.multiple ? 'Mixed' : (parseInt(fSto.value) ? 'Yes' : 'No')}</span>
            </div>

        </div>
    </div>`;
}

async function saveParam(field, value, multipleAttr) {
    const wasMultiple = (multipleAttr === 'true');
    if (wasMultiple && value === '') return; // nothing changed
    if (field === 'name' && !value) return;

    const selectedIds = [...state.selectedRoomIds];

    for (const id of selectedIds) {
        // Update local state if the room is on this floor
        const room = state.rooms.find(r => r.id === id);
        if (room) {
            if (field === 'capacity') {
                room[field] = value === '' ? null : parseInt(value) || 0;
            } else if (field === 'is_reservable' || field === 'is_storage') {
                room[field] = value ? 1 : 0;
            } else {
                room[field] = value || null;
            }
        }
        // Save to DB (works for rooms on other floors too)
        const saveVal = field === 'capacity' ? (value === '' ? null : parseInt(value) || 0)
                      : (field === 'is_reservable' || field === 'is_storage') ? (value ? 1 : 0)
                      : (value || null);
        await apiPost('save_room', { id, [field]: saveVal });
    }

    facPicker.load();
    render(); // refresh labels (name change)
    updateRoomPane(); // refresh pane (clears "multiple" state if all now same)
}

// ─────────────────────────────────────────────────────────────────────────────
// Init
setViewBox(0, 0, 80, 60);

// ─────────────────────────────────────────────────────────────────────────────
// ROOM PICKER
// ─────────────────────────────────────────────────────────────────────────────

// Initialise the room-picker module
const facPicker = new FloorPlanPicker({
    paneId      : 'fac-fp-pane',
    dividerId   : 'fac-fp-divider',
    dividerKey  : 'fp_w_fac',
    defaultWidth: 340,
    onChange    : () => updateLinkButtons(),
    onRoomClick : (room, floor) => selectFloor(floor),
});

// ─────────────────────────────────────────────────────────────────────────────
// ROOM LINKS
// ─────────────────────────────────────────────────────────────────────────────

let roomLinks = []; // [{id, name, room_ids:[], members:[], total_capacity}]

async function loadRoomLinks() {
    try {
        const res = await fetch(BASE_PATH + '/api/links_api.php?action=get_links');
        roomLinks = await res.json();
    } catch(e) { roomLinks = []; }
    facPicker.setLinkGroups(roomLinks);
    updateLinkButtons();
}
loadRoomLinks();

/** Enable/disable Link and Unlink buttons based on picker selection. */
function updateLinkButtons() {
    const sel = facPicker.getSelection();
    const ids = Object.keys(sel).map(Number);
    const btnLink   = document.getElementById('btn-link');
    const btnUnlink = document.getElementById('btn-unlink');

    // Link: need 2+ rooms selected
    btnLink.disabled = ids.length < 2;

    // Unlink: at least one selected room belongs to a link group
    const anyLinked = ids.some(id => roomLinks.some(g => g.room_ids.includes(id)));
    btnUnlink.disabled = !anyLinked || ids.length === 0;
}

// ── Link flow ────────────────────────────────────────────

function doLink() {
    const sel   = facPicker.getSelection();
    const ids   = Object.keys(sel).map(Number);
    const names = ids.map(id => sel[id]?.name || `Room ${id}`).join(', ');

    document.getElementById('link-modal-desc').textContent = `Rooms: ${names}`;
    document.getElementById('link-name-input').value = '';
    document.getElementById('link-modal-overlay').style.display = 'flex';
    setTimeout(() => document.getElementById('link-name-input').focus(), 50);
}

function closeLinkModal() {
    document.getElementById('link-modal-overlay').style.display = 'none';
}

async function confirmLink() {
    const name = document.getElementById('link-name-input').value.trim();
    if (!name) { document.getElementById('link-name-input').focus(); return; }

    const sel = facPicker.getSelection();
    const ids = Object.keys(sel).map(Number);

    const body = new FormData();
    body.append('action',   'create_link');
    body.append('name',     name);
    body.append('room_ids', JSON.stringify(ids));

    const res  = await fetch(BASE_PATH + '/api/links_api.php', { method: 'POST', body });
    const data = await res.json();
    if (data.error) { alert('Error: ' + data.error); return; }

    closeLinkModal();
    await loadRoomLinks();
    setStatus(`Link "${name}" created`);
}

// ── Unlink flow ──────────────────────────────────────────

async function doUnlink() {
    const sel = facPicker.getSelection();
    const ids = Object.keys(sel).map(Number);

    // Find all link groups that contain any of the selected rooms
    const affectedLinks = roomLinks.filter(g => ids.some(id => g.room_ids.includes(id)));
    if (!affectedLinks.length) return;

    const names = affectedLinks.map(g => g.name).join(', ');
    const confirmed = confirm(`Remove link group${affectedLinks.length > 1 ? 's' : ''}: ${names}?`);
    if (!confirmed) return;

    for (const link of affectedLinks) {
        const body = new FormData();
        body.append('action',  'delete_link');
        body.append('link_id', link.id);
        await fetch(BASE_PATH + '/api/links_api.php', { method: 'POST', body });
    }

    facPicker.clearSelection();
    await loadRoomLinks();
    setStatus(`Unlinked ${affectedLinks.length} group(s)`);
}

// Close link modal on backdrop click
document.getElementById('link-modal-overlay').addEventListener('click', e => {
    if (e.target === document.getElementById('link-modal-overlay')) closeLinkModal();
});

</script>

</body>
</html>
