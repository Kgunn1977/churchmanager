<?php
$pageTitle = 'Equipment — Church Facility Manager';
require_once __DIR__ . '/../includes/nav.php';
require_once __DIR__ . '/../config/database.php';
requireRole(['admin', 'scheduler']);

$db        = getDB();
$buildings = $db->query("SELECT id, name FROM buildings ORDER BY name")->fetchAll();

// Floor plan picker config
$fp_id        = 'eq';
$fp_div_key   = 'fp_w_eq';
$fp_default_w = 300;
$fp_buildings = $buildings;

$categories = [
    'furniture' => 'Furniture',
    'av_tech'   => 'AV & Tech',
    'kitchen'   => 'Kitchen',
    'other'     => 'Other',
];
$catColors = [
    'furniture' => '#dbeafe',
    'av_tech'   => '#ede9fe',
    'kitchen'   => '#fef9c3',
    'other'     => '#f3f4f6',
];
$catTextColors = [
    'furniture' => '#1d4ed8',
    'av_tech'   => '#6d28d9',
    'kitchen'   => '#a16207',
    'other'     => '#4b5563',
];
?>

<style>
#eq-app {
    display: flex;
    height: calc(100vh - 56px);
    overflow: hidden;
    font-family: ui-sans-serif, system-ui, sans-serif;
}

/* ── Center: Catalog ─────────────────────────────── */
#eq-catalog {
    flex: 1;
    min-width: 280px;
    display: flex;
    flex-direction: column;
    border-right: 1px solid #e5e7eb;
    background: #f9fafb;
}
#eq-catalog-header {
    padding: 16px 20px 12px;
    border-bottom: 1px solid #e5e7eb;
    background: white;
}
#eq-catalog-list {
    flex: 1;
    overflow-y: auto;
    padding: 12px;
}
.cat-item {
    display: grid;
    grid-template-columns: 1fr 44px 44px 44px 44px 28px;
    align-items: center;
    gap: 4px;
    padding: 8px 12px;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    margin-bottom: 5px;
    cursor: grab;
    transition: border-color .15s, box-shadow .15s;
    font-size: 13px;
    user-select: none;
}
.cat-item:hover { border-color: #93c5fd; box-shadow: 0 1px 4px rgba(59,130,246,0.08); }
.cat-item.selected { border-color: #3b82f6; background: #eff6ff; }
.cat-item.dragging { opacity: 0.5; border-color: #3b82f6; }
.cat-item:active { cursor: grabbing; }
.cat-edit-btn {
    background: none; border: none; color: #9ca3af; cursor: pointer; font-size: 13px;
    padding: 2px; border-radius: 4px; line-height: 1; transition: color .15s;
}
.cat-edit-btn:hover { color: #2563eb; background: #eff6ff; }
.cat-badge {
    display: inline-block;
    font-size: 10px;
    font-weight: 700;
    padding: 1px 6px;
    border-radius: 4px;
    white-space: nowrap;
}
.cat-item-name { font-weight: 600; color: #111827; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.cat-item-qty { font-size: 12px; color: #374151; text-align: center; font-weight: 600; }
.cat-col-hdr {
    display: grid;
    grid-template-columns: 1fr 44px 44px 44px 44px 28px;
    gap: 4px;
    padding: 2px 12px 6px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: #9ca3af;
}

/* ── Right: Room Inventory ───────────────────────── */
#eq-room-panel {
    width: 360px;
    min-width: 280px;
    display: flex;
    flex-direction: column;
    background: white;
}
#eq-room-header {
    padding: 16px 20px 12px;
    border-bottom: 1px solid #e5e7eb;
}
#eq-room-list {
    flex: 1;
    overflow-y: auto;
    padding: 12px;
}
.eq-row {
    display: grid;
    grid-template-columns: 1fr 54px 54px;
    align-items: center;
    gap: 6px;
    padding: 8px 10px;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    margin-bottom: 5px;
    font-size: 13px;
    cursor: grab;
    user-select: none;
}
.eq-row:active { cursor: grabbing; }
.eq-row-name { font-weight: 600; color: #1e293b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.eq-row input[type="number"] {
    width: 100%; padding: 3px 5px; border: 1px solid #d1d5db; border-radius: 6px;
    font-size: 12px; text-align: center; background: white;
}

/* ── Drag & Drop ────────────────────────────────── */
#eq-room-panel.drag-over {
    background: #eff6ff;
    outline: 2px dashed #3b82f6;
    outline-offset: -2px;
}
#eq-room-panel.drag-over #eq-room-header {
    background: #eff6ff;
}
.eq-row.dragging-out {
    opacity: 0.4;
    border-color: #ef4444;
    background: #fef2f2;
}
.drag-ghost {
    position: fixed;
    pointer-events: none;
    z-index: 9999;
    background: white;
    border: 2px solid #3b82f6;
    border-radius: 8px;
    padding: 6px 12px;
    font-size: 12px;
    font-weight: 600;
    color: #1e293b;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    white-space: nowrap;
}

