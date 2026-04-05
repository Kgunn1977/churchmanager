<?php
$type = $_GET['type'] ?? 'supplies';
if (!in_array($type, ['supplies', 'tools', 'materials'])) {
    $type = 'supplies';
}

$typeNames = [
    'supplies' => 'Supplies',
    'tools' => 'Tools',
    'materials' => 'Materials'
];
$typeDescriptions = [
    'supplies' => 'Consumable items used in cleaning and maintenance tasks.',
    'tools' => 'Reusable tools used in cleaning and maintenance tasks.',
    'materials' => 'Raw materials and components for facility repairs and projects.'
];

$pageTitle = $typeNames[$type] . ' — Church Facility Manager';
require_once __DIR__ . '/../includes/nav.php';
require_once __DIR__ . '/../config/database.php';
requireRole(['admin', 'scheduler']);
$db = getDB();
?>

<style>
.cat-page { max-width: 640px; margin: 0 auto; padding: 32px 16px; font-family: ui-sans-serif, system-ui, sans-serif; }
.cat-tabs { display: flex; gap: 8px; margin-bottom: 24px; border-bottom: 2px solid #e5e7eb; }
.cat-tab { padding: 8px 16px; font-size: 14px; font-weight: 600; color: #6b7280; border: none; background: none; cursor: pointer; border-bottom: 3px solid transparent; margin-bottom: -2px; }
.cat-tab.active { color: #2563eb; border-bottom-color: #2563eb; }
.cat-hdr { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
.cat-hdr h1 { font-size: 22px; font-weight: 700; color: #111827; margin: 0; }
.cat-hdr p { font-size: 13px; color: #6b7280; margin: 4px 0 0; }
.cat-add-btn { background: #2563eb; color: white; border: none; border-radius: 10px; padding: 8px 16px; font-size: 12px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 4px; }
.cat-add-btn:hover { background: #1d4ed8; }
.cat-list { background: white; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); overflow: hidden; }
.cat-row { display: grid; grid-template-columns: 1fr 80px 64px; align-items: center; padding: 12px 20px; border-bottom: 1px solid #f3f4f6; font-size: 14px; }
.cat-row:last-child { border-bottom: none; }
.cat-row:hover { background: #f9fafb; }
.cat-row-name { font-weight: 600; color: #1e293b; }
.cat-row-qty { text-align: center; font-weight: 600; color: #374151; }
.cat-row-actions { display: flex; gap: 4px; justify-content: flex-end; }
.cat-row-actions button { background: none; border: none; cursor: pointer; font-size: 12px; padding: 4px 8px; border-radius: 6px; font-weight: 600; }
.cat-row-actions .edit-btn { color: #2563eb; } .cat-row-actions .edit-btn:hover { background: #eff6ff; }
.cat-hdr-row { display: grid; grid-template-columns: 1fr 80px 64px; padding: 8px 20px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #9ca3af; border-bottom: 1px solid #e5e7eb; background: #fafafa; }
.cat-empty { padding: 40px 20px; text-align: center; color: #9ca3af; }
.cat-count { font-size: 12px; color: #9ca3af; text-align: center; margin-top: 12px; }
/* Modal */
.cat-modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 3000; align-items: center; justify-content: center; }
.cat-modal-inner { background: white; border-radius: 16px; padding: 24px; width: 340px; box-shadow: 0 20px 60px rgba(0,0,0,0.2); }
.cat-modal h3 { margin: 0 0 16px; font-size: 16px; font-weight: 700; color: #111827; }
.cat-modal label { display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; margin-bottom: 3px; }
.cat-modal input { width: 100%; border: 1px solid #d1d5db; border-radius: 8px; padding: 7px 10px; font-size: 13px; outline: none; box-sizing: border-box; }
.cat-modal .btn-row { display: flex; gap: 8px; margin-top: 16px; }
.cat-modal .btn-row button { flex: 1; padding: 9px; border: none; border-radius: 8px; font-size: 13px; font-weight: 700; cursor: pointer; }
.cat-modal .btn-save { background: #2563eb; color: white; }
.cat-modal .btn-cancel { background: #f3f4f6; color: #374151; }
.cat-modal .btn-delete { width: 100%; margin-top: 8px; padding: 8px; background: none; border: 1px solid #fca5a5; color: #dc2626; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; }
</style>

<div class="cat-page">
    <div class="cat-tabs">
        <a href="?type=supplies" class="cat-tab <?php echo $type === 'supplies' ? 'active' : ''; ?>">Supplies</a>
        <a href="?type=tools" class="cat-tab <?php echo $type === 'tools' ? 'active' : ''; ?>">Tools</a>
        <a href="?type=materials" class="cat-tab <?php echo $type === 'materials' ? 'active' : ''; ?>">Materials</a>
    </div>

    <div class="cat-hdr">
        <div>
            <h1><?php echo htmlspecialchars($typeNames[$type]); ?></h1>
            <p><?php echo htmlspecialchars($typeDescriptions[$type]); ?></p>
        </div>
        <button class="cat-add-btn" onclick="openModal()">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span id="add-btn-text">Add</span>
        </button>
    </div>
    <div class="cat-list" id="cat-list"></div>
    <div class="cat-count" id="cat-count"></div>
</div>

<div class="cat-modal" id="catModal">
    <div class="cat-modal-inner">
        <h3 id="modal-title">Add</h3>
        <input type="hidden" id="modal-id">
        <div style="margin-bottom:12px;">
            <label>Name *</label>
            <input id="modal-name" type="text" placeholder="">
        </div>
        <div style="margin-bottom:12px;">
            <label>Nickname</label>
            <input id="modal-nickname" type="text" placeholder="Short display name (optional)" maxlength="100">
        </div>
        <div style="margin-bottom:4px;">
            <label>Quantity</label>
            <input id="modal-qty" type="number" min="1" value="1">
        </div>
        <div class="btn-row">
            <button class="btn-save" onclick="saveItem()">Save</button>
            <button class="btn-cancel" onclick="closeModal()">Cancel</button>
        </div>
        <button class="btn-delete" id="modal-del" onclick="deleteItem()" style="display:none;">Delete</button>
    </div>
</div>

<script>
let items = [];
let currentType = '<?php echo $type; ?>';
const typeNames = {
    'supplies': 'Supply',
    'tools': 'Tool',
    'materials': 'Material'
};
const typePlurals = {
    'supplies': 'supply item',
    'tools': 'tool',
    'materials': 'material'
};
const typePluralForms = {
    'supplies': 'supply items',
    'tools': 'tools',
    'materials': 'materials'
};

async function apiGet(action) {
    const r = await fetch(BASE_PATH + '/api/catalog_api.php?action=' + action + '&type=' + currentType);
    return r.json();
}
async function apiPost(action, data) {
    const r = await fetch(BASE_PATH + '/api/catalog_api.php', {
        method: 'POST', headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ action, type: currentType, ...data })
    });
    return r.json();
}

async function load() {
    items = await apiGet('get_all');
    render();
}

function render() {
    const el = document.getElementById('cat-list');
    if (!items.length) {
        const plural = typePluralForms[currentType];
        el.innerHTML = '<div class="cat-empty"><p style="font-size:14px;font-weight:500;">No ' + plural + ' yet</p><p style="font-size:12px;">Click "+ Add ' + typeNames[currentType] + '" to get started</p></div>';
        document.getElementById('cat-count').textContent = '';
        return;
    }
    let html = '<div class="cat-hdr-row"><span>Name</span><span style="text-align:center;">Qty</span><span></span></div>';
    html += items.map(i => `
        <div class="cat-row">
            <span class="cat-row-name">${esc(i.name)}${i.nickname ? '<span style="font-weight:400;color:#6b7280;font-size:12px;margin-left:6px;">(' + esc(i.nickname) + ')</span>' : ''}</span>
            <span class="cat-row-qty">${i.quantity}</span>
            <div class="cat-row-actions">
                <button class="edit-btn" onclick="openModal(${i.id})">Edit</button>
            </div>
        </div>
    `).join('');
    el.innerHTML = html;
    const plural = items.length !== 1 ? typePluralForms[currentType] : typePlurals[currentType];
    document.getElementById('cat-count').textContent = items.length + ' ' + plural;
}

function openModal(id) {
    const item = id ? items.find(i => i.id === id) : null;
    const typeName = typeNames[currentType];
    document.getElementById('modal-title').textContent = item ? 'Edit ' + typeName : 'Add ' + typeName;
    document.getElementById('modal-id').value = item ? item.id : '';
    document.getElementById('modal-name').value = item ? item.name : '';
    document.getElementById('modal-nickname').value = item ? (item.nickname || '') : '';
    document.getElementById('modal-qty').value = item ? item.quantity : 1;
    document.getElementById('modal-del').textContent = 'Delete ' + typeName;
    document.getElementById('modal-del').style.display = item ? 'block' : 'none';
    document.getElementById('modal-name').placeholder = 'e.g. ' + (currentType === 'supplies' ? 'Paper Towels' : currentType === 'tools' ? 'Broom' : 'Wood Screws');
    document.getElementById('catModal').style.display = 'flex';
    setTimeout(() => document.getElementById('modal-name').focus(), 50);
}

function closeModal() { document.getElementById('catModal').style.display = 'none'; }

async function saveItem() {
    const id       = document.getElementById('modal-id').value;
    const name     = document.getElementById('modal-name').value.trim();
    const nickname = document.getElementById('modal-nickname').value.trim();
    const qty      = parseInt(document.getElementById('modal-qty').value) || 1;
    if (!name) { document.getElementById('modal-name').focus(); return; }
    if (id) {
        await apiPost('update', { id: parseInt(id), name, nickname, quantity: qty });
    } else {
        await apiPost('add', { name, nickname, quantity: qty });
    }
    closeModal();
    load();
}

async function deleteItem() {
    const id = document.getElementById('modal-id').value;
    const typeName = typeNames[currentType];
    if (!id || !confirm('Delete this ' + typeName.toLowerCase() + '?')) return;
    await apiPost('delete', { id: parseInt(id) });
    closeModal();
    load();
}

function esc(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

document.getElementById('catModal').addEventListener('click', e => { if (e.target === document.getElementById('catModal')) closeModal(); });
document.getElementById('modal-name').addEventListener('keydown', e => { if (e.key === 'Enter') saveItem(); });

load();
</script>
</body>
</html>
