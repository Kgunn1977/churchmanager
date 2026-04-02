<?php
require_once __DIR__ . '/../includes/auth.php';
if (!isLoggedIn()) {
    header('Location: /pwa/login.php');
    exit;
}
$user = getCurrentUser();

// If not running as installed PWA, redirect to install page (first visit only)
// The install page sets a cookie so we don't redirect in a loop
if (!isset($_COOKIE['cfm_pwa_seen'])) {
    setcookie('cfm_pwa_seen', '1', time() + 86400 * 365, '/pwa/');
    header('Location: /pwa/install.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#1e40af">
    <title>My Tasks</title>
    <link rel="manifest" href="/pwa/manifest.json">
    <link rel="apple-touch-icon" href="/pwa/icons/icon-192.svg">
    <style>
/* ═══════════════════════════════════════════════════════════
   RESET & BASE
   ═══════════════════════════════════════════════════════════ */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html, body {
    height: 100%; overflow: hidden;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: #f0f2f5;
    -webkit-tap-highlight-color: transparent;
    -webkit-user-select: none; user-select: none;
}

/* ═══════════════════════════════════════════════════════════
   APP SHELL
   ═══════════════════════════════════════════════════════════ */
#app {
    display: flex; flex-direction: column;
    height: 100vh; height: 100dvh;
}

/* ── Top bar ──────────────────────────────────────────────── */
.top-bar {
    background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
    color: #fff; padding: 12px 16px;
    padding-top: max(12px, env(safe-area-inset-top));
    display: flex; align-items: center; justify-content: space-between;
    flex-shrink: 0;
}
.top-bar-title {
    font-size: 17px; font-weight: 700; letter-spacing: -0.01em;
}
.top-bar-sub {
    font-size: 11px; color: rgba(255,255,255,.7); margin-top: 1px;
}
.top-bar-right {
    display: flex; align-items: center; gap: 12px;
}
.sync-indicator {
    width: 8px; height: 8px; border-radius: 50%;
    background: #22c55e; transition: background .3s;
}
.sync-indicator.offline { background: #f59e0b; }
.sync-indicator.syncing { background: #3b82f6; animation: pulse .8s infinite; }
@keyframes pulse { 0%,100% { opacity: 1; } 50% { opacity: .3; } }
.logout-btn {
    background: rgba(255,255,255,.15); border: none; border-radius: 8px;
    color: #fff; font-size: 12px; font-weight: 600;
    padding: 6px 12px; cursor: pointer;
}

/* ── Date strip ───────────────────────────────────────────── */
.date-strip {
    display: flex; align-items: center; gap: 4px;
    padding: 12px 16px; background: #fff;
    border-bottom: 1px solid #e5e7eb; flex-shrink: 0;
    overflow-x: auto; -webkit-overflow-scrolling: touch;
    scrollbar-width: none; -ms-overflow-style: none;
}
.date-strip::-webkit-scrollbar { display: none; }
.date-chip {
    flex-shrink: 0; display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    width: 52px; height: 60px; border-radius: 12px;
    background: #f9fafb; border: 2px solid transparent;
    cursor: pointer; transition: all .15s;
}
.date-chip.selected {
    background: #1e40af; border-color: #1e40af; color: #fff;
}
.date-chip.today:not(.selected) {
    border-color: #3b82f6;
}
.date-chip .day-name { font-size: 10px; font-weight: 600; color: #9ca3af; text-transform: uppercase; }
.date-chip .day-num  { font-size: 18px; font-weight: 800; color: #111827; line-height: 1.1; }
.date-chip.selected .day-name,
.date-chip.selected .day-num { color: #fff; }
.date-chip .task-dot {
    width: 5px; height: 5px; border-radius: 50%;
    background: #3b82f6; margin-top: 2px;
}
.date-chip.selected .task-dot { background: #93c5fd; }

/* ── Summary cards ────────────────────────────────────────── */
.summary-row {
    display: flex; gap: 8px; padding: 12px 16px; flex-shrink: 0;
}
.summary-card {
    flex: 1; background: #fff; border-radius: 12px;
    border: 1px solid #e5e7eb; padding: 10px 12px;
    text-align: center;
}
.summary-num { font-size: 22px; font-weight: 800; }
.summary-label { font-size: 10px; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: .04em; }

/* ── Scrollable task list ─────────────────────────────────── */
.task-scroll {
    flex: 1; overflow-y: auto; padding: 0 16px 16px;
    -webkit-overflow-scrolling: touch;
    padding-bottom: max(16px, env(safe-area-inset-bottom));
}

/* ── Task card ────────────────────────────────────────────── */
.task-card {
    background: #fff; border-radius: 14px;
    border: 1px solid #e5e7eb; margin-bottom: 10px;
    overflow: hidden; transition: border-color .15s, opacity .2s;
}
.task-card.completed { opacity: .55; }
.task-card-hdr {
    display: flex; align-items: center; gap: 10px;
    padding: 14px 16px; cursor: pointer;
}
.task-card-hdr:active { background: #f9fafb; }
.expand-icon {
    transition: transform .2s; color: #9ca3af; flex-shrink: 0;
}
.task-card.open .expand-icon { transform: rotate(90deg); }
.task-card-body {
    display: none; padding: 0 16px 14px;
    border-top: 1px solid #f3f4f6;
}
.task-card.open .task-card-body { display: block; }

.group-title { font-size: 15px; font-weight: 700; color: #111827; }
.room-label { font-size: 12px; color: #6b7280; margin-top: 1px; }
.deadline-label { font-size: 11px; color: #9ca3af; margin-top: 2px; }
.deadline-label.urgent { color: #ef4444; font-weight: 600; }
.type-badge {
    font-size: 10px; font-weight: 700; padding: 3px 10px; border-radius: 20px;
    background: #dbeafe; color: #1e40af;
}
.time-badge {
    font-size: 10px; font-weight: 600; padding: 3px 10px; border-radius: 20px;
    background: #f3f4f6; color: #6b7280; margin-top: 4px;
}
.done-count {
    font-size: 11px; color: #9ca3af; margin-top: 4px; font-weight: 600;
}

/* ── Subgroup ─────────────────────────────────────────────── */
.subgroup { margin-bottom: 4px; }
.subgroup-hdr {
    display: flex; align-items: center; gap: 8px;
    padding: 10px 0 4px; cursor: pointer;
    font-size: 13px; font-weight: 700; color: #374151;
}
.subgroup-hdr:active { color: #1e40af; }
.subgroup-body { display: none; margin-left: 22px; }
.subgroup.open > .subgroup-body { display: block; }
.subgroup.open > .subgroup-hdr .expand-icon { transform: rotate(90deg); }

/* ── Check items ──────────────────────────────────────────── */
.check-item {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 0; border-bottom: 1px solid #f8fafc;
}
.check-item:last-child { border-bottom: none; }
.check-item input[type=checkbox] {
    width: 22px; height: 22px; accent-color: #2563eb;
    cursor: pointer; flex-shrink: 0;
}
.check-label { font-size: 14px; color: #374151; flex: 1; }
.check-item.done .check-label {
    text-decoration: line-through; color: #9ca3af;
}

/* ── Empty state ──────────────────────────────────────────── */
.empty-state {
    text-align: center; padding: 60px 24px; color: #9ca3af;
}
.empty-state svg { width: 56px; height: 56px; margin: 0 auto 16px; opacity: .25; }
.empty-state p { font-size: 15px; font-weight: 600; margin-bottom: 4px; }
.empty-state small { font-size: 13px; }

/* ── Offline banner ───────────────────────────────────────── */
.offline-banner {
    display: none; background: #fef3c7; color: #92400e;
    font-size: 12px; font-weight: 600; text-align: center;
    padding: 6px 16px; flex-shrink: 0;
}
.offline-banner.visible { display: block; }

/* ── Pull to refresh indicator ────────────────────────────── */
.refresh-hint {
    text-align: center; font-size: 11px; color: #9ca3af;
    padding: 8px 0; display: none;
}
    </style>
</head>
<body>
<div id="app">

    <!-- Top bar -->
    <div class="top-bar">
        <div>
            <div class="top-bar-title">My Tasks</div>
            <div class="top-bar-sub"><?= htmlspecialchars($user['name']) ?></div>
        </div>
        <div class="top-bar-right">
            <div class="sync-indicator" id="syncDot" title="Online"></div>
            <button class="logout-btn" onclick="location.href='/pwa/login.php?logout=1'">Sign Out</button>
        </div>
    </div>

    <!-- Offline banner -->
    <div class="offline-banner" id="offlineBanner">You're offline — changes will sync when reconnected</div>

    <!-- Date strip (horizontal scrollable week) -->
    <div class="date-strip" id="dateStrip"></div>

    <!-- Summary -->
    <div class="summary-row" id="summaryRow"></div>

    <!-- Task list -->
    <div class="task-scroll" id="taskScroll">
        <div id="taskList"></div>
        <div class="empty-state" id="emptyState" style="display:none;">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p>No tasks for this day</p>
            <small>Select a different date or check with your supervisor.</small>
        </div>
    </div>

</div>

<script>
// ═══════════════════════════════════════════════════════════
// CONFIG
// ═══════════════════════════════════════════════════════════
const USER_ID = <?= (int)$user['id'] ?>;
const USER_NAME = <?= json_encode($user['name']) ?>;
let selectedDate = todayStr();
let assignments = [];
let isOnline = navigator.onLine;

// ═══════════════════════════════════════════════════════════
// SERVICE WORKER REGISTRATION
// ═══════════════════════════════════════════════════════════
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/pwa/sw.js').then(reg => {
        console.log('SW registered:', reg.scope);
    }).catch(err => {
        console.warn('SW registration failed:', err);
    });

    // Listen for sync-complete messages from SW
    navigator.serviceWorker.addEventListener('message', e => {
        if (e.data && e.data.type === 'sync-complete') {
            loadAssignments(); // Refresh after sync
        }
    });
}

// ═══════════════════════════════════════════════════════════
// ONLINE/OFFLINE HANDLING
// ═══════════════════════════════════════════════════════════
function updateOnlineStatus() {
    isOnline = navigator.onLine;
    const dot = document.getElementById('syncDot');
    const banner = document.getElementById('offlineBanner');
    dot.className = 'sync-indicator' + (isOnline ? '' : ' offline');
    dot.title = isOnline ? 'Online' : 'Offline';
    banner.classList.toggle('visible', !isOnline);

    if (isOnline) {
        // Trigger background sync if supported
        if ('serviceWorker' in navigator && 'SyncManager' in window) {
            navigator.serviceWorker.ready.then(reg => reg.sync.register('sync-tasks'));
        } else {
            flushLocalQueue();
        }
    }
}
window.addEventListener('online', updateOnlineStatus);
window.addEventListener('offline', updateOnlineStatus);

// ═══════════════════════════════════════════════════════════
// DATE HELPERS
// ═══════════════════════════════════════════════════════════
function todayStr() {
    const d = new Date();
    return fmt(d);
}
function fmt(d) {
    return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
}
function parseDate(str) {
    const [y, m, d] = str.split('-').map(Number);
    return new Date(y, m - 1, d);
}
const DAY_NAMES = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

// ═══════════════════════════════════════════════════════════
// DATE STRIP — show 2 weeks centered on today
// ═══════════════════════════════════════════════════════════
function renderDateStrip() {
    const strip = document.getElementById('dateStrip');
    const today = new Date();
    today.setHours(0,0,0,0);
    const start = new Date(today);
    start.setDate(start.getDate() - 3); // 3 days before today

    let html = '';
    for (let i = 0; i < 14; i++) {
        const d = new Date(start);
        d.setDate(d.getDate() + i);
        const ds = fmt(d);
        const isToday = ds === todayStr();
        const isSel = ds === selectedDate;
        let cls = 'date-chip';
        if (isSel) cls += ' selected';
        if (isToday && !isSel) cls += ' today';
        html += `<div class="${cls}" onclick="selectDate('${ds}')" data-date="${ds}">
            <span class="day-name">${DAY_NAMES[d.getDay()]}</span>
            <span class="day-num">${d.getDate()}</span>
        </div>`;
    }
    strip.innerHTML = html;

    // Scroll selected into view
    requestAnimationFrame(() => {
        const sel = strip.querySelector('.selected');
        if (sel) sel.scrollIntoView({ inline: 'center', behavior: 'smooth' });
    });
}

function selectDate(ds) {
    selectedDate = ds;
    renderDateStrip();
    loadAssignments();
}

// ═══════════════════════════════════════════════════════════
// LOAD ASSIGNMENTS
// ═══════════════════════════════════════════════════════════
function loadAssignments() {
    const dot = document.getElementById('syncDot');
    if (isOnline) {
        dot.className = 'sync-indicator syncing';
    }

    fetch(`/api/tasks_api.php?action=get_janitor_assignments&date=${selectedDate}&user_id=${USER_ID}`)
        .then(r => r.json())
        .then(data => {
            assignments = data;
            renderSummary();
            renderList();
            dot.className = 'sync-indicator';

            // Cache locally for offline
            try {
                localStorage.setItem('cfm_tasks_' + selectedDate, JSON.stringify(data));
                localStorage.setItem('cfm_tasks_updated', Date.now());
            } catch(e) {}
        })
        .catch(() => {
            // Offline — load from localStorage
            dot.className = 'sync-indicator offline';
            try {
                const cached = localStorage.getItem('cfm_tasks_' + selectedDate);
                if (cached) {
                    assignments = JSON.parse(cached);
                    renderSummary();
                    renderList();
                }
            } catch(e) {}
        });
}

// ═══════════════════════════════════════════════════════════
// RENDER SUMMARY
// ═══════════════════════════════════════════════════════════
function renderSummary() {
    let total = 0, completed = 0;
    assignments.forEach(a => {
        const items = a.checklist || [];
        total += items.length;
        completed += items.filter(c => c.completed == 1).length;
    });
    const pending = total - completed;
    const pct = total > 0 ? Math.round(completed / total * 100) : 0;

    document.getElementById('summaryRow').innerHTML = `
        <div class="summary-card">
            <div class="summary-num">${total}</div>
            <div class="summary-label">Tasks</div>
        </div>
        <div class="summary-card">
            <div class="summary-num" style="color:#22c55e;">${completed}</div>
            <div class="summary-label">Done</div>
        </div>
        <div class="summary-card">
            <div class="summary-num" style="color:#f59e0b;">${pending}</div>
            <div class="summary-label">Left</div>
        </div>
        <div class="summary-card">
            <div class="summary-num" style="color:#3b82f6;">${pct}%</div>
            <div class="summary-label">Progress</div>
        </div>
    `;
}

// ═══════════════════════════════════════════════════════════
// RENDER TASK LIST
// ═══════════════════════════════════════════════════════════
function renderList() {
    const list = document.getElementById('taskList');
    const empty = document.getElementById('emptyState');
    list.innerHTML = '';

    if (!assignments.length) {
        empty.style.display = '';
        return;
    }
    empty.style.display = 'none';

    assignments.forEach(a => {
        const checklist = a.checklist || [];
        const doneCount = checklist.filter(c => c.completed == 1).length;
        const isComplete = a.status === 'completed';
        const deadlineHtml = a.deadline ? fmtDeadline(a.deadline) : '';

        // Group by sub_group_name
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

        let bodyHtml = '';
        const sgNames = Object.keys(subGroups);

        if (sgNames.length > 0) {
            sgNames.forEach(sgName => {
                const items = subGroups[sgName];
                const sgDone = items.filter(c => c.completed == 1).length;
                const sgAllDone = sgDone === items.length;
                bodyHtml += `
                    <div class="subgroup" data-sg-name="${esc(sgName)}">
                        <div class="subgroup-hdr" onclick="toggleSubGroup(this.parentElement)">
                            <svg class="expand-icon" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                            <span style="flex:1;">${esc(sgName)}</span>
                            <span style="font-size:11px;color:${sgAllDone ? '#22c55e' : '#9ca3af'};font-weight:600;">${sgDone}/${items.length}</span>
                        </div>
                        <div class="subgroup-body">
                            ${items.map(c => renderCheckItem(a.id, c)).join('')}
                        </div>
                    </div>`;
            });
        }

        // Ungrouped
        const flatItems = ungrouped.length > 0 ? ungrouped : (sgNames.length === 0 ? checklist : []);
        flatItems.forEach(c => { bodyHtml += renderCheckItem(a.id, c); });

        if (!checklist.length) {
            bodyHtml = '<div style="font-size:13px;color:#9ca3af;padding:12px 0;">No checklist items</div>';
        }

        const card = document.createElement('div');
        card.className = 'task-card' + (isComplete ? ' completed' : '');
        card.id = 'assignment-' + a.id;
        card.innerHTML = `
            <div class="task-card-hdr" onclick="toggleCard(${a.id})">
                <svg class="expand-icon" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <div style="flex:1;min-width:0;">
                    <div class="group-title">${esc(a.group_name)}</div>
                    <div class="room-label">${esc(a.room_name)} &middot; ${esc(a.building_name)}</div>
                    ${deadlineHtml}
                </div>
                <div style="text-align:right;">
                    <div class="type-badge">${esc(a.type_name)}</div>
                    <div class="time-badge">${a.estimated_minutes} min</div>
                    <div class="done-count">${doneCount}/${checklist.length}</div>
                </div>
            </div>
            <div class="task-card-body">${bodyHtml}</div>
        `;
        list.appendChild(card);
    });
}

function renderCheckItem(assignmentId, c) {
    return `
        <div class="check-item${c.completed == 1 ? ' done' : ''}" data-task-id="${c.task_id}">
            <input type="checkbox" ${c.completed == 1 ? 'checked' : ''}
                   onchange="toggleCheck(${assignmentId}, ${c.task_id}, this.checked)">
            <span class="check-label">${esc(c.task_name)}</span>
        </div>`;
}

function fmtDeadline(dt) {
    const deadline = new Date(dt);
    const now = new Date();
    const diffMin = (deadline - now) / 60000;
    const h = deadline.getHours();
    const m = deadline.getMinutes();
    const ampm = h < 12 ? 'AM' : 'PM';
    const disp = (h === 0 ? 12 : h > 12 ? h - 12 : h) + ':' + String(m).padStart(2, '0') + ' ' + ampm;
    const urgent = diffMin < 60 && diffMin > 0;
    return `<div class="deadline-label${urgent ? ' urgent' : ''}">Due by ${disp}</div>`;
}

// ═══════════════════════════════════════════════════════════
// INTERACTIONS
// ═══════════════════════════════════════════════════════════
function toggleCard(assignmentId) {
    const card = document.getElementById('assignment-' + assignmentId);
    const wasOpen = card.classList.contains('open');
    card.classList.toggle('open');
    if (!wasOpen) {
        const firstSg = card.querySelector('.subgroup');
        if (firstSg && !firstSg.classList.contains('open')) {
            firstSg.classList.add('open');
        }
    }
}

function toggleSubGroup(el) {
    el.classList.toggle('open');
}

function toggleCheck(assignmentId, taskId, checked) {
    // Optimistic UI update
    const card = document.getElementById('assignment-' + assignmentId);
    const item = card ? card.querySelector(`.check-item[data-task-id="${taskId}"]`) : null;
    if (item) {
        item.querySelector('input[type=checkbox]').checked = checked;
        item.classList.toggle('done', checked);
    }
    updateCardCounts(card);
    updateSummary();

    // Auto-expand next subgroup
    if (checked && item) {
        const sg = item.closest('.subgroup');
        if (sg) {
            const sgAll = sg.querySelectorAll('.check-item');
            const sgDone = sg.querySelectorAll('.check-item.done');
            if (sgAll.length > 0 && sgAll.length === sgDone.length) {
                // Update subgroup header count color
                const countSpan = sg.querySelector('.subgroup-hdr span:last-child');
                if (countSpan) countSpan.style.color = '#22c55e';

                // Collapse finished subgroup
                sg.classList.remove('open');
                // Open the next sibling subgroup
                let next = sg.nextElementSibling;
                while (next && !next.classList.contains('subgroup')) next = next.nextElementSibling;
                if (next && !next.classList.contains('open')) next.classList.add('open');
            }
        }
    }

    // Also update local cache
    updateLocalCache(assignmentId, taskId, checked);

    // Send to server (or queue for offline)
    const payload = {
        url: '/api/tasks_api.php',
        data: {
            action: 'toggle_checklist_item',
            assignment_id: assignmentId,
            task_id: taskId,
            completed: checked ? 1 : 0
        }
    };

    if (isOnline) {
        const fd = new FormData();
        Object.entries(payload.data).forEach(([k, v]) => fd.set(k, String(v)));
        fetch(payload.url, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(r => {
                if (r.assignment_status === 'completed' && card) {
                    card.classList.add('completed');
                } else if (card) {
                    card.classList.remove('completed');
                }
            })
            .catch(() => queueOfflineAction(payload));
    } else {
        queueOfflineAction(payload);
    }
}

function updateCardCounts(card) {
    if (!card) return;
    const all = card.querySelectorAll('.check-item');
    const done = card.querySelectorAll('.check-item.done');
    const countEl = card.querySelector('.done-count');
    if (countEl) countEl.textContent = `${done.length}/${all.length}`;

    // Update subgroup counts
    card.querySelectorAll('.subgroup').forEach(sg => {
        const sgAll = sg.querySelectorAll('.check-item');
        const sgDone = sg.querySelectorAll('.check-item.done');
        const hdrCount = sg.querySelector('.subgroup-hdr span:last-child');
        if (hdrCount) {
            hdrCount.textContent = `${sgDone.length}/${sgAll.length}`;
            hdrCount.style.color = sgAll.length === sgDone.length ? '#22c55e' : '#9ca3af';
        }
    });

    if (all.length === done.length) {
        card.classList.add('completed');
    } else {
        card.classList.remove('completed');
    }
}

function updateSummary() {
    let total = 0, completed = 0;
    document.querySelectorAll('.check-item').forEach(el => {
        total++;
        if (el.classList.contains('done')) completed++;
    });
    const pending = total - completed;
    const pct = total > 0 ? Math.round(completed / total * 100) : 0;
    document.getElementById('summaryRow').innerHTML = `
        <div class="summary-card">
            <div class="summary-num">${total}</div>
            <div class="summary-label">Tasks</div>
        </div>
        <div class="summary-card">
            <div class="summary-num" style="color:#22c55e;">${completed}</div>
            <div class="summary-label">Done</div>
        </div>
        <div class="summary-card">
            <div class="summary-num" style="color:#f59e0b;">${pending}</div>
            <div class="summary-label">Left</div>
        </div>
        <div class="summary-card">
            <div class="summary-num" style="color:#3b82f6;">${pct}%</div>
            <div class="summary-label">Progress</div>
        </div>
    `;
}

// ═══════════════════════════════════════════════════════════
// OFFLINE QUEUE
// ═══════════════════════════════════════════════════════════
function updateLocalCache(assignmentId, taskId, checked) {
    try {
        const key = 'cfm_tasks_' + selectedDate;
        const data = JSON.parse(localStorage.getItem(key) || '[]');
        data.forEach(a => {
            if (a.id === assignmentId && a.checklist) {
                a.checklist.forEach(c => {
                    if (c.task_id === taskId) c.completed = checked ? 1 : 0;
                });
            }
        });
        localStorage.setItem(key, JSON.stringify(data));
    } catch(e) {}
}

function queueOfflineAction(payload) {
    // Try to send to service worker
    if (navigator.serviceWorker && navigator.serviceWorker.controller) {
        navigator.serviceWorker.controller.postMessage({
            type: 'queue-action',
            payload: payload
        });
    } else {
        // Fallback: store in localStorage
        try {
            const queue = JSON.parse(localStorage.getItem('cfm_offline_queue') || '[]');
            queue.push(payload);
            localStorage.setItem('cfm_offline_queue', JSON.stringify(queue));
        } catch(e) {}
    }
}

async function flushLocalQueue() {
    try {
        const queue = JSON.parse(localStorage.getItem('cfm_offline_queue') || '[]');
        if (!queue.length) return;

        const remaining = [];
        for (const item of queue) {
            try {
                const fd = new FormData();
                Object.entries(item.data).forEach(([k, v]) => fd.set(k, String(v)));
                const res = await fetch(item.url, { method: 'POST', body: fd });
                if (!res.ok) remaining.push(item);
            } catch {
                remaining.push(item);
            }
        }

        if (remaining.length > 0) {
            localStorage.setItem('cfm_offline_queue', JSON.stringify(remaining));
        } else {
            localStorage.removeItem('cfm_offline_queue');
        }

        loadAssignments(); // Refresh
    } catch(e) {}
}

// ═══════════════════════════════════════════════════════════
// UTILITIES
// ═══════════════════════════════════════════════════════════
function esc(s) {
    if (!s) return '';
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

// ═══════════════════════════════════════════════════════════
// INIT
// ═══════════════════════════════════════════════════════════
updateOnlineStatus();
renderDateStrip();
loadAssignments();
</script>
</body>
</html>
