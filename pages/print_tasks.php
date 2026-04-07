<?php
$pageTitle = 'Print Task List — Church Facility Manager';
require_once __DIR__ . '/../includes/nav.php';
require_once __DIR__ . '/../config/database.php';
$db = getDB();
$user = getCurrentUser();
$canPickWorker = in_array($user['role'], ['admin', 'scheduler']);
$workers = [];
if ($canPickWorker) {
    $workers = $db->query("SELECT id, name, role FROM users WHERE is_active = 1 ORDER BY name")->fetchAll();
}
?>

<style>
/* ── Screen layout ─────────────────────────────────────── */
.pt-container { max-width: 900px; margin: 0 auto; padding: 16px; }

/* ── Controls bar ──────────────────────────────────────── */
.pt-controls {
    background: #fff; border-radius: 14px; padding: 16px;
    box-shadow: 0 1px 4px rgba(0,0,0,.06); margin-bottom: 16px;
    display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end;
}
.pt-control-group { display: flex; flex-direction: column; gap: 4px; }
.pt-control-group label {
    font-size: 11px; font-weight: 700; color: #9ca3af;
    text-transform: uppercase; letter-spacing: 0.05em;
}
.pt-control-group input,
.pt-control-group select {
    padding: 7px 12px; border: 1px solid #e5e7eb; border-radius: 8px;
    font-size: 14px; color: #374151; background: #fff;
}
.pt-control-group input:focus,
.pt-control-group select:focus { outline: none; border-color: #93c5fd; box-shadow: 0 0 0 2px rgba(59,130,246,.15); }

/* ── Toggle buttons ────────────────────────────────────── */
.pt-toggle { display: flex; gap: 4px; }
.pt-toggle button {
    padding: 7px 14px; border: 1.5px solid #e5e7eb; border-radius: 8px;
    background: #fff; font-size: 12px; font-weight: 600; color: #6b7280;
    cursor: pointer; transition: all .15s;
}
.pt-toggle button:hover { background: #f9fafb; }
.pt-toggle button.active { background: #eff6ff; border-color: #93c5fd; color: #1e40af; }

/* ── Print button ──────────────────────────────────────── */
.pt-print-btn {
    padding: 8px 20px; background: #2563eb; color: #fff; border: none;
    border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer;
    transition: background .15s; margin-left: auto;
}
.pt-print-btn:hover { background: #1d4ed8; }

/* ── Summary bar ───────────────────────────────────────── */
.pt-summary {
    display: flex; gap: 12px; margin-bottom: 16px;
}
.pt-summary-card {
    flex: 1; background: #fff; border-radius: 12px; padding: 10px 16px;
    text-align: center; border: 1px solid #f3f4f6;
}
.pt-summary-num { font-size: 22px; font-weight: 800; color: #1e293b; }
.pt-summary-label { font-size: 11px; font-weight: 600; color: #9ca3af; text-transform: uppercase; }

/* ── Task list (screen) ────────────────────────────────── */
.pt-list { display: flex; flex-direction: column; gap: 8px; }

.pt-card {
    background: #fff; border-radius: 12px; border: 1px solid #f3f4f6;
    overflow: hidden;
}
.pt-card-hdr {
    display: flex; align-items: center; gap: 10px; padding: 12px 16px;
    cursor: pointer; user-select: none;
}
.pt-card-hdr:hover { background: #f9fafb; }
.pt-card-title { font-size: 14px; font-weight: 700; color: #1e293b; }
.pt-card-sub { font-size: 12px; color: #6b7280; }
.pt-card-right { text-align: right; margin-left: auto; }
.pt-card-badge {
    font-size: 10px; font-weight: 600; padding: 2px 8px;
    border-radius: 6px; display: inline-block;
}
.pt-badge-type { background: #f3f4f6; color: #6b7280; }
.pt-badge-worker { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
.pt-card-count { font-size: 12px; font-weight: 600; color: #9ca3af; margin-top: 2px; }

.pt-card-body { display: none; padding: 0 16px 12px; }
.pt-card.open .pt-card-body { display: block; }

.pt-check {
    display: flex; align-items: center; gap: 8px; padding: 6px 0;
    border-bottom: 1px solid #f9fafb; font-size: 13px; color: #374151;
}
.pt-check:last-child { border-bottom: none; }
.pt-check.done { color: #9ca3af; text-decoration: line-through; }
.pt-check-box {
    width: 16px; height: 16px; border: 2px solid #d1d5db; border-radius: 4px;
    flex-shrink: 0; display: flex; align-items: center; justify-content: center;
}
.pt-check.done .pt-check-box { border-color: #22c55e; background: #22c55e; }
.pt-check.done .pt-check-box::after { content: '✓'; color: #fff; font-size: 10px; font-weight: 800; }
.pt-expand-icon {
    width: 16px; height: 16px; transition: transform .2s; flex-shrink: 0; color: #9ca3af;
}
.pt-card.open .pt-expand-icon { transform: rotate(90deg); }

/* ── Section header (for rooms/task views) ─────────────── */
.pt-section-header {
    font-size: 11px; font-weight: 700; color: #9ca3af; text-transform: uppercase;
    letter-spacing: 0.05em; padding: 16px 0 6px;
}

/* ── Empty state ───────────────────────────────────────── */
.pt-empty {
    text-align: center; padding: 60px 20px; color: #9ca3af;
}
.pt-empty p { font-size: 16px; font-weight: 600; margin-bottom: 4px; }
.pt-empty small { font-size: 13px; }

/* ── Print header (hidden on screen) ───────────────────── */
.pt-print-header { display: none; }

/* ═══════════════════════════════════════════════════════ */
/* PRINT STYLES                                           */
/* ═══════════════════════════════════════════════════════ */
@media print {
    /* Hide nav, controls, non-essential UI */
    nav, .nav-container, .pt-controls, .pt-print-btn, .pt-summary { display: none !important; }
    body { background: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .pt-container { max-width: 100%; padding: 0; }

    /* Show print header */
    .pt-print-header {
        display: block !important; padding: 0 0 12px; margin-bottom: 12px;
        border-bottom: 2px solid #000;
    }
    .pt-print-header h1 { font-size: 18px; margin: 0 0 2px; }
    .pt-print-header .pt-print-meta { font-size: 12px; color: #555; }

    /* Force all cards open */
    .pt-card-body { display: block !important; }
    .pt-card { border: 1px solid #ccc; border-radius: 0; break-inside: avoid; margin-bottom: 6px; }
    .pt-card-hdr { padding: 8px 12px; }
    .pt-expand-icon { display: none !important; }
    .pt-card-hdr:hover { background: transparent; }

    /* Clean typography */
    .pt-card-title { font-size: 12px; }
    .pt-card-sub { font-size: 10px; }
    .pt-check { font-size: 11px; padding: 3px 0; }
    .pt-check-box { width: 12px; height: 12px; border-width: 1.5px; }

    /* Tighter spacing */
    .pt-list { gap: 4px; }
    .pt-card-body { padding: 0 12px 6px; }
}
</style>

<!-- Print header (visible only when printing) -->
<div class="pt-print-header">
    <h1 id="printTitle">Task List</h1>
    <div class="pt-print-meta" id="printMeta"></div>
</div>

<div class="pt-container">

    <!-- Controls -->
    <div class="pt-controls">
        <div class="pt-control-group">
            <label>Date</label>
            <input type="date" id="ptDate" value="<?= date('Y-m-d') ?>">
        </div>

        <?php if ($canPickWorker): ?>
        <div class="pt-control-group">
            <label>Worker</label>
            <select id="ptWorker">
                <option value="<?= (int)$user['id'] ?>">Me (<?= htmlspecialchars($user['name']) ?>)</option>
                <?php foreach ($workers as $w): ?>
                    <?php if ($w['id'] != $user['id']): ?>
                    <option value="<?= (int)$w['id'] ?>"><?= htmlspecialchars($w['name']) ?> (<?= htmlspecialchars($w['role']) ?>)</option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <div class="pt-control-group">
            <label>Scope</label>
            <div class="pt-toggle" id="scopeToggle">
                <button class="active" data-val="personal" onclick="setScope('personal')">My Tasks</button>
                <button data-val="class" onclick="setScope('class')">Team</button>
            </div>
        </div>

        <div class="pt-control-group">
            <label>Group by</label>
            <div class="pt-toggle" id="viewToggle">
                <button class="active" data-val="default" onclick="setView('default')">Default</button>
                <button data-val="rooms" onclick="setView('rooms')">Rooms</button>
                <button data-val="task" onclick="setView('task')">Tasks</button>
            </div>
        </div>

        <div class="pt-control-group">
            <label>Completed</label>
            <div class="pt-toggle" id="hiddenToggle">
                <button data-val="hide" class="active" onclick="setShowCompleted(false)">Hide</button>
                <button data-val="show" onclick="setShowCompleted(true)">Show</button>
            </div>
        </div>

        <button class="pt-print-btn" onclick="doPrint()">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="vertical-align:middle;margin-right:4px;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            Print
        </button>
    </div>

    <!-- Summary -->
    <div class="pt-summary" id="ptSummary"></div>

    <!-- Task list -->
    <div class="pt-list" id="ptList"></div>
    <div class="pt-empty" id="ptEmpty" style="display:none;">
        <p>No tasks for this day</p>
        <small>Select a different date or check filters.</small>
    </div>
</div>

<script>
const USER_ID = <?= (int)$user['id'] ?>;
const USER_NAME = <?= json_encode($user['name']) ?>;
let ptDate = document.getElementById('ptDate').value;
let ptScope = 'personal';
let ptView = 'default';
let ptShowCompleted = false;
let ptWorkerId = USER_ID;
let assignments = [];

function esc(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

// ── Controls ──────────────────────────────────────────
function toggleBtn(groupId, val) {
    document.querySelectorAll('#' + groupId + ' button').forEach(b => {
        b.classList.toggle('active', b.getAttribute('data-val') === val);
    });
}

function setScope(v) { ptScope = v; toggleBtn('scopeToggle', v); loadData(); }
function setView(v) { ptView = v; toggleBtn('viewToggle', v); render(); }
function setShowCompleted(v) { ptShowCompleted = v; toggleBtn('hiddenToggle', v ? 'show' : 'hide'); render(); }

document.getElementById('ptDate').addEventListener('change', function() {
    ptDate = this.value; loadData();
});

<?php if ($canPickWorker): ?>
document.getElementById('ptWorker').addEventListener('change', function() {
    ptWorkerId = parseInt(this.value); loadData();
});
<?php endif; ?>

// ── Data loading ──────────────────────────────────────
function loadData() {
    fetch(BASE_PATH + `/api/tasks_api.php?action=get_janitor_assignments&date=${ptDate}&user_id=${ptWorkerId}&view=${ptScope}`)
        .then(r => r.json())
        .then(data => {
            assignments = data || [];
            renderSummary();
            render();
        })
        .catch(() => { assignments = []; renderSummary(); render(); });
}

// ── Summary ───────────────────────────────────────────
function renderSummary() {
    let total = 0, completed = 0;
    assignments.forEach(a => {
        const items = a.checklist || [];
        total += items.length;
        completed += items.filter(c => c.completed == 1).length;
    });
    const pending = total - completed;
    const pct = total > 0 ? Math.round(completed / total * 100) : 0;
    document.getElementById('ptSummary').innerHTML = `
        <div class="pt-summary-card"><div class="pt-summary-num">${total}</div><div class="pt-summary-label">Tasks</div></div>
        <div class="pt-summary-card"><div class="pt-summary-num" style="color:#22c55e;">${completed}</div><div class="pt-summary-label">Done</div></div>
        <div class="pt-summary-card"><div class="pt-summary-num" style="color:#f59e0b;">${pending}</div><div class="pt-summary-label">Left</div></div>
        <div class="pt-summary-card"><div class="pt-summary-num" style="color:#3b82f6;">${pct}%</div><div class="pt-summary-label">Progress</div></div>
    `;
}

// ── Render dispatcher ─────────────────────────────────
function render() {
    if (ptView === 'rooms') return renderRoomsView();
    if (ptView === 'task') return renderTaskView();
    renderDefaultView();
}

// ── Default view ──────────────────────────────────────
function renderDefaultView() {
    const list = document.getElementById('ptList');
    const empty = document.getElementById('ptEmpty');
    list.innerHTML = '';

    const hierOrder = { group_of_groups: 0, group: 1, task: 2 };
    let sorted = [...assignments].sort((a, b) => {
        const sa = a.sort_order ?? 0, sb = b.sort_order ?? 0;
        if (sa !== sb) return sa - sb;
        return (hierOrder[a.hierarchy_type] ?? 1) - (hierOrder[b.hierarchy_type] ?? 1);
    });

    let rendered = 0;
    sorted.forEach(a => {
        const checklist = a.checklist || [];
        const doneCount = checklist.filter(c => c.completed == 1).length;
        const isComplete = doneCount === checklist.length && checklist.length > 0;
        if (isComplete && !ptShowCompleted) return;

        const workerBadge = (ptScope === 'class' && a.assigned_to_name)
            ? `<span class="pt-card-badge pt-badge-worker">${esc(a.assigned_to_name)}</span>` : '';

        list.innerHTML += buildCard(
            a.group_name,
            `${esc(a.room_name)} &middot; ${esc(a.building_name)} ${workerBadge}`,
            a.type_name ? `<span class="pt-card-badge pt-badge-type">${esc(a.type_name)}</span>` : '',
            `${doneCount}/${checklist.length}`,
            checklist,
            isComplete
        );
        rendered++;
    });

    empty.style.display = rendered ? 'none' : 'block';
}

// ── Rooms view ────────────────────────────────────────
function renderRoomsView() {
    const list = document.getElementById('ptList');
    const empty = document.getElementById('ptEmpty');
    list.innerHTML = '';

    const rooms = {};
    assignments.forEach(a => {
        const key = a.room_name + ' · ' + a.building_name;
        if (!rooms[key]) rooms[key] = { room_name: a.room_name, building_name: a.building_name, items: [] };
        (a.checklist || []).forEach(c => {
            rooms[key].items.push({ ...c, group_name: a.group_name, assigned_to_name: a.assigned_to_name });
        });
    });

    let rendered = 0;
    Object.keys(rooms).sort().forEach(key => {
        const room = rooms[key];
        const doneCount = room.items.filter(c => c.completed == 1).length;
        const isComplete = doneCount === room.items.length && room.items.length > 0;
        if (isComplete && !ptShowCompleted) return;

        list.innerHTML += buildCard(
            room.room_name,
            esc(room.building_name),
            '',
            `${doneCount}/${room.items.length}`,
            room.items,
            isComplete
        );
        rendered++;
    });

    empty.style.display = rendered ? 'none' : 'block';
}

// ── Task view ─────────────────────────────────────────
function renderTaskView() {
    const list = document.getElementById('ptList');
    const empty = document.getElementById('ptEmpty');
    list.innerHTML = '';

    const taskMap = {};
    assignments.forEach(a => {
        (a.checklist || []).forEach(c => {
            if (!taskMap[c.task_name]) taskMap[c.task_name] = [];
            taskMap[c.task_name].push({
                ...c,
                room_name: a.room_name,
                building_name: a.building_name,
                assignment_id: a.id,
                assigned_to_name: a.assigned_to_name
            });
        });
    });

    let rendered = 0;
    Object.keys(taskMap).sort().forEach(taskName => {
        const items = taskMap[taskName];
        const doneCount = items.filter(c => c.completed == 1).length;
        const isComplete = doneCount === items.length && items.length > 0;
        if (isComplete && !ptShowCompleted) return;

        // Build room-based checklist display
        const roomChecklist = items.map(c => ({
            task_name: `${c.room_name} — ${c.building_name}`,
            completed: c.completed
        }));

        list.innerHTML += buildCard(
            taskName,
            `${items.length} room${items.length !== 1 ? 's' : ''}`,
            '',
            `${doneCount}/${items.length}`,
            roomChecklist,
            isComplete
        );
        rendered++;
    });

    empty.style.display = rendered ? 'none' : 'block';
}

// ── Card builder ──────────────────────────────────────
function buildCard(title, subtitle, badge, count, checklist, isComplete) {
    const checkHtml = checklist.map(c => {
        const isDone = c.completed == 1;
        if (!ptShowCompleted && isDone) return '';
        return `<div class="pt-check${isDone ? ' done' : ''}">
            <div class="pt-check-box"></div>
            <span>${esc(c.task_name)}</span>
        </div>`;
    }).join('');

    return `
    <div class="pt-card${isComplete ? ' completed' : ''}" onclick="this.classList.toggle('open')">
        <div class="pt-card-hdr">
            <svg class="pt-expand-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <div style="flex:1;min-width:0;">
                <div class="pt-card-title">${esc(title)}</div>
                <div class="pt-card-sub">${subtitle}</div>
            </div>
            <div class="pt-card-right">
                ${badge}
                <div class="pt-card-count">${count}</div>
            </div>
        </div>
        <div class="pt-card-body">${checkHtml}</div>
    </div>`;
}

// ── Print ─────────────────────────────────────────────
function doPrint() {
    // Set print header info
    const d = new Date(ptDate + 'T12:00:00');
    const dateStr = d.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    const scopeLabel = ptScope === 'class' ? 'Team Tasks' : 'My Tasks';
    const viewLabel = ptView === 'default' ? '' : ` — ${ptView.charAt(0).toUpperCase() + ptView.slice(1)} View`;
    const workerEl = document.getElementById('ptWorker');
    const workerName = workerEl ? workerEl.options[workerEl.selectedIndex].text : USER_NAME;

    document.getElementById('printTitle').textContent = `${scopeLabel}${viewLabel}`;
    document.getElementById('printMeta').textContent = `${dateStr}  •  ${workerName}  •  Printed ${new Date().toLocaleString()}`;

    // Expand all cards for print
    document.querySelectorAll('.pt-card').forEach(c => c.classList.add('open'));
    window.print();
}

// ── Init ──────────────────────────────────────────────
loadData();
</script>

</body>
</html>
