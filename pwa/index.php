<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

// Prevent browser & proxy caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// If not logged in, show inline login form with AJAX submit (no page navigation at all)
if (!isLoggedIn()) {
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#1e3a5f">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta http-equiv="Cache-Control" content="no-cache,no-store,must-revalidate">
    <title>Sign In — My Tasks</title>
    <link rel="manifest" href="<?= url('/pwa/manifest.php') ?>">
    <link rel="apple-touch-icon" href="<?= url('/pwa/icons/icon-192.svg') ?>">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #1e3a5f 0%, #2d6a9f 100%);
            min-height: 100vh; min-height: 100dvh;
            display: flex; align-items: center; justify-content: center;
            padding: 24px; padding-top: max(24px, env(safe-area-inset-top));
        }
        .login-wrap { width: 100%; max-width: 380px; }
        .logo { text-align: center; margin-bottom: 32px; }
        .logo-icon {
            display: inline-flex; align-items: center; justify-content: center;
            width: 64px; height: 64px; background: #fff; border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,.15); margin-bottom: 16px;
        }
        .logo h1 { color: #fff; font-size: 22px; font-weight: 700; }
        .logo p { color: rgba(255,255,255,.7); font-size: 14px; margin-top: 4px; }
        .card { background: #fff; border-radius: 20px; padding: 28px 24px; box-shadow: 0 8px 32px rgba(0,0,0,.12); }
        .error { display:none; background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; border-radius: 12px; padding: 10px 14px; font-size: 13px; margin-bottom: 16px; align-items: center; gap: 8px; }
        .error.visible { display: flex; }
        label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; }
        input[type=email], input[type=password] {
            width: 100%; padding: 12px 14px; border: 1px solid #d1d5db;
            border-radius: 12px; font-size: 15px; color: #111827;
            outline: none; transition: border-color .15s; font-family: inherit;
        }
        input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.15); }
        .field { margin-bottom: 16px; }
        .btn {
            width: 100%; padding: 14px; background: #2563eb; color: #fff;
            border: none; border-radius: 12px; font-size: 15px; font-weight: 600;
            cursor: pointer; transition: background .15s; font-family: inherit;
        }
        .btn:active { background: #1d4ed8; }
        .btn:disabled { opacity: .6; }
        .footer { text-align: center; color: rgba(255,255,255,.5); font-size: 12px; margin-top: 24px; }
    </style>
</head>
<body>
    <div class="login-wrap">
        <div class="logo">
            <div class="logo-icon">
                <svg width="32" height="32" fill="none" stroke="#1e40af" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
            <h1>My Tasks</h1>
            <p>Sign in to view your assignments</p>
        </div>
        <div class="card">
            <div class="error" id="loginError">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                <span id="loginErrorText"></span>
            </div>
            <form id="loginForm" onsubmit="return doLogin(event)" novalidate>
                <div class="field">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="you@yourchurch.org" required autocomplete="email">
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn" id="loginBtn">Sign In</button>
                <a href="<?= url('/forgot_password.php?pwa=1') ?>" style="display:block;text-align:center;margin-top:16px;color:#3b82f6;font-size:13px;font-weight:600;text-decoration:none;">Forgot your password?</a>
            </form>
        </div>
        <p class="footer">Church Facility Manager</p>
    </div>
    <script>
    const BASE_PATH = <?= json_encode(BASE_PATH) ?>;
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register(BASE_PATH + '/pwa/sw.js?base=' + encodeURIComponent(BASE_PATH));
    }
    async function doLogin(e) {
        e.preventDefault(); // No form submission — no page navigation
        const btn = document.getElementById('loginBtn');
        const errBox = document.getElementById('loginError');
        const errText = document.getElementById('loginErrorText');
        btn.disabled = true;
        btn.textContent = 'Signing in...';
        errBox.classList.remove('visible');
        try {
            const fd = new FormData(document.getElementById('loginForm'));
            const resp = await fetch(BASE_PATH + '/pwa/login_api.php', {
                method: 'POST',
                body: fd,
                credentials: 'same-origin'
            });
            const data = await resp.json();
            if (data.success) {
                // Session is now active — reload this same page as a fresh GET.
                // No navigation to a different URL, so Android PWA renders correctly.
                window.location.reload();
                return;
            }
            errText.textContent = data.error || 'Login failed.';
            errBox.classList.add('visible');
        } catch(ex) {
            errText.textContent = 'Connection error. Please try again.';
            errBox.classList.add('visible');
        }
        btn.disabled = false;
        btn.textContent = 'Sign In';
        return false;
    }
    </script>
</body>
</html>
    <?php
    exit;
}

// ── Authenticated from here on ──
$user = getCurrentUser();

// Load date strip settings
$_pwaDb = getDB();
$_pwaStripBack = 3;
$_pwaStripForward = 10;
$_pwaSchedMode = 'deadline';
try {
    $r = $_pwaDb->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('pwa_date_strip_back','pwa_date_strip_forward','scheduling_mode')");
    foreach ($r->fetchAll() as $row) {
        if ($row['setting_key'] === 'pwa_date_strip_back') $_pwaStripBack = max(0, (int)$row['setting_value']);
        if ($row['setting_key'] === 'pwa_date_strip_forward') $_pwaStripForward = max(1, (int)$row['setting_value']);
        if ($row['setting_key'] === 'scheduling_mode') $_pwaSchedMode = $row['setting_value'];
    }
} catch (Exception $e) {}