/* ── Empty states ────────────────────────────────── */
.eq-empty {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    padding: 40px 20px; color: #9ca3af; text-align: center;
}
.eq-empty p { margin: 4px 0; }

/* ── Add-to-room button ──────────────────────────── */
.eq-add-btn {
    display: flex; align-items: center; gap: 6px;
    padding: 8px 14px; background: #eff6ff; border: 1px dashed #93c5fd;
    border-radius: 8px; color: #2563eb; font-size: 12px; font-weight: 600;
    cursor: pointer; transition: background .15s; margin-bottom: 5px;
}
.eq-add-btn:hover { background: #dbeafe; }

/* ── Movable toggle ──────────────────────────────── */
.movable-toggle {
    width: 16px; height: 16px; accent-color: #2563eb; cursor: pointer;
}
</style>

<div id="eq-app">

    <!-- ── Room Picker (left pane) ─────────────────────────── -->
    <?php require_once __DIR__ . '/../includes/floor_plan_picker.php'; ?>

    <!-- ── Catalog (center) ────────────────────────────────── -->
    <div id="eq-catalog">
        <div id="eq-catalog-header">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                <h1 style="font-size:18px;font-weight:700;color:#111827;margin:0;">Equipment Catalog</h1>
                <button onclick="openAddCatalogModal()" style="background:#2563eb;color:white;border:none;border-radius:8px;padding:6px 14px;font-size:12px;font-weight:700;cursor:pointer;">+ Add Item</button>
            </div>
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                <button class="cat-filter-btn active" data-cat="all" onclick="setCatFilter('all',this)" style="padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;border:1px solid #d1d5db;background:#2563eb;color:white;cursor:pointer;">All</button>
                <?php foreach ($categories as $key => $label): ?>
                <button class="cat-filter-btn" data-cat="<?= $key ?>" onclick="setCatFilter('<?= $key ?>',this)" style="padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;border:1px solid #d1d5db;background:white;color:#374151;cursor:pointer;"><?= $label ?></button>
                <?php endforeach; ?>
            </div>
        </div>
        <div id="eq-catalog-list"></div>
    </div>

    <!-- ── Room Inventory (right) ──────────────────────────── -->
    <div id="eq-room-panel">
        <div id="eq-room-header">
            <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:#9ca3af;margin:0 0 2px;" id="eq-room-subtitle">Room Equipment</p>
            <h2 style="font-size:16px;font-weight:700;color:#111827;margin:0;" id="eq-room-title">Select a room</h2>
        </div>
        <div id="eq-room-list">
            <div class="eq-empty">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:40px;height:40px;margin-bottom:8px;opacity:0.35;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                <p style="font-size:13px;font-weight:500;">No room selected</p>
                <p style="font-size:12px;">Click a room in the picker to view its equipment</p>
            </div>
        </div>
    </div>
</div>

<!-- ── Add Catalog Item Modal ─────────────────────────────── -->
<div id="addCatModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:3000;align-items:center;justify-content:center;">
    <div style="background:white;border-radius:16px;padding:24px;width:380px;box-shadow:0 20px 60px rgba(0,0,0,0.2);font-family:ui-sans-serif,system-ui,sans-serif;">
        <h3 style="margin:0 0 16px;font-size:16px;font-weight:700;color:#111827;" id="catModalTitle">Add Equipment</h3>
        <input type="hidden" id="catModal-id">
        <div style="margin-bottom:12px;">
            <label style="display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:3px;">Name *</label>
            <input id="catModal-name" type="text" placeholder="e.g. Folding Chair" style="width:100%;border:1px solid #d1d5db;border-radius:8px;padding:7px 10px;font-size:13px;outline:none;box-sizing:border-box;">
        </div>
        <div style="margin-bottom:12px;">
            <label style="display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:3px;">Category</label>
            <select id="catModal-category" style="width:100%;border:1px solid #d1d5db;border-radius:8px;padding:7px 10px;font-size:13px;outline:none;box-sizing:border-box;background:white;">
                <?php foreach ($categories as $key => $label): ?>
                <option value="<?= $key ?>"><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="margin-bottom:12px;">
            <label style="display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:3px;">Description</label>
            <input id="catModal-desc" type="text" placeholder="Optional" style="width:100%;border:1px solid #d1d5db;border-radius:8px;padding:7px 10px;font-size:13px;outline:none;box-sizing:border-box;">
        </div>
        <div style="margin-bottom:16px;">
            <label style="display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:3px;">Total Quantity Owned</label>
            <input id="catModal-totalQty" type="number" min="0" value="0" style="width:100%;border:1px solid #d1d5db;border-radius:8px;padding:7px 10px;font-size:13px;outline:none;box-sizing:border-box;">
        </div>
        <div style="display:flex;gap:8px;">
            <button onclick="saveCatalogItem()" style="flex:1;padding:9px;background:#2563eb;color:white;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;">Save</button>
            <button onclick="closeCatModal()" style="flex:1;padding:9px;background:#f3f4f6;color:#374151;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;">Cancel</button>
        </div>
        <button id="catModal-deleteBtn" onclick="deleteCatalogItem()" style="display:none;width:100%;margin-top:8px;padding:8px;background:none;border:1px solid #fca5a5;color:#dc2626;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;">Delete Item</button>
    </div>
</div>

<!-- ── Assign-to-Room Modal ───────────────────────────────── -->
<div id="assignModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:3000;align-items:center;justify-content:center;">
    <div style="background:white;border-radius:16px;padding:24px;width:360px;box-shadow:0 20px 60px rgba(0,0,0,0.2);font-family:ui-sans-serif,system-ui,sans-serif;">
        <h3 style="margin:0 0 6px;font-size:16px;font-weight:700;color:#111827;">Assign to Room</h3>
        <p id="assign-desc" style="font-size:12px;color:#6b7280;margin:0 0 16px;"></p>
        <input type="hidden" id="assign-eqId">
        <div style="margin-bottom:12px;">
            <label style="display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:3px;">Quantity</label>
            <input id="assign-qty" type="number" min="1" value="1" style="width:100%;border:1px solid #d1d5db;border-radius:8px;padding:7px 10px;font-size:13px;outline:none;box-sizing:border-box;">
        </div>
        <div style="margin-bottom:16px;display:flex;align-items:center;gap:8px;">
            <input type="checkbox" id="assign-movable" checked style="width:16px;height:16px;accent-color:#2563eb;">
            <label for="assign-movable" style="font-size:13px;color:#374151;">Movable (can be pulled for events)</label>
        </div>
        <div style="display:flex;gap:8px;">
            <button onclick="confirmAssign()" style="flex:1;padding:9px;background:#2563eb;color:white;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;">Assign</button>
            <button onclick="closeAssignModal()" style="flex:1;padding:9px;background:#f3f4f6;color:#374151;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;">Cancel</button>
        </div>
    </div>
</div>

<script>
const CAT_COLORS = <?= json_encode($catColors) ?>;
const CAT_TEXT   = <?= json_encode($catTextColors) ?>;
const CAT_LABELS = <?= json_encode($categories) ?>;

let catalog       = [];   // full equipment catalog
let selectedRoomId = null;
let selectedRoomName = '';
let roomEquipment = [];   // current room's equipment
let catFilter     = 'all';
let selectedCatId = null; // currently highlighted catalog item
let multiRoomMode = false; // true when catalog click selected multiple rooms (no drag allowed)
let catLocations  = [];   // rooms where the selected catalog item lives
let _programmaticSelect = false; // flag to distinguish selectRooms from user clicks

// ═══════════════════════════════════════════════════════════
// API helpers
// ═══════════════════════════════════════════════════════════
async function apiGet(action, params = '') {
    const res = await fetch(BASE_PATH + '/api/equipment_api.php?action=' + action + (params ? '&' + params : ''));
    return res.json();
}
async function apiPost(action, data) {
    const res = await fetch(BASE_PATH + '/api/equipment_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action, ...data })
    });
    return res.json();
}

