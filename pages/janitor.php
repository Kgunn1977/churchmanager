<?php
$pageTitle = 'My Tasks — Church Facility Manager';
$isEmbed = isset($_GET['embed']) && $_GET['embed'] === '1';
if ($isEmbed) {
    // Minimal bootstrap for embedded/iframe mode — skip nav bar
    require_once __DIR__ . '/../includes/auth.php';
    requireLogin();
    $currentUser = getCurrentUser();
    require_once __DIR__ . '/../config/database.php';
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>My Tasks</title><script src="https://cdn.tailwindcss.com"></script>';
    echo '<script>const BASE_PATH = ' . json_encode(defined('BASE_PATH') ? BASE_PATH : '') . ';</script></head><body class="bg-gray-100" style="min-height:100vh;">';
} else {
    require_once __DIR__ . '/../includes/nav.php';
}
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

/* ── Sub-groups ───────────────────────────────────────── */
.j-subgroup { margin-bottom:4px; }
.j-subgroup-hdr {
    display:flex; align-items:center; gap:8px; padding:8px 0 4px;
    cursor:pointer; user-select:none; font-size:13px; font-weight:700; color:#374151;
}
.j-subgroup-hdr:hover { color:#1e40af; }
.j-subgroup-body { display:none; margin-left:22px; }
.j-subgroup.open > .j-subgroup-body { display:block; }
.j-subgroup.open > .j-subgroup-hdr .j-expand-icon { transform:rotate(90deg); }

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

    <?php if ($canPickWorker): ?>
    <!-- Worker selector (admin/scheduler only) -->
    <div style="margin-bottom:12px;">
        <select id="j-worker" onchange="loadAssignments()"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-semibold focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            <?php foreach ($workers as $w): ?>
                <option value="<?= (int)$w['id'] ?>" <?= $w['id'] == $user['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($w['name']) ?> (<?= htmlspecialchars($w['role']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>

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
const defaultUserId = <?= (int)$user['id'] ?>;
const canPickWorker = <?= $canPickWorker ? 'true' : 'false' ?>;

function getActiveUserId() {
    if (canPickWorker) {
        const sel = document.getElementById('j-worker');
        return sel ? parseInt(sel.value) : defaultUserId;
    }
    return defaultUserId;
}

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
    fetch(`${BASE_PATH}/api/tasks_api.php?action=get_janitor_assignments&date=${date}&user_id=${getActiveUserId()}`)
        .then(r => r.json())
        .then(assignments => {
            renderSummary(assignments);
            renderList(assignments);
        });
}

function renderSummary(assignments) {
    // Count individual checklist items, not group-level assignments
    let total = 0, completed = 0;
    assignments.forEach(a => {
        const items = a.checklist || [];
        total += items.length;
        completed += items.filter(c => c.completed == 1).length;
    });
    const pending = total - completed;
    document.getElementById('j-summary').innerHTML = `
        <div class="j-stat"><div class="j-stat-num">${total}</div><div class="j-stat-label">Tasks</div></div>
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

        // Build checklist HTML — group by sub_group_name if present
        let checklistHtml = '';
        const subGroups = {};
        const ungrouped = [];
        checklist.forEach(c => {
            if (c.sub_group_name) {
                if (!subGroups[c.sub_group_name]) subGroups[c.sub_group_name] = [];
                subGroups[c.sub_group_name].push(c);
            } else {
                ungrouped.push(c);
            }
        });

        const sgNames = Object.keys(subGroups);
        if (sgNames.length > 0) {
            // Render sub-groups with expand/collapse
            sgNames.forEach((sgName, idx) => {
                const sgItems = subGroups[sgName];
                const sgDone = sgItems.filter(c => c.completed == 1).length;
                const sgAllDone = sgDone === sgItems.length;
                checklistHtml += `
                    <div class="j-subgroup" data-sg-name="${esc(sgName)}">
                        <div class="j-subgroup-hdr" onclick="toggleSubGroup(this.parentElement)">
                            <svg class="j-expand-icon" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                            <span style="flex:1;">${esc(sgName)}</span>
                            <span style="font-size:11px;color:${sgAllDone ? '#22c55e' : '#9ca3af'};font-weight:600;">${sgDone}/${sgItems.length}</span>
                        </div>
                        <div class="j-subgroup-body">
                            ${sgItems.map(c => `
                                <div class="j-check-item${c.completed == 1 ? ' done' : ''}" data-task-id="${c.task_id}">
                                    <input type="checkbox" ${c.completed == 1 ? 'checked' : ''}
                                           onchange="toggleCheck(${a.id}, ${c.task_id}, this.checked)">
                                    <span class="j-check-label">${esc(c.task_name)}</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>`;
            });
        }

        // Ungrouped items (or all items if no sub-groups)
        if (ungrouped.length > 0 || sgNames.length === 0) {
            const items = ungrouped.length > 0 ? ungrouped : (sgNames.length === 0 ? checklist : []);
            items.forEach(c => {
                checklistHtml += `
                    <div class="j-check-item${c.completed == 1 ? ' done' : ''}" data-task-id="${c.task_id}">
                        <input type="checkbox" ${c.completed == 1 ? 'checked' : ''}
                               onchange="toggleCheck(${a.id}, ${c.task_id}, this.checked)">
                        <span class="j-check-label">${esc(c.task_name)}</span>
                    </div>`;
            });
        }

        if (!checklist.length) {
            checklistHtml = '<div style="font-size:12px;color:#9ca3af;padding:8px 0;">No checklist items</div>';
        }

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
                    <div class="j-done-count" style="font-size:11px;color:#9ca3af;margin-top:4px;">${doneCount}/${checklist.length}</div>
                </div>
            </div>
            <div class="j-card-body">
                ${checklistHtml}
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
    const card = document.getElementById('assignment-' + assignmentId);
    const wasOpen = card.classList.contains('open');
    card.classList.toggle('open');

    // Auto-expand only the first sub-group when opening
    if (!wasOpen) {
        const firstSubGroup = card.querySelector('.j-subgroup');
        if (firstSubGroup && !firstSubGroup.classList.contains('open')) {
            firstSubGroup.classList.add('open');
        }
    }
}