// If not running as installed PWA, redirect to install page (first visit only)
// The install page sets a cookie so we don't redirect in a loop
// Skip if loaded in preview iframe (?skipinstall=1)
if (!isset($_GET['skipinstall']) && !isset($_COOKIE['cfm_pwa_seen'])) {
    setcookie('cfm_pwa_seen', '1', time() + 86400 * 365, url('/pwa/'));
    header('Location: ' . url('/pwa/install.php'));
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
    <link rel="manifest" href="<?= url('/pwa/manifest.php') ?>">
    <link rel="apple-touch-icon" href="<?= url('/pwa/icons/icon-192.svg') ?>">
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

/* Date chip status colors */
.date-chip.status-complete:not(.selected) { background: #dcfce7; border-color: #86efac; }
.date-chip.status-complete:not(.selected) .day-num { color: #15803d; }
.date-chip.status-complete:not(.selected) .day-name { color: #22c55e; }
.date-chip.status-incomplete-past:not(.selected) { background: #fee2e2; border-color: #fca5a5; }
.date-chip.status-incomplete-past:not(.selected) .day-num { color: #b91c1c; }
.date-chip.status-incomplete-past:not(.selected) .day-name { color: #ef4444; }
.date-chip.status-future:not(.selected) { background: #fef9c3; border-color: #fde68a; }
.date-chip.status-future:not(.selected) .day-num { color: #a16207; }
.date-chip.status-future:not(.selected) .day-name { color: #eab308; }

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
.check-label { font-size: 14px; color: #374151; flex: 1; cursor: pointer; -webkit-tap-highlight-color: rgba(59,130,246,.15); }
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

/* ── Calendar overlay ─────────────────────────────────────── */
.cal-overlay {
    display: none; position: fixed; inset: 0; background: rgba(0,0,0,.4);
    z-index: 100; align-items: center; justify-content: center;
}
.cal-overlay.visible { display: flex; }
.cal-box {
    background: #fff; border-radius: 16px; padding: 20px; width: 320px;
    box-shadow: 0 20px 50px rgba(0,0,0,.25);
}
.cal-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 12px;
}
.cal-header button {
    background: none; border: 1px solid #e5e7eb; border-radius: 8px;
    width: 32px; height: 32px; cursor: pointer; color: #374151;
    display: flex; align-items: center; justify-content: center;
}
.cal-header span { font-size: 15px; font-weight: 700; color: #111827; }
.cal-grid {
    display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; text-align: center;
}
.cal-grid .cal-dow { font-size: 10px; font-weight: 700; color: #9ca3af; padding: 4px 0; }
.cal-grid .cal-day {
    font-size: 13px; padding: 8px 0; border-radius: 8px; cursor: pointer;
    font-weight: 600; color: #374151; transition: background .12s;
}
.cal-grid .cal-day:hover { background: #eff6ff; }
.cal-grid .cal-day.today { border: 2px solid #3b82f6; }
.cal-grid .cal-day.selected { background: #1e40af; color: #fff; }
.cal-grid .cal-day.outside { color: #d1d5db; }
.cal-close {
    display: block; width: 100%; margin-top: 12px; padding: 10px;
    background: none; border: 1px solid #e5e7eb; border-radius: 10px;
    font-size: 13px; font-weight: 600; color: #374151; cursor: pointer;
}

/* ── Task Detail Popup ───────────────────────────────────── */
.task-detail-overlay {
    display: none; position: fixed; inset: 0; z-index: 200;
    background: rgba(0,0,0,.45); justify-content: center; align-items: flex-end;
}
.task-detail-overlay.visible { display: flex; }
.task-detail-box {
    background: #fff; border-radius: 20px 20px 0 0; padding: 24px 20px 32px;
    width: 100%; max-width: 420px; max-height: 75vh; overflow-y: auto;
    animation: slideUp .25s ease-out;
}
@keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
.task-detail-box h3 {
    font-size: 17px; font-weight: 800; color: #111827; margin-bottom: 4px;
}
.task-detail-box .td-desc {
    font-size: 13px; color: #6b7280; margin-bottom: 12px; line-height: 1.5;
}
.task-detail-box .td-time {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 12px; font-weight: 600; color: #3b82f6; background: #eff6ff;
    padding: 4px 10px; border-radius: 8px; margin-bottom: 12px;
}
.task-detail-box .td-section {
    font-size: 11px; font-weight: 700; color: #9ca3af; text-transform: uppercase;
    letter-spacing: .05em; margin: 12px 0 6px;
}
.task-detail-box .td-list {
    list-style: none; padding: 0; margin: 0;
}
.task-detail-box .td-list li {
    font-size: 13px; color: #374151; padding: 5px 0;
    border-bottom: 1px solid #f3f4f6; display: flex; align-items: center; gap: 6px;
}
.task-detail-box .td-list li:last-child { border-bottom: none; }
.task-detail-close {
    display: block; width: 100%; margin-top: 16px; padding: 12px;
    background: none; border: 1px solid #e5e7eb; border-radius: 12px;
    font-size: 14px; font-weight: 600; color: #374151; cursor: pointer;
}

/* ── Toolbar (Sort / Show Hidden) ────────────────────────── */
.pwa-toolbar {
    display: flex; gap: 8px; padding: 8px 16px; background: #fff;
    border-bottom: 1px solid #e5e7eb; flex-shrink: 0;
}
.pwa-toolbar button {
    flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px;
    padding: 7px 0; border: 1px solid #e5e7eb; border-radius: 10px;
    background: #fff; font-size: 12px; font-weight: 600; color: #6b7280;
    cursor: pointer; transition: all .15s;
}
.pwa-toolbar button:active { background: #f3f4f6; }
.pwa-toolbar button.active { background: #eff6ff; border-color: #93c5fd; color: #1e40af; }

/* ── Hide animations ─────────────────────────────────────── */
.check-item.hiding {
    opacity: 0; max-height: 0; padding: 0; margin: 0; overflow: hidden;
    transition: opacity .25s, max-height .3s ease-out, padding .3s;
}
.subgroup.hiding, .task-card.hiding {
    opacity: 0; max-height: 0; margin: 0; overflow: hidden;
    transition: opacity .25s, max-height .3s ease-out, margin .3s;
}

/* ── Pull to refresh indicator ────────────────────────────── */
.refresh-hint {
    text-align: center; font-size: 11px; color: #9ca3af;
    padding: 8px 0; display: none;
}
    </style>
</head>
<body>
<script>
// Prevent browser scroll restoration after form POST (login) — keeps top bar in view
if ('scrollRestoration' in history) history.scrollRestoration = 'manual';
window.scrollTo(0, 0);
</script>
<div id="app">

    <!-- Top bar -->
    <div class="top-bar">
        <div>
            <div class="top-bar-title">My Tasks</div>
            <div class="top-bar-sub"><?= htmlspecialchars($user['name']) ?></div>
        </div>
        <div class="top-bar-right">
            <div class="sync-indicator" id="syncDot" title="Online"></div>
            <button class="logout-btn" onclick="doLogout()">Sign Out</button>
        </div>
    </div>

    <!-- Offline banner -->
    <div class="offline-banner" id="offlineBanner">You're offline — changes will sync when reconnected</div>

    <!-- Date strip (horizontal scrollable week) -->
    <div class="date-strip" id="dateStrip"></div>

    <!-- Toolbar -->
    <div class="pwa-toolbar">
        <button id="btnView" onclick="cycleView()">
            <svg id="viewIcon" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            <span id="viewLabel">View</span>
        </button>
        <button id="btnCollapse" onclick="toggleCollapseAll()">
            <svg id="collapseIcon" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            <span id="collapseLabel">Collapse</span>
        </button>
        <button id="btnShowHidden" onclick="toggleShowHidden()">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            <span id="btnShowHiddenLabel">Hide</span>
        </button>
    </div>

    <!-- Summary -->
    <div class="summary-row" id="summaryRow"></div>

    <!-- Calendar overlay -->
    <div class="cal-overlay" id="calOverlay" onclick="if(event.target===this)closeCal()">
        <div class="cal-box">
            <div class="cal-header">
                <button onclick="calNav(-1)"><svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></button>
                <span id="calTitle"></span>
                <button onclick="calNav(1)"><svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></button>
            </div>
            <div class="cal-grid" id="calGrid"></div>
            <button class="cal-close" onclick="closeCal()">Cancel</button>
        </div>
    </div>

    <!-- Task detail popup -->
    <div class="task-detail-overlay" id="taskDetailOverlay" onclick="if(event.target===this)closeTaskDetail()">
        <div class="task-detail-box" id="taskDetailBox"></div>
    </div>

    <!-- Task list -->
    <div class="task-scroll" id="taskScroll">
        <div id="taskList"></div>
        <div class="empty-state" id="emptyState" style="display:none;">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p>No tasks for this day <span style="color:#d1d5db;font-size:10px;">v9</span></p>
            <small>Select a different date or check with your supervisor.</small>
        </div>
    </div>

</div>

<script>
// ═══════════════════════════════════════════════════════════
// CONFIG
// ═══════════════════════════════════════════════════════════
const BASE_PATH = <?= json_encode(BASE_PATH) ?>;
const USER_ID = <?= (int)$user['id'] ?>;
const USER_NAME = <?= json_encode($user['name']) ?>;
let selectedDate = todayStr();
let assignments = [];
let isOnline = navigator.onLine;
let showHidden = false;
let viewMode = 'default'; // 'default' | 'rooms' | 'task' | 'resources'
let stripAnchor = null; // null = anchored to today, else a Date object
let dateStatuses = {}; // { 'YYYY-MM-DD': 'complete'|'incomplete'|'none' }
const STRIP_DAYS_BACK = <?= (int)$_pwaStripBack ?>;
const STRIP_DAYS_FORWARD = <?= (int)$_pwaStripForward ?>;
const STRIP_TOTAL = STRIP_DAYS_BACK + 1 + STRIP_DAYS_FORWARD;
const SCHED_MODE = <?= json_encode($_pwaSchedMode) ?>;
let taskDetailCache = {}; // task_id => checklist item with resources, description, etc.

// ═══════════════════════════════════════════════════════════
// LOGOUT — clear caches before redirecting
// ═══════════════════════════════════════════════════════════
async function doLogout() {
    try {
        // Clear all caches so stale pages can't bypass login
        const keys = await caches.keys();
        await Promise.all(keys.map(k => caches.delete(k)));
        // Clear localStorage task caches
        Object.keys(localStorage).forEach(k => {
            if (k.startsWith('cfm_')) localStorage.removeItem(k);
        });
    } catch(e) {}
    // Destroy session server-side, then reload index.php (which shows inline login form).
    // Staying on index.php avoids redirect-chain issues in Android PWA standalone mode.
    try {
        await fetch(BASE_PATH + '/pwa/logout_api.php', { method: 'POST', credentials: 'same-origin' });
    } catch(e) {}
    location.reload();
}

// ═══════════════════════════════════════════════════════════
// SERVICE WORKER REGISTRATION
// ═══════════════════════════════════════════════════════════
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register(BASE_PATH + '/pwa/sw.js?base=' + encodeURIComponent(BASE_PATH)).then(reg => {
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
// DATE STRIP — 2 weeks centered on anchor (today or selected future date)
// ═══════════════════════════════════════════════════════════
function renderDateStrip() {
    const strip = document.getElementById('dateStrip');
    const today = new Date(); today.setHours(0,0,0,0);
    const anchor = stripAnchor || today;
    const start = new Date(anchor);
    start.setDate(start.getDate() - STRIP_DAYS_BACK);

    // "more future" chip at end, "back to today" chip if on future anchor
    let html = '';

    // If anchored to a future date, show a "← Today" chip first
    if (stripAnchor && fmt(stripAnchor) !== fmt(today)) {
        html += `<div class="date-chip" onclick="jumpToToday()" style="background:#eff6ff;border-color:#93c5fd;min-width:60px;">
            <span class="day-name" style="color:#1e40af;">←</span>
            <span class="day-num" style="font-size:11px;color:#1e40af;">Today</span>
        </div>`;
    }

    const todayDs = todayStr();
    const rangeStart = fmt(start);
    const rangeEnd = fmt(new Date(start.getTime() + (STRIP_TOTAL - 1) * 86400000));

    for (let i = 0; i < STRIP_TOTAL; i++) {
        const d = new Date(start);
        d.setDate(d.getDate() + i);
        const ds = fmt(d);
        const isToday = ds === todayDs;
        const isSel = ds === selectedDate;
        let cls = 'date-chip';
        if (isSel) cls += ' selected';
        if (isToday && !isSel) cls += ' today';

        // Status coloring
        const st = dateStatuses[ds];
        if (st && !isSel) {
            const isPast = ds < todayDs;
            const isFuture = ds > todayDs;
            if (st === 'complete') cls += ' status-complete';
            else if (st === 'incomplete' && isPast) cls += ' status-incomplete-past';
            else if (st === 'incomplete' && isFuture) cls += ' status-future';
        }

        html += `<div class="${cls}" onclick="selectDate('${ds}')" data-date="${ds}">
            <span class="day-name">${DAY_NAMES[d.getDay()]}</span>
            <span class="day-num">${d.getDate()}</span>
        </div>`;
    }

    // Fetch date statuses for visible range
    fetchDateStatuses(rangeStart, rangeEnd);

    // "More ▸" chip at the end to open calendar
    html += `<div class="date-chip" onclick="openCal()" style="background:#eff6ff;border-color:#93c5fd;min-width:60px;">
        <span class="day-name" style="color:#1e40af;">More</span>
        <span class="day-num" style="font-size:14px;color:#1e40af;">📅</span>
    </div>`;

    strip.innerHTML = html;

    requestAnimationFrame(() => {
        const sel = strip.querySelector('.selected');
        if (sel) sel.scrollIntoView({ inline: 'center', behavior: 'smooth' });
    });
}

function fetchDateStatuses(startDate, endDate) {
    if (!navigator.onLine) return;
    fetch(BASE_PATH + `/api/tasks_api.php?action=get_date_statuses&start=${startDate}&end=${endDate}&user_id=${USER_ID}`)
        .then(r => r.json())
        .then(data => {
            let changed = false;
            for (const [date, status] of Object.entries(data)) {
                if (dateStatuses[date] !== status) {
                    dateStatuses[date] = status;
                    changed = true;
                }
            }
            if (changed) {
                // Apply colors to existing chips without full re-render
                const todayDs = todayStr();
                document.querySelectorAll('.date-chip[data-date]').forEach(chip => {
                    const ds = chip.getAttribute('data-date');
                    const st = dateStatuses[ds];
                    chip.classList.remove('status-complete', 'status-incomplete-past', 'status-future');
                    if (st && !chip.classList.contains('selected')) {
                        const isPast = ds < todayDs;
                        const isFuture = ds > todayDs;
                        if (st === 'complete') chip.classList.add('status-complete');
                        else if (st === 'incomplete' && isPast) chip.classList.add('status-incomplete-past');
                        else if (st === 'incomplete' && isFuture) chip.classList.add('status-future');
                    }
                });
            }
        })
        .catch(() => {});
}

function selectDate(ds) {
    selectedDate = ds;
    // If the selected date is far from today, set anchor
    const sel = parseDate(ds);
    const today = new Date(); today.setHours(0,0,0,0);
    const diffDays = Math.round((sel - today) / 86400000);
    if (diffDays > STRIP_DAYS_FORWARD) {
        stripAnchor = sel;
    }
    renderDateStrip();
    loadAssignments();
}

function jumpToToday() {
    stripAnchor = null;
    selectedDate = todayStr();
    renderDateStrip();
    loadAssignments();
}

// ═══════════════════════════════════════════════════════════
// CALENDAR OVERLAY
// ═══════════════════════════════════════════════════════════
let calMonth, calYear;
function openCal() {
    const d = parseDate(selectedDate);
    calMonth = d.getMonth();
    calYear = d.getFullYear();
    renderCal();
    document.getElementById('calOverlay').classList.add('visible');
}
function closeCal() { document.getElementById('calOverlay').classList.remove('visible'); }
function calNav(dir) { calMonth += dir; if (calMonth > 11) { calMonth = 0; calYear++; } if (calMonth < 0) { calMonth = 11; calYear--; } renderCal(); }

function renderCal() {
    const MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    document.getElementById('calTitle').textContent = MONTHS[calMonth] + ' ' + calYear;
    const grid = document.getElementById('calGrid');
    let html = ['Su','Mo','Tu','We','Th','Fr','Sa'].map(d => `<div class="cal-dow">${d}</div>`).join('');

    const firstDay = new Date(calYear, calMonth, 1).getDay();
    const daysInMonth = new Date(calYear, calMonth + 1, 0).getDate();
    const todayS = todayStr();

    // Blank cells before first day
    for (let i = 0; i < firstDay; i++) html += '<div></div>';

    for (let d = 1; d <= daysInMonth; d++) {
        const ds = calYear + '-' + String(calMonth+1).padStart(2,'0') + '-' + String(d).padStart(2,'0');
        let cls = 'cal-day';
        if (ds === todayS) cls += ' today';
        if (ds === selectedDate) cls += ' selected';
        html += `<div class="${cls}" onclick="calSelect('${ds}')">${d}</div>`;
    }
    grid.innerHTML = html;
}

function calSelect(ds) {
    selectedDate = ds;
    const sel = parseDate(ds);
    const today = new Date(); today.setHours(0,0,0,0);
    const diffDays = Math.round((sel - today) / 86400000);
    stripAnchor = diffDays > 10 ? sel : null;
    closeCal();
    renderDateStrip();
    loadAssignments();
}

// ═══════════════════════════════════════════════════════════
// TASK DETAIL POPUP
// ═══════════════════════════════════════════════════════════
function openTaskDetail(taskId) {
    const c = taskDetailCache[taskId];
    if (!c) return;
    const box = document.getElementById('taskDetailBox');
    const r = c.resources || {};

    let html = `<h3>${esc(c.task_name)}</h3>`;

    if (c.task_description) {
        html += `<div class="td-desc">${esc(c.task_description)}</div>`;
    }

    if (c.task_minutes && SCHED_MODE !== 'none') {
        html += `<div class="td-time">
            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            ${c.task_minutes} min
        </div>`;
    }

    const sections = [
        { key: 'supplies',  label: 'Supplies',  icon: '🧴' },
        { key: 'tools',     label: 'Tools',      icon: '🔧' },
        { key: 'materials', label: 'Materials',  icon: '📦' },
        { key: 'equipment', label: 'Equipment',  icon: '⚙️' },
    ];

    sections.forEach(s => {
        const items = r[s.key] || [];
        if (!items.length) return;
        html += `<div class="td-section">${s.icon} ${s.label}</div>`;
        html += `<ul class="td-list">${items.map(n => `<li>${esc(n)}</li>`).join('')}</ul>`;
    });

    html += `<button class="task-detail-close" onclick="closeTaskDetail()">Close</button>`;
    box.innerHTML = html;
    document.getElementById('taskDetailOverlay').classList.add('visible');
}

function closeTaskDetail() {
    document.getElementById('taskDetailOverlay').classList.remove('visible');
}

// ═══════════════════════════════════════════════════════════
// SORT & SHOW HIDDEN
// ═══════════════════════════════════════════════════════════
function cycleView() {
    const modes = ['default', 'rooms', 'task', 'resources'];
    const labels = ['View', 'Rooms', 'Tasks', 'Resources'];
    const icons = {
        default:   '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>',
        rooms:     '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0h4"/>',
        task:      '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>',
        resources: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>'
    };
    const idx = (modes.indexOf(viewMode) + 1) % modes.length;
    viewMode = modes[idx];
    document.getElementById('viewLabel').textContent = labels[idx];
    document.getElementById('viewIcon').innerHTML = icons[viewMode];
    document.getElementById('btnView').classList.toggle('active', viewMode !== 'default');
    renderList();
}

function getOpenState() {
    const openCards = new Set();
    const openSubs = new Set();
    document.querySelectorAll('.task-card.open').forEach(c => {
        const id = c.id || c.getAttribute('data-view-card');
        if (id) openCards.add(id);
    });
    document.querySelectorAll('.subgroup.open').forEach(sg => {
        const card = sg.closest('.task-card');
        const cardId = card ? (card.id || card.getAttribute('data-view-card')) : '';
        const sgName = sg.getAttribute('data-sg-name') || '';
        openSubs.add(cardId + '::' + sgName);
    });
    return { openCards, openSubs };
}

function restoreOpenState({ openCards, openSubs }) {
    openCards.forEach(id => {
        const el = document.getElementById(id) || document.querySelector(`[data-view-card="${id}"]`);
        if (el) el.classList.add('open');
    });
    openSubs.forEach(key => {
        const [cardId, sgName] = key.split('::');
        const card = document.getElementById(cardId) || document.querySelector(`[data-view-card="${cardId}"]`);
        if (card) {
            const sg = card.querySelector(`.subgroup[data-sg-name="${sgName}"]`);
            if (sg) sg.classList.add('open');
        }
    });
}

function toggleShowHidden() {
    const state = getOpenState();
    showHidden = !showHidden;
    document.getElementById('btnShowHidden').classList.toggle('active', showHidden);
    document.getElementById('btnShowHiddenLabel').textContent = showHidden ? 'Show' : 'Hide';
    renderList();
    restoreOpenState(state);
}

let allCollapsed = false;
function toggleCollapseAll() {
    allCollapsed = !allCollapsed;
    const btn = document.getElementById('btnCollapse');
    btn.classList.toggle('active', allCollapsed);
    document.getElementById('collapseLabel').textContent = allCollapsed ? 'Expand' : 'Collapse';
    // Update icon: chevron-up when collapsed (ready to expand), chevron-down when expanded (ready to collapse)
    document.getElementById('collapseIcon').innerHTML = allCollapsed
        ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>'
        : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>';
    if (allCollapsed) {
        document.querySelectorAll('.task-card.open').forEach(c => c.classList.remove('open'));
        document.querySelectorAll('.subgroup.open').forEach(s => s.classList.remove('open'));
    } else {
        document.querySelectorAll('.task-card').forEach(c => c.classList.add('open'));
        document.querySelectorAll('.subgroup').forEach(s => s.classList.add('open'));
    }
}

// ═══════════════════════════════════════════════════════════
// LOAD ASSIGNMENTS
// ═══════════════════════════════════════════════════════════
function loadAssignments() {
    const dot = document.getElementById('syncDot');
    if (isOnline) {
        dot.className = 'sync-indicator syncing';
    }

    fetch(BASE_PATH + `/api/tasks_api.php?action=get_janitor_assignments&date=${selectedDate}&user_id=${USER_ID}`)
        .then(r => {
            if (r.status === 401) { location.reload(); return Promise.reject('auth'); }
            return r.json();
        })
        .then(data => {
            if (!data) return;
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
    if (viewMode === 'rooms') return renderRoomsView();
    if (viewMode === 'task') return renderTaskView();
    if (viewMode === 'resources') return renderResourcesView();
    renderDefaultView();
}

function renderDefaultView() {
    const list = document.getElementById('taskList');
    const empty = document.getElementById('emptyState');
    list.innerHTML = '';

    // Sort: groups of groups first, then groups, then individual tasks
    const hierOrder = { group_of_groups: 0, group: 1, task: 2 };
    let sorted = [...assignments].sort((a, b) => {
        const ha = hierOrder[a.hierarchy_type] ?? 1;
        const hb = hierOrder[b.hierarchy_type] ?? 1;
        return ha - hb;
    });
    const hasVisible = sorted.some(a => showHidden || a.status !== 'completed');
    if (!sorted.length || !hasVisible) {
        showEmptyState(sorted.length > 0 && !hasVisible);
        return;
    }
    empty.style.display = 'none';

    sorted.forEach(a => {
        const checklist = a.checklist || [];
        const doneCount = checklist.filter(c => c.completed == 1).length;
        const isComplete = doneCount === checklist.length && checklist.length > 0;
        if (isComplete && !showHidden) return;

        const deadlineHtml = (SCHED_MODE !== 'none' && a.deadline) ? fmtDeadline(a.deadline) : '';
        const timeHtml = SCHED_MODE !== 'none' ? `<div class="time-badge">${a.estimated_minutes} min</div>` : '';
        let bodyHtml = buildSubGroupBody(a.id, checklist);

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
                    ${timeHtml}
                    <div class="done-count">${doneCount}/${checklist.length}</div>
                </div>
            </div>
            <div class="task-card-body">${bodyHtml}</div>
        `;
        list.appendChild(card);
    });
}

// ── Rooms view: group all tasks by room ──
function renderRoomsView() {
    const list = document.getElementById('taskList');
    const empty = document.getElementById('emptyState');
    list.innerHTML = '';

    // Build room → [{assignmentId, task_name, task_id, completed}]
    const rooms = {};
    assignments.forEach(a => {
        const key = a.room_name + ' · ' + a.building_name;
        if (!rooms[key]) rooms[key] = { room_name: a.room_name, building_name: a.building_name, items: [] };
        (a.checklist || []).forEach(c => {
            rooms[key].items.push({ ...c, assignment_id: a.id, group_name: a.group_name });
        });
    });

    const roomKeys = Object.keys(rooms).sort();
    const hasVisible = roomKeys.some(k => {
        const items = rooms[k].items;
        return showHidden || items.some(c => c.completed != 1);
    });
    if (!roomKeys.length || !hasVisible) {
        showEmptyState(roomKeys.length > 0 && !hasVisible);
        return;
    }
    empty.style.display = 'none';

    roomKeys.forEach(key => {
        const room = rooms[key];
        const items = room.items;
        const doneCount = items.filter(c => c.completed == 1).length;
        const isComplete = doneCount === items.length && items.length > 0;
        if (isComplete && !showHidden) return;

        const cardId = 'room-' + esc(key).replace(/[^a-zA-Z0-9]/g, '_');
        let bodyHtml = '';
        items.forEach(c => {
            if (!showHidden && c.completed == 1) return;
            bodyHtml += renderCheckItem(c.assignment_id, c, false);
        });

        const card = document.createElement('div');
        card.className = 'task-card' + (isComplete ? ' completed' : '');
        card.id = cardId;
        card.setAttribute('data-view-card', key);
        card.innerHTML = `
            <div class="task-card-hdr" onclick="this.parentElement.classList.toggle('open')">
                <svg class="expand-icon" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <div style="flex:1;min-width:0;">
                    <div class="group-title">${esc(room.room_name)}</div>
                    <div class="room-label">${esc(room.building_name)}</div>
                </div>
                <div style="text-align:right;">
                    <div class="done-count">${doneCount}/${items.length}</div>
                </div>
            </div>
            <div class="task-card-body">${bodyHtml}</div>
        `;
        list.appendChild(card);
    });
}

// ── Task view: group same tasks across rooms ──
function renderTaskView() {
    const list = document.getElementById('taskList');
    const empty = document.getElementById('emptyState');
    list.innerHTML = '';

    // Build taskName → [{assignment_id, room_name, building_name, task_id, completed}]
    const taskMap = {};
    assignments.forEach(a => {
        (a.checklist || []).forEach(c => {
            const tName = c.task_name;
            if (!taskMap[tName]) taskMap[tName] = [];
            taskMap[tName].push({ ...c, assignment_id: a.id, room_name: a.room_name, building_name: a.building_name });
        });
    });

    const taskNames = Object.keys(taskMap).sort();
    const hasVisible = taskNames.some(t => {
        const items = taskMap[t];
        return showHidden || items.some(c => c.completed != 1);
    });
    if (!taskNames.length || !hasVisible) {
        showEmptyState(taskNames.length > 0 && !hasVisible);
        return;
    }
    empty.style.display = 'none';

    taskNames.forEach(tName => {
        const items = taskMap[tName];
        const doneCount = items.filter(c => c.completed == 1).length;
        const isComplete = doneCount === items.length && items.length > 0;
        if (isComplete && !showHidden) return;

        const cardId = 'task-' + esc(tName).replace(/[^a-zA-Z0-9]/g, '_');
        let bodyHtml = '';
        items.forEach(c => {
            if (!showHidden && c.completed == 1) return;
            taskDetailCache[c.task_id] = c;
            // Show room name as the label instead of task name
            bodyHtml += `
                <div class="check-item${c.completed == 1 ? ' done' : ''}" data-task-id="${c.task_id}" data-assignment-id="${c.assignment_id}">
                    <input type="checkbox" ${c.completed == 1 ? 'checked' : ''}
                           onchange="toggleCheck(${c.assignment_id}, ${c.task_id}, this.checked)">
                    <span class="check-label" onclick="openTaskDetail(${c.task_id})">${esc(c.room_name)} · ${esc(c.building_name)}</span>
                </div>`;
        });

        const card = document.createElement('div');
        card.className = 'task-card' + (isComplete ? ' completed' : '');
        card.id = cardId;
        card.setAttribute('data-view-card', tName);
        card.innerHTML = `
            <div class="task-card-hdr" onclick="this.parentElement.classList.toggle('open')">
                <svg class="expand-icon" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <div style="flex:1;min-width:0;">
                    <div class="group-title">${esc(tName)}</div>
                </div>
                <div style="text-align:right;">
                    <div class="done-count">${doneCount}/${items.length}</div>
                </div>
            </div>
            <div class="task-card-body">${bodyHtml}</div>
        `;
        list.appendChild(card);
    });
}

// ── Resources view: deduplicated list of needed supplies, tools, materials, equipment ──
function renderResourcesView() {
    const list = document.getElementById('taskList');
    const empty = document.getElementById('emptyState');
    list.innerHTML = '';

    // Collect unique resources from all assignments' checklists
    const sets = { supplies: new Set(), tools: new Set(), materials: new Set(), equipment: new Set() };
    assignments.forEach(a => {
        (a.checklist || []).forEach(c => {
            const r = c.resources;
            if (!r) return;
            (r.supplies || []).forEach(n => sets.supplies.add(n));
            (r.tools || []).forEach(n => sets.tools.add(n));
            (r.materials || []).forEach(n => sets.materials.add(n));
            (r.equipment || []).forEach(n => sets.equipment.add(n));
        });
    });

    const categories = [
        { key: 'supplies',  label: 'Supplies',  icon: '🧴' },
        { key: 'tools',     label: 'Tools',      icon: '🔧' },
        { key: 'materials', label: 'Materials',  icon: '📦' },
        { key: 'equipment', label: 'Equipment',  icon: '⚙️' },
    ];

    const totalItems = categories.reduce((sum, cat) => sum + sets[cat.key].size, 0);
    if (!totalItems) {
        empty.style.display = '';
        empty.querySelector('p').textContent = 'No resources needed';
        empty.querySelector('small').textContent = 'No supplies, tools, materials, or equipment linked to today\'s tasks.';
        return;
    }
    empty.style.display = 'none';

    categories.forEach(cat => {
        const items = [...sets[cat.key]].sort();
        if (!items.length) return;

        const card = document.createElement('div');
        card.className = 'task-card open'; // start expanded
        card.innerHTML = `
            <div class="task-card-hdr" onclick="this.parentElement.classList.toggle('open')">
                <svg class="expand-icon" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <div style="flex:1;min-width:0;">
                    <div class="group-title">${cat.icon} ${cat.label}</div>
                </div>
                <div style="text-align:right;">
                    <div class="done-count">${items.length} item${items.length !== 1 ? 's' : ''}</div>
                </div>
            </div>
            <div class="task-card-body">
                ${items.map(name => `
                    <div class="resource-item" style="display:flex;align-items:center;gap:8px;padding:8px 0;border-bottom:1px solid #f3f4f6;">
                        <span style="font-size:14px;color:#374151;">${esc(name)}</span>
                    </div>
                `).join('')}
            </div>
        `;
        list.appendChild(card);
    });
}

// ── Shared helpers ──
function showEmptyState(allDone) {
    const empty = document.getElementById('emptyState');
    empty.style.display = '';
    if (allDone) {
        empty.querySelector('p').textContent = 'All tasks completed!';
        empty.querySelector('small').textContent = 'Tap "Show Hidden" to review.';
    } else {
        empty.querySelector('p').innerHTML = 'No tasks for this day <span style="color:#d1d5db;font-size:10px;">v9</span>';
        empty.querySelector('small').textContent = 'Select a different date or check with your supervisor.';
    }
}

function buildSubGroupBody(assignmentId, checklist) {
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
            const sgHidden = sgAllDone && !showHidden ? ' style="display:none;"' : '';
            const visibleItems = showHidden ? items : items.filter(c => c.completed != 1);
            const itemsHtml = (showHidden ? items : visibleItems).map(c => renderCheckItem(assignmentId, c, !showHidden && c.completed == 1)).join('');

            bodyHtml += `
                <div class="subgroup${sgAllDone ? ' all-done' : ''}" data-sg-name="${esc(sgName)}"${sgHidden}>
                    <div class="subgroup-hdr" onclick="toggleSubGroup(this.parentElement)">
                        <svg class="expand-icon" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        <span style="flex:1;">${esc(sgName)}</span>
                        <span style="font-size:11px;color:${sgAllDone ? '#22c55e' : '#9ca3af'};font-weight:600;">${sgDone}/${items.length}</span>
                    </div>
                    <div class="subgroup-body">${itemsHtml}</div>
                </div>`;
        });
    }

    const flatItems = ungrouped.length > 0 ? ungrouped : (sgNames.length === 0 ? checklist : []);
    flatItems.forEach(c => {
        if (!showHidden && c.completed == 1) return;
        bodyHtml += renderCheckItem(assignmentId, c, false);
    });

    return bodyHtml;
}

function renderCheckItem(assignmentId, c, hidden) {
    const hideStyle = hidden ? ' style="display:none;"' : '';
    // Store task detail for popup
    taskDetailCache[c.task_id] = c;
    return `
        <div class="check-item${c.completed == 1 ? ' done' : ''}" data-task-id="${c.task_id}" data-assignment-id="${assignmentId}"${hideStyle}>
            <input type="checkbox" ${c.completed == 1 ? 'checked' : ''}
                   onchange="toggleCheck(${assignmentId}, ${c.task_id}, this.checked)">
            <span class="check-label" onclick="openTaskDetail(${c.task_id})">${esc(c.task_name)}</span>
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
    // Optimistic UI update — find the item (may be in default, rooms, or task view card)
    let card, item;
    if (viewMode === 'default') {
        card = document.getElementById('assignment-' + assignmentId);
        item = card ? card.querySelector(`.check-item[data-task-id="${taskId}"]`) : null;
    } else {
        // In rooms/task views, find the item by both assignment-id and task-id
        item = document.querySelector(`.check-item[data-task-id="${taskId}"][data-assignment-id="${assignmentId}"]`)
            || document.querySelector(`.check-item[data-task-id="${taskId}"]`);
        card = item ? item.closest('.task-card') : null;
    }
    if (item) {
        item.querySelector('input[type=checkbox]').checked = checked;
        item.classList.toggle('done', checked);
    }
    updateCardCounts(card);
    updateSummary();

    // Auto-hide checked item and handle subgroup/card completion
    if (checked && item && !showHidden) {
        // Hide the checked item with animation
        item.style.maxHeight = item.offsetHeight + 'px';
        requestAnimationFrame(() => { item.classList.add('hiding'); });
        setTimeout(() => { item.style.display = 'none'; }, 300);

        const sg = item.closest('.subgroup');
        if (sg) {
            const sgAll = sg.querySelectorAll('.check-item');
            const sgDone = sg.querySelectorAll('.check-item.done');
            // Update subgroup header count
            const countSpan = sg.querySelector('.subgroup-hdr span:last-child');
            if (countSpan) {
                countSpan.textContent = `${sgDone.length}/${sgAll.length}`;
                countSpan.style.color = sgAll.length === sgDone.length ? '#22c55e' : '#9ca3af';
            }

            if (sgAll.length > 0 && sgAll.length === sgDone.length) {
                // All done in subgroup — hide it, open next
                setTimeout(() => {
                    sg.style.display = 'none';
                    let next = sg.nextElementSibling;
                    while (next && !next.classList.contains('subgroup')) next = next.nextElementSibling;
                    if (next && next.style.display !== 'none' && !next.classList.contains('open')) next.classList.add('open');
                }, 350);
            }
        }

        // If all items in card are done, hide the card
        setTimeout(() => {
            if (card) {
                const allItems = card.querySelectorAll('.check-item');
                const doneItems = card.querySelectorAll('.check-item.done');
                if (allItems.length > 0 && allItems.length === doneItems.length) {
                    card.classList.add('hiding');
                    setTimeout(() => { card.style.display = 'none'; }, 300);
                }
            }
        }, 400);
    } else if (checked && item) {
        // showHidden mode — just handle subgroup progression
        const sg = item.closest('.subgroup');
        if (sg) {
            const sgAll = sg.querySelectorAll('.check-item');
            const sgDone = sg.querySelectorAll('.check-item.done');
            const countSpan = sg.querySelector('.subgroup-hdr span:last-child');
            if (countSpan) {
                countSpan.textContent = `${sgDone.length}/${sgAll.length}`;
                countSpan.style.color = sgAll.length === sgDone.length ? '#22c55e' : '#9ca3af';
            }
            if (sgAll.length > 0 && sgAll.length === sgDone.length) {
                sg.classList.remove('open');
                let next = sg.nextElementSibling;
                while (next && !next.classList.contains('subgroup')) next = next.nextElementSibling;
                if (next && !next.classList.contains('open')) next.classList.add('open');
            }
        }
    }

    // If unchecking, re-render but preserve open state
    if (!checked) {
        setTimeout(() => {
            const state = getOpenState();
            renderList();
            restoreOpenState(state);
        }, 100);
    }

    // Update in-memory assignments array (so view switches reflect correct state)
    assignments.forEach(a => {
        if (a.id === assignmentId && a.checklist) {
            a.checklist.forEach(c => {
                if (c.task_id === taskId) c.completed = checked ? 1 : 0;
            });
            const doneAll = a.checklist.every(c => c.completed == 1);
            if (doneAll) a.status = 'completed';
            else a.status = a.checklist.some(c => c.completed == 1) ? 'in_progress' : 'pending';
        }
    });

    // Also update local cache
    updateLocalCache(assignmentId, taskId, checked);

    // Send to server (or queue for offline)
    const payload = {
        url: BASE_PATH + '/api/tasks_api.php',
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
                // Only mark completed in default view (rooms/task views don't use assignment cards)
                if (viewMode === 'default' && card) {
                    if (r.assignment_status === 'completed') {
                        // Delay so hide animation finishes first
                        setTimeout(() => card.classList.add('completed'), 500);
                    } else {
                        card.classList.remove('completed');
                    }
                }
                // Refresh date chip colors
                fetchDateStatuses(selectedDate, selectedDate);
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
// Freshness check: if this page was served from SW cache, verify session is still valid
// by making a lightweight fetch. If session expired, redirect to login.
if (navigator.onLine) {
    fetch(BASE_PATH + '/api/tasks_api.php?action=get_task_types', { credentials: 'same-origin' })
        .then(r => {
            if (r.status === 401 || r.redirected) {
                location.reload();
            }
        })
        .catch(() => {});
}

// Force SW update check on every page load
if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
    navigator.serviceWorker.ready.then(reg => reg.update());
}

updateOnlineStatus();
renderDateStrip();
loadAssignments();
</script>
</body>
</html>