// ═══════════════════════════════════════════════════════════
// CATALOG
// ═══════════════════════════════════════════════════════════
async function loadCatalog() {
    catalog = await apiGet('get_catalog');
    renderCatalog();
}

function renderCatalog() {
    const list = document.getElementById('eq-catalog-list');
    const filtered = catFilter === 'all' ? catalog : catalog.filter(c => c.category === catFilter);

    if (!filtered.length) {
        list.innerHTML = '<div class="eq-empty"><p style="font-size:13px;font-weight:500;">No equipment items</p><p style="font-size:12px;">Click "+ Add Item" to get started</p></div>';
        return;
    }

    let html = '<div class="cat-col-hdr"><span>Item</span><span style="text-align:center;">Total</span><span style="text-align:center;">Stored</span><span style="text-align:center;">In Use</span><span style="text-align:center;">Avail</span><span></span></div>';

    html += filtered.map(item => {
        const bg    = CAT_COLORS[item.category] || '#f3f4f6';
        const color = CAT_TEXT[item.category] || '#4b5563';
        const avail = item.total_quantity - item.stored_qty - item.assigned_qty;
        return `
            <div class="cat-item${selectedCatId === item.id ? ' selected' : ''}" data-id="${item.id}" draggable="true"
                 ondragstart="onCatDragStart(event, ${item.id})"
                 ondragend="onCatDragEnd(event)"
                 onclick="onCatItemClick(${item.id})">
                <div>
                    <span class="cat-badge" style="background:${bg};color:${color};margin-right:6px;">${CAT_LABELS[item.category] || item.category}</span>
                    <span class="cat-item-name">${esc(item.name)}</span>
                </div>
                <span class="cat-item-qty">${item.total_quantity}</span>
                <span class="cat-item-qty" style="color:#0369a1;">${item.stored_qty}</span>
                <span class="cat-item-qty" style="color:#16a34a;">${item.assigned_qty}</span>
                <span class="cat-item-qty" style="color:${avail > 0 ? '#9333ea' : avail < 0 ? '#dc2626' : '#9ca3af'};font-weight:700;">${avail}</span>
                <button class="cat-edit-btn" onclick="event.stopPropagation();openEditCatalogModal(catalog.find(c=>c.id===${item.id}))" title="Edit item">✎</button>
            </div>
        `;
    }).join('');

    list.innerHTML = html;
}