function toggleSubGroup(el) {
    el.classList.toggle('open');
}

function toggleCheck(assignmentId, taskId, checked) {
    const fd = new FormData();
    fd.set('action', 'toggle_checklist_item');
    fd.set('assignment_id', assignmentId);
    fd.set('task_id', taskId);
    fd.set('completed', checked ? 1 : 0);
    fetch(BASE_PATH + '/api/tasks_api.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(r => {
            if (!r.success) return;

            // Update UI in-place instead of reloading (prevents collapse)
            const card = document.getElementById('assignment-' + assignmentId);
            if (!card) { loadAssignments(); return; }

            // Update the checkbox item
            const item = card.querySelector(`.j-check-item[data-task-id="${taskId}"]`);
            if (item) {
                const cb = item.querySelector('input[type=checkbox]');
                cb.checked = checked;
                item.classList.toggle('done', checked);
            }

            // Update the count display
            const allItems = card.querySelectorAll('.j-check-item');
            const doneItems = card.querySelectorAll('.j-check-item.done');
            const countEl = card.querySelector('.j-done-count');
            if (countEl) countEl.textContent = `${doneItems.length}/${allItems.length}`;

            // Update card completed state
            if (r.assignment_status === 'completed') {
                card.classList.add('completed');
            } else {
                card.classList.remove('completed');
            }

            // Update summary bar
            updateSummaryInPlace();

            // Auto-expand next subgroup if all tasks in current subgroup are done
            if (checked && item) {
                const subgroup = item.closest('.j-subgroup');
                if (subgroup) {
                    const sgItems = subgroup.querySelectorAll('.j-check-item');
                    const sgDone  = subgroup.querySelectorAll('.j-check-item.done');
                    if (sgItems.length > 0 && sgItems.length === sgDone.length) {
                        // Collapse finished subgroup
                        subgroup.classList.remove('open');
                        // Open the next sibling subgroup
                        let next = subgroup.nextElementSibling;
                        while (next && !next.classList.contains('j-subgroup')) {
                            next = next.nextElementSibling;
                        }
                        if (next && !next.classList.contains('open')) {
                            next.classList.add('open');
                        }
                    }
                }
            }
        });
}

function updateSummaryInPlace() {
    let total = 0, completed = 0;
    document.querySelectorAll('.j-check-item').forEach(el => {
        total++;
        if (el.classList.contains('done')) completed++;
    });
    const pending = total - completed;
    document.getElementById('j-summary').innerHTML = `
        <div class="j-stat"><div class="j-stat-num">${total}</div><div class="j-stat-label">Tasks</div></div>
        <div class="j-stat"><div class="j-stat-num" style="color:#22c55e;">${completed}</div><div class="j-stat-label">Done</div></div>
        <div class="j-stat"><div class="j-stat-num" style="color:#f59e0b;">${pending}</div><div class="j-stat-label">Remaining</div></div>
    `;
}

// ═══════════════════════════════════════════════════════════
// INIT
// ═══════════════════════════════════════════════════════════
loadAssignments();
</script>

</body>
</html>
