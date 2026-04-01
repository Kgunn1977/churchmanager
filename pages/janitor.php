<?php
$pageTitle = 'My Tasks — Church Facility Manager';
require_once __DIR__ . '/../includes/nav.php';
require_once __DIR__ . '/../config/database.php';
$db = getDB();
$user = getCurrentUser();
?>

<style>
/* ── Mobile-first layout ───────────────────────────────── */
.j-container { max-width:600px; margin:0 auto; padding:16px; }
.j-date-bar {
    display:flex; align-items:center; justify-content:space-between;
    background:#fff; border-radius:12px; padding:10px 16px;
    box-shadow:0 1px 4px rgba(0,0,0,.06); margin-bottom:16px;
    border:1px solid #e5e7eb;
}
.j-date-bar input[type=date] {
    border:none; font-size:15px; font-weight:700; color:#111827;
    background:transparent; outline:none; cursor:pointer;
    font-family:ui-sans-serif,system-ui,sans-serif;
}
.j-date-nav {
    background:none; border:1px solid #e5e7eb; border-radius:8px;
    width:36px; height:36px; display:flex; align-items:center; justify-content:center;
    cursor:pointer; color:#6b7280; transition:background .12s;
}
.j-date-nav:hover { background:#f3f4f6; }

/* ── Task cards ────────────────────────────────────────── */
.j-card {
    background:#fff; border-radius:12px; border:1px solid #e5e7eb;
    margin-bottom:12px; overflow:hidden;
    transition:border-color .15s;
}
.j-card.completed { opacity:.6; }
.j-card-hdr {
    display:flex; align-items:center; gap:10px; padding:14px 16px;
    cursor:pointer; user-select:none;
}
.j-card-hdr:active { background:#f9fafb; }
.j-expand-icon { transition:transform .2s; color:#9ca3af; flex-shrink:0; }
.j-card.open .j-expand-icon { transform:rotate(90deg); }
.j-card-body { display:none; padding:0 16px 14px; border-top:1px solid #f3f4f6; }
.j-card.open .j-card-body { display:block; }
.j-group-title { font-size:14px; font-weight:700; color:#111827; flex:1; }
.j-room-label { font-size:11px; color:#6b7280; }
.j-type-badge {
    font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px;
    background:#dbeafe; color:#1e40af;
}
.j-time-badge {
    font-size:10px; font-weight:600; padding:2px 8px; border-radius:20px;
    background:#f3f4f6; color:#6b7280;
}
.j-deadline {
    font-size:11px; color:#9ca3af; margin-top:2px;
}
.j-deadline.urgent { color:#ef4444; font-weight:600; }

/* ── Checklist ─────────────────────────────────────────── */
.j-check-item {
    display:flex; align-items:center; gap:10px; padding:10px 0;
    border-bottom:1px solid #f8fafc;
}
.j-check-item:last-child { border-bottom:none; }
.j-check-item input[type=checkbox] {
    width:20px; height:20px; accent-color:#2563eb; cursor:pointer; flex-shrink:0;
}
.j-check-label { font-size:13px; color:#374151; flex:1; }
.j-check-item.done .j-check-label { text-decoration:line-through; color:#9ca3af; }
.j-check-time { font-size:11px; color:#9ca3af; }

/* ── Summary bar ───────────────────────────────────────── */
.j-summary {
    display:flex; gap:12px; margin-bottom:16px; flex-wrap:wrap;
}
.j-stat {
    background:#fff; border-radius:10px; border:1px solid #e5e7eb;
    padding:10px 16px; flex:1; min-width:80px; text-align:center;
}
.j-stat-num { font-size:20px; font-weight:800; color:#111827; }
.j-stat-label { font-size:10px; font-weight:600; color:#9ca3af; text-transform:uppercase; letter-spacing:.05em; }

/* ── Empty state ───────────────────────────────────────── */
.j-empty {
    text-align:center; padding:48px 24px; color:#9ca3af;
}
.j-empty svg { width:48px; height:48px; margin:0 auto 12px; opacity:.3; }
</style>

<div class="j-container">

    <!-- Date bar -->
    <div class="j-date-bar">
        <button class="j-date-nav" onclick="adjDate(-1)" title="Previous day">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <input type="date" id="j-date" onchange="loadAssignments()">
        <button class="j-date-nav" onclick="adjDate(1)" title="Next day">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>
    </div>

    <!-- Summary -->
    <div class="j-summary" id="j-summary"></div>

    <!-- Task list -->
    <div id="j-list"></div>

    <!-- Empty state -->
    <div id="j-empty" class="j-empty hidden">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p style="font-size:14px;font-weight:500;margin:0 0 4px;">No tasks for this day</p>
        <small style="font-size:12px;">Select a different date or check with your supervisor.</small>
    </div>
</div>

<script>
const userId = <?= (int)$user['id'] ?>;

// ═══════════════════════════════════════════════════════════
// DATE
// ═══════════════════════════════════════════════════════════
function fmtDate(d) {
    return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
}

function adjDate(delta) {
    const input = document.getElementById('j-date');
    const d = new Date(input.value + 'T12:00:00');
    d.setDate(d.getDate() + delta);
    input.value = fmtDate(d);
    loadAssignments();
}

// Init to today
document.getElementById('j-date').value = fmtDate(new Date());

// ═══════════════════════════════════════════════════════════
// LOAD
// ═══════════════════════════════════════════════════════════
function loadAssignments() {
    const date = document.getElementById('j-date').value;
    fetch(`/api/tasks_api.php?action=get_janitor_assignments&date=${date}&user_id=${userId}`)
        .then(r => r.json())
        .then(assignments => {
            renderSummary(assignments);
            renderList(assignments);
        });
}

function renderSummary(assignments) {
    const total     = assignments.length;
    const completed = assignments.filter(a => a.status === 'completed').length;
    const pending   = total - completed;
    document.getElementById('j-summary').innerHTML = `
        <div class="j-stat"><div class="j-stat-num">${total}</div><div class="j-stat-label">Total</div></div>
        <div class="j-stat"><div class="j-stat-num" style="color:#22c55e;">${completed}</div><div class="j-stat-label">Done</div></div>
        <div class="j-stat"><div class="j-stat-num" style="color:#f59e0b;">${pending}</div><div class="j-stat-label">Remaining</div></div>
    `;
}

function renderList(assignments) {
    const list  = document.getElementById('j-list');
    const empty = document.getElementById('j-empty');
    list.innerHTML = '';

    if (!assignments.length) {
        empty.classList.remove('hidden');
        return;
    }
    empty.classList.add('hidden');

    assignments.forEach(a => {
        const isComplete = a.status === 'completed';
        const deadlineHtml = a.deadline ? formatDeadline(a.deadline) : '';
        const checklist = (a.checklist || []);
        const doneCount = checklist.filter(c => c.completed == 1).length;

        const card = document.createElement('div');
        card.className = 'j-card' + (isComplete ? ' completed' : '');
        card.id = 'assignment-' + a.id;

        card.innerHTML = `
            <div class="j-card-hdr" onclick="toggleCard(${a.id})">
                <svg class="j-expand-icon" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <div style="flex:1;min-width:0;">
                    <div class="j-group-title">${esc(a.group_name)}</div>
                    <div class="j-room-label">${esc(a.room_name)} · ${esc(a.building_name)}</div>
                    ${deadlineHtml}
                </div>
                <div style="text-align:right;">
                    <div class="j-type-badge">${esc(a.type_name)}</div>
                    <div class="j-time-badge" style="margin-top:4px;">${a.estimated_minutes} min</div>
                    <div style="font-size:11px;color:#9ca3af;margin-top:4px;">${doneCount}/${checklist.length}</div>
                </div>
            </div>
            <div class="j-card-body">
                ${checklist.map(c => `
                    <div class="j-check-item${c.completed == 1 ? ' done' : ''}">
                        <input type="checkbox" ${c.completed == 1 ? 'checked' : ''}
                               onchange="toggleCheck(${a.id}, ${c.task_id}, this.checked)">
                        <span class="j-check-label">${esc(c.task_name)}</span>
                    </div>
                `).join('')}
                ${!checklist.length ? '<div style="font-size:12px;color:#9ca3af;padding:8px 0;">No checklist items</div>' : ''}
            </div>
        `;
        list.appendChild(card);
    });
}

function formatDeadline(dt) {
    const deadline = new Date(dt);
    const now = new Date();
    const diffMin = (deadline - now) / 60000;
    const h = deadline.getHours();
    const m = deadline.getMinutes();
    const ampm = h < 12 ? 'AM' : 'PM';
    const disp = (h === 0 ? 12 : h > 12 ? h - 12 : h) + ':' + String(m).padStart(2, '0') + ' ' + ampm;
    const urgent = diffMin < 60 && diffMin > 0;
    return `<div class="j-deadline${urgent ? ' urgent' : ''}">Due by ${disp}</div>`;
}

// ═══════════════════════════════════════════════════════════
// INTERACTIONS
// ═══════════════════════════════════════════════════════════
function toggleCard(assignmentId) {
    document.getElementById('assignment-' + assignmentId).classList.toggle('open');
}

function toggleCheck(assignmentId, taskId, checked) {
    const fd = new FormData();
    fd.set('action', 'toggle_checklist_item');
    fd.set('assignment_id', assignmentId);
    fd.set('task_id', taskId);
    fd.set('completed', checked ? 1 : 0);
    fetch('/api/tasks_api.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(r => {
            if (r.success) loadAssignments();
        });
}

function esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ═══════════════════════════════════════════════════════════
// INIT
// ═══════════════════════════════════════════════════════════
loadAssignments();
</script>

</body>
</html>