function setCatFilter(cat, btn) {
    catFilter = cat;
    document.querySelectorAll('.cat-filter-btn').forEach(b => {
        b.style.background = 'white'; b.style.color = '#374151';
    });
    btn.style.background = '#2563eb'; btn.style.color = 'white';
    renderCatalog();
}

async function onCatItemClick(eqId) {
    // Toggle: clicking the same item again deselects
    if (selectedCatId === eqId) {
        selectedCatId = null;
        multiRoomMode = false;
        catLocations  = [];
        eqPicker.clearSelection();
        renderCatalog();
        renderRoomEquipment(); // restore normal room view
        return;
    }
    selectedCatId = eqId;
    renderCatalog();
    // Fetch rooms where this item is assigned
    const locations = await apiGet('get_equipment_locations', 'equipment_id=' + eqId);
    catLocations = locations;
    const roomIds = locations.map(l => l.room_id);
    _programmaticSelect = true;
    eqPicker.clearSelection();
    if (roomIds.length) {
        multiRoomMode = true;
        eqPicker.selectRooms(roomIds);
    } else {
        multiRoomMode = false;
    }
    _programmaticSelect = false;
    renderCatLocations();
}

function renderCatLocations() {
    const list  = document.getElementById('eq-room-list');
    const title = document.getElementById('eq-room-title');
    const sub   = document.getElementById('eq-room-subtitle');
    const item  = catalog.find(c => c.id === selectedCatId);
    if (!item) return;

    title.textContent = item.name;
    const totalPlaced = catLocations.reduce((s, l) => s + l.quantity, 0);
    const avail = item.total_quantity - totalPlaced;
    sub.textContent = `Located in ${catLocations.length} room${catLocations.length !== 1 ? 's' : ''} · ${avail} available`;

    if (!catLocations.length) {
        list.innerHTML = '<div class="eq-empty" style="padding:24px;"><p style="font-size:13px;">Not assigned to any rooms</p><p style="font-size:12px;">Drag this item to the room panel after selecting a room</p></div>';
        return;
    }

    let html = '<div style="display:grid;grid-template-columns:1fr 54px 54px;gap:6px;padding:2px 10px 6px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#9ca3af;">'
             + '<span>Room</span><span style="text-align:center;">Qty</span><span style="text-align:center;">Move</span></div>';

    html += catLocations.map(loc => `
        <div style="display:grid;grid-template-columns:1fr 54px 54px;align-items:center;gap:6px;padding:8px 10px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:5px;font-size:13px;">
            <div style="overflow:hidden;">
                <span style="font-weight:600;color:#1e293b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block;" title="${esc(loc.room_name)}">${esc(loc.room_name)}</span>
                <span style="font-size:10px;color:#9ca3af;">${esc(loc.building_name)} · ${esc(loc.floor_name)}</span>
            </div>
            <input type="number" min="0" value="${loc.quantity}"
                style="width:100%;padding:3px 5px;border:1px solid #d1d5db;border-radius:6px;font-size:12px;text-align:center;background:white;"
                onchange="updateLocAssignment(${loc.id}, parseInt(this.value)||0, ${loc.is_movable})"
                title="Quantity">
            <div style="text-align:center;">
                <input type="checkbox" class="movable-toggle" ${loc.is_movable ? 'checked' : ''}
                    onchange="updateLocAssignment(${loc.id}, ${loc.quantity}, this.checked?1:0)"
                    title="Movable — can be pulled for events">
            </div>
        </div>
    `).join('');

    list.innerHTML = html;
}

async function updateLocAssignment(reId, qty, movable) {
    await apiPost('update_assignment', { id: reId, quantity: qty, is_movable: movable });
    // Refresh locations and catalog counts
    catLocations = await apiGet('get_equipment_locations', 'equipment_id=' + selectedCatId);
    await loadCatalog();
    renderCatLocations();
}

function openAssignModal(eqId) {
    if (!selectedRoomId) return;
    const item = catalog.find(c => c.id === eqId);
    if (!item) return;
    document.getElementById('assign-eqId').value = eqId;
    document.getElementById('assign-qty').value = 1;
    document.getElementById('assign-movable').checked = true;
    document.getElementById('assign-desc').textContent = `Add "${item.name}" to ${selectedRoomName}`;
    document.getElementById('assignModal').style.display = 'flex';
    setTimeout(() => document.getElementById('assign-qty').focus(), 50);
}

// ═══════════════════════════════════════════════════════════
// ROOM EQUIPMENT
// ═══════════════════════════════════════════════════════════
async function loadRoomEquipment(roomId) {
    roomEquipment = await apiGet('get_room_equipment', 'room_id=' + roomId);
    renderRoomEquipment();
}

function renderRoomEquipment() {
    const list = document.getElementById('eq-room-list');
    const title = document.getElementById('eq-room-title');
    const sub   = document.getElementById('eq-room-subtitle');

    if (!selectedRoomId) {
        title.textContent = 'Select a room';
        sub.textContent   = 'Room Equipment';
        list.innerHTML = '<div class="eq-empty"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:40px;height:40px;margin-bottom:8px;opacity:0.35;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg><p style="font-size:13px;font-weight:500;">No room selected</p><p style="font-size:12px;">Click a room in the picker</p></div>';
        return;
    }

    title.textContent = selectedRoomName;
    sub.textContent   = 'Room Equipment · ' + roomEquipment.length + ' item' + (roomEquipment.length !== 1 ? 's' : '');

    // Build list of catalog items not already in this room
    const assignedEqIds = new Set(roomEquipment.map(re => re.equipment_id));
    const available = catalog.filter(c => !assignedEqIds.has(c.id));

    let html = `<div style="position:relative;margin-bottom:8px;">
        <button class="eq-add-btn" onclick="document.getElementById('eq-add-dropdown').style.display=document.getElementById('eq-add-dropdown').style.display==='block'?'none':'block'" style="width:100%;">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Equipment to Room
        </button>
        <div id="eq-add-dropdown" style="display:none;position:absolute;top:100%;left:0;right:0;background:white;border:1px solid #d1d5db;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,0.12);max-height:240px;overflow-y:auto;z-index:100;">
            ${available.length ? available.map(c => `
                <div style="padding:8px 12px;cursor:pointer;font-size:12px;border-bottom:1px solid #f3f4f6;display:flex;justify-content:space-between;align-items:center;"
                     onmouseenter="this.style.background='#eff6ff'" onmouseleave="this.style.background=''"
                     onclick="document.getElementById('eq-add-dropdown').style.display='none';openAssignModal(${c.id})">
                    <span style="font-weight:600;color:#1e293b;">${esc(c.name)}</span>
                    <span style="color:#9ca3af;font-size:11px;">${CAT_LABELS[c.category] || c.category}</span>
                </div>
            `).join('') : '<div style="padding:12px;text-align:center;color:#9ca3af;font-size:12px;">All catalog items already assigned</div>'}
        </div>
    </div>`;

    if (!roomEquipment.length) {
        html += '<div class="eq-empty" style="padding:24px;"><p style="font-size:13px;">No equipment in this room yet</p></div>';
    } else {
        // Header row
        html += '<div style="display:grid;grid-template-columns:1fr 54px 54px;gap:6px;padding:2px 10px 6px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#9ca3af;">'
              + '<span>Item</span><span style="text-align:center;">Qty</span><span style="text-align:center;">Move</span></div>';

        html += roomEquipment.map(re => `
            <div class="eq-row" data-id="${re.id}" data-re-id="${re.id}" draggable="true"
                 ondragstart="onRoomDragStart(event, ${re.id})"
                 ondragend="onRoomDragEnd(event)">
                <span class="eq-row-name" title="${esc(re.name)}">${esc(re.name)}</span>
                <input type="number" min="0" value="${re.quantity}"
                    onchange="updateAssignment(${re.id}, parseInt(this.value)||0, ${re.is_movable})"
                    onclick="event.stopPropagation()" title="Quantity">
                <div style="text-align:center;">
                    <input type="checkbox" class="movable-toggle" ${re.is_movable ? 'checked' : ''}
                        onchange="updateAssignment(${re.id}, ${re.quantity}, this.checked?1:0)"
                        onclick="event.stopPropagation()"
                        title="Movable — can be pulled for events">
                </div>
            </div>
        `).join('');
    }
    list.innerHTML = html;
}

async function confirmAssign() {
    const eqId    = parseInt(document.getElementById('assign-eqId').value);
    const qty     = parseInt(document.getElementById('assign-qty').value) || 1;
    const movable = document.getElementById('assign-movable').checked ? 1 : 0;
    await apiPost('assign_to_room', { room_id: selectedRoomId, equipment_id: eqId, quantity: qty, is_movable: movable });
    closeAssignModal();
    await Promise.all([loadRoomEquipment(selectedRoomId), loadCatalog()]);
}

async function updateAssignment(reId, qty, movable) {
    await apiPost('update_assignment', { id: reId, quantity: qty, is_movable: movable });
    await Promise.all([loadRoomEquipment(selectedRoomId), loadCatalog()]);
}

async function removeAssignment(reId) {
    await apiPost('remove_from_room', { id: reId });
    await Promise.all([loadRoomEquipment(selectedRoomId), loadCatalog()]);
}

function closeAssignModal() {
    document.getElementById('assignModal').style.display = 'none';
}

// ═══════════════════════════════════════════════════════════
// CATALOG MODAL (Add / Edit)
// ═══════════════════════════════════════════════════════════
function openAddCatalogModal() {
    document.getElementById('catModalTitle').textContent = 'Add Equipment';
    document.getElementById('catModal-id').value = '';
    document.getElementById('catModal-name').value = '';
    document.getElementById('catModal-category').value = 'furniture';
    document.getElementById('catModal-desc').value = '';
    document.getElementById('catModal-totalQty').value = 0;
    document.getElementById('catModal-deleteBtn').style.display = 'none';
    document.getElementById('addCatModal').style.display = 'flex';
    setTimeout(() => document.getElementById('catModal-name').focus(), 50);
}

function openEditCatalogModal(item) {
    document.getElementById('catModalTitle').textContent = 'Edit Equipment';
    document.getElementById('catModal-id').value = item.id;
    document.getElementById('catModal-name').value = item.name;
    document.getElementById('catModal-category').value = item.category;
    document.getElementById('catModal-desc').value = item.description || '';
    document.getElementById('catModal-totalQty').value = item.total_quantity || 0;
    document.getElementById('catModal-deleteBtn').style.display = 'block';
    document.getElementById('addCatModal').style.display = 'flex';
    setTimeout(() => document.getElementById('catModal-name').focus(), 50);
}

function closeCatModal() {
    document.getElementById('addCatModal').style.display = 'none';
}

async function saveCatalogItem() {
    const id    = document.getElementById('catModal-id').value;
    const name  = document.getElementById('catModal-name').value.trim();
    const cat   = document.getElementById('catModal-category').value;
    const desc  = document.getElementById('catModal-desc').value.trim();
    const total = parseInt(document.getElementById('catModal-totalQty').value) || 0;
    if (!name) { document.getElementById('catModal-name').focus(); return; }

    if (id) {
        await apiPost('update_catalog_item', { id: parseInt(id), name, category: cat, description: desc, total_quantity: total });
    } else {
        await apiPost('add_catalog_item', { name, category: cat, description: desc, total_quantity: total });
    }
    closeCatModal();
    await loadCatalog();
    if (selectedRoomId) await loadRoomEquipment(selectedRoomId);
}

async function deleteCatalogItem() {
    const id = document.getElementById('catModal-id').value;
    if (!id) return;
    if (!confirm('Delete this item? It will be removed from all rooms.')) return;
    await apiPost('delete_catalog_item', { id: parseInt(id) });
    closeCatModal();
    await loadCatalog();
    if (selectedRoomId) await loadRoomEquipment(selectedRoomId);
}

// ═══════════════════════════════════════════════════════════
// FLOOR PLAN PICKER
// ═══════════════════════════════════════════════════════════
const eqPicker = new FloorPlanPicker({
    paneId      : 'eq-fp-pane',
    dividerId   : 'eq-fp-divider',
    dividerKey  : 'fp_w_eq',
    defaultWidth: 300,
    singleSelect: true,
    linkedGroups: [],
    onChange     : function(rooms) {
        const ids = Object.keys(rooms).map(Number);
        const isUserClick = !_programmaticSelect;
        if (isUserClick && selectedCatId) {
            selectedCatId = null;
            multiRoomMode = false;
            catLocations  = [];
            renderCatalog();
        }
        if (ids.length === 1) {
            selectedRoomId   = ids[0];
            selectedRoomName = Object.values(rooms)[0]?.name || 'Room';
            loadRoomEquipment(selectedRoomId);
        } else if (ids.length === 0) {
            selectedRoomId   = null;
            selectedRoomName = '';
            roomEquipment    = [];
            multiRoomMode    = false;
            renderRoomEquipment();
        } else {
            // Multiple rooms selected (from catalog click) — don't load single room
            selectedRoomId   = null;
            selectedRoomName = '';
        }
    }
});

// Load linked groups
(async () => {
    try {
        const res = await fetch(BASE_PATH + '/api/room_links_api.php?action=get_links');
        const groups = await res.json();
        eqPicker.setLinkedGroups(groups);
    } catch(e) {}
})();

// ═══════════════════════════════════════════════════════════
// HELPERS
// ═══════════════════════════════════════════════════════════

// Close modals on backdrop click
document.getElementById('addCatModal').addEventListener('click', e => { if (e.target === document.getElementById('addCatModal')) closeCatModal(); });
document.getElementById('assignModal').addEventListener('click', e => { if (e.target === document.getElementById('assignModal')) closeAssignModal(); });

// Keyboard: Enter to save in modals
document.getElementById('catModal-name').addEventListener('keydown', e => { if (e.key === 'Enter') saveCatalogItem(); });
document.getElementById('assign-qty').addEventListener('keydown', e => { if (e.key === 'Enter') confirmAssign(); });

// ═══════════════════════════════════════════════════════════
// DRAG & DROP: Catalog → Room Panel
// ═══════════════════════════════════════════════════════════
let dragType = null;   // 'catalog' or 'room'
let dragData = null;

function onCatDragStart(e, eqId) {
    if (!selectedRoomId || multiRoomMode) { e.preventDefault(); return; }
    dragType = 'catalog';
    dragData = { equipmentId: eqId };
    e.target.classList.add('dragging');
    const item = catalog.find(c => c.id === eqId);
    e.dataTransfer.setData('text/plain', JSON.stringify({ type: 'catalog', equipmentId: eqId }));
    e.dataTransfer.effectAllowed = 'copy';
    // Custom ghost
    const ghost = document.createElement('div');
    ghost.className = 'drag-ghost';
    ghost.textContent = '+ ' + (item ? item.name : 'Equipment');
    document.body.appendChild(ghost);
    e.dataTransfer.setDragImage(ghost, 0, 0);
    setTimeout(() => ghost.remove(), 0);
}

function onCatDragEnd(e) {
    e.target.classList.remove('dragging');
    document.getElementById('eq-room-panel').classList.remove('drag-over');
    dragType = null;
    dragData = null;
}

// ═══════════════════════════════════════════════════════════
// DRAG & DROP: Room Item → outside to remove
// ═══════════════════════════════════════════════════════════
function onRoomDragStart(e, reId) {
    dragType = 'room';
    dragData = { reId: reId };
    e.target.classList.add('dragging-out');
    e.dataTransfer.setData('text/plain', JSON.stringify({ type: 'room', reId: reId }));
    e.dataTransfer.effectAllowed = 'move';
    const re = roomEquipment.find(r => r.id === reId);
    const ghost = document.createElement('div');
    ghost.className = 'drag-ghost';
    ghost.style.borderColor = '#ef4444';
    ghost.textContent = '✕ ' + (re ? re.name : 'Equipment');
    document.body.appendChild(ghost);
    e.dataTransfer.setDragImage(ghost, 0, 0);
    setTimeout(() => ghost.remove(), 0);
}

function onRoomDragEnd(e) {
    e.target.classList.remove('dragging-out');
    dragType = null;
    dragData = null;
}

// ── Room panel drop zone (for catalog items) ──
const roomPanel = document.getElementById('eq-room-panel');
roomPanel.addEventListener('dragover', e => {
    if (dragType !== 'catalog') return;
    e.preventDefault();
    e.dataTransfer.dropEffect = 'copy';
    roomPanel.classList.add('drag-over');
});
roomPanel.addEventListener('dragleave', e => {
    if (!roomPanel.contains(e.relatedTarget)) {
        roomPanel.classList.remove('drag-over');
    }
});
roomPanel.addEventListener('drop', async e => {
    e.preventDefault();
    roomPanel.classList.remove('drag-over');
    if (dragType !== 'catalog' || !selectedRoomId || !dragData) return;
    const eqId = dragData.equipmentId;
    // Check if already assigned
    const existing = roomEquipment.find(re => re.equipment_id === eqId);
    if (existing) return; // already in room
    await apiPost('assign_to_room', { room_id: selectedRoomId, equipment_id: eqId, quantity: 0, is_movable: 1 });
    await Promise.all([loadRoomEquipment(selectedRoomId), loadCatalog()]);
});

// ── Catalog panel drop zone (for removing room items) ──
const catalogPanel = document.getElementById('eq-catalog');
catalogPanel.addEventListener('dragover', e => {
    if (dragType !== 'room') return;
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    catalogPanel.style.background = '#fef2f2';
    catalogPanel.style.outline = '2px dashed #ef4444';
    catalogPanel.style.outlineOffset = '-2px';
});
catalogPanel.addEventListener('dragleave', e => {
    if (!catalogPanel.contains(e.relatedTarget)) {
        catalogPanel.style.background = '';
        catalogPanel.style.outline = '';
        catalogPanel.style.outlineOffset = '';
    }
});
catalogPanel.addEventListener('drop', async e => {
    e.preventDefault();
    catalogPanel.style.background = '';
    catalogPanel.style.outline = '';
    catalogPanel.style.outlineOffset = '';
    if (dragType !== 'room' || !dragData) return;
    await apiPost('remove_from_room', { id: dragData.reId });
    await Promise.all([loadRoomEquipment(selectedRoomId), loadCatalog()]);
});

// Also allow dropping room items on the floor plan picker to remove
const fpPane = document.getElementById('eq-fp-pane');
if (fpPane) {
    fpPane.addEventListener('dragover', e => {
        if (dragType !== 'room') return;
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        fpPane.style.background = '#fef2f2';
        fpPane.style.outline = '2px dashed #ef4444';
        fpPane.style.outlineOffset = '-2px';
    });
    fpPane.addEventListener('dragleave', e => {
        if (!fpPane.contains(e.relatedTarget)) {
            fpPane.style.background = '';
            fpPane.style.outline = '';
            fpPane.style.outlineOffset = '';
        }
    });
    fpPane.addEventListener('drop', async e => {
        e.preventDefault();
        fpPane.style.background = '';
        fpPane.style.outline = '';
        fpPane.style.outlineOffset = '';
        if (dragType !== 'room' || !dragData) return;
        await apiPost('remove_from_room', { id: dragData.reId });
        await Promise.all([loadRoomEquipment(selectedRoomId), loadCatalog()]);
    });
}

// ── Boot ─────────────────────────────────────────────────
loadCatalog();
</script>
</body>
</html>
