<?php
require_once __DIR__ . '/auth.php';
requireLogin();
$currentUser = getCurrentUser();

// Auto-generate task assignments on every page load (if enabled in Settings)
require_once __DIR__ . '/auto_generate.php';
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Church Facility Manager' ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>const BASE_PATH = <?= json_encode(BASE_PATH) ?>;</script>
    <?= $extraHead ?? '' ?>
</head>
<body class="bg-gray-100 min-h-screen"<?= isset($bodyAttr) ? ' '.$bodyAttr : '' ?>>

<nav class="bg-blue-800 text-white shadow-lg">
    <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">

        <!-- Logo -->
        <a href="<?= url('/pages/reservations.php') ?>" class="flex items-center gap-2 hover:opacity-80 transition">
            <svg class="w-6 h-6 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            <span class="font-bold text-sm hidden sm:block">Church Facility Manager</span>
        </a>

        <!-- Nav Links -->
        <div class="hidden md:flex items-center gap-1 text-sm">
            <a href="<?= url('/pages/reservations.php') ?>"
               class="px-3 py-1.5 rounded-lg transition <?= $currentPage === 'reservations.php' ? 'bg-blue-600 text-white' : 'text-blue-200 hover:text-white hover:bg-blue-700' ?>">
                Calendar
            </a>
            <a href="<?= url('/pages/pwa_preview.php') ?>"
               class="px-3 py-1.5 rounded-lg transition <?= in_array($currentPage, ['pwa_preview.php','janitor.php']) ? 'bg-blue-600 text-white' : 'text-blue-200 hover:text-white hover:bg-blue-700' ?>">
                PWA
            </a>

            <!-- Scheduling dropdown -->
            <div class="relative" data-dropdown>
                <button class="px-3 py-1.5 rounded-lg transition flex items-center gap-1 <?= in_array($currentPage, ['scheduling.php','task_log.php']) ? 'bg-blue-600 text-white' : 'text-blue-200 hover:text-white hover:bg-blue-700' ?>">
                    Scheduling
                    <svg class="w-3.5 h-3.5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div class="nav-dropdown absolute top-full left-0 mt-1 bg-white rounded-lg shadow-xl border border-gray-200 py-1 min-w-[160px] z-50 hidden">
                    <a href="<?= url('/pages/scheduling.php') ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition">Cleaning</a>
                    <a href="<?= url('/pages/task_log.php') ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition">Task Log</a>
                    <a href="#" class="block px-4 py-2 text-sm text-gray-400 cursor-default">Maintenance</a>
                </div>
            </div>

            <a href="<?= url('/pages/tasks.php') ?>"
               class="px-3 py-1.5 rounded-lg transition <?= $currentPage === 'tasks.php' ? 'bg-blue-600 text-white' : 'text-blue-200 hover:text-white hover:bg-blue-700' ?>">
                Tasks
            </a>

            <!-- Resources dropdown -->
            <div class="relative" data-dropdown>
                <button class="px-3 py-1.5 rounded-lg transition flex items-center gap-1 <?= in_array($currentPage, ['catalog.php','equipment_catalog.php']) ? 'bg-blue-600 text-white' : 'text-blue-200 hover:text-white hover:bg-blue-700' ?>">
                    Resources
                    <svg class="w-3.5 h-3.5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div class="nav-dropdown absolute top-full left-0 mt-1 bg-white rounded-lg shadow-xl border border-gray-200 py-1 min-w-[160px] z-50 hidden">
                    <a href="<?= url('/pages/equipment_catalog.php') ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition">Equipment</a>
                    <a href="<?= url('/pages/catalog.php?type=supplies') ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition">Supplies</a>
                    <a href="<?= url('/pages/catalog.php?type=tools') ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition">Tools</a>
                    <a href="<?= url('/pages/catalog.php?type=materials') ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition">Materials</a>
                </div>
            </div>

            <?php if (isAdmin()): ?>
            <a href="<?= url('/pages/settings.php') ?>"
               class="px-3 py-1.5 rounded-lg transition <?= in_array($currentPage, ['settings.php','users.php','app.php','facilities.php']) ? 'bg-blue-600 text-white' : 'text-blue-200 hover:text-white hover:bg-blue-700' ?>"
               title="Settings">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </a>
            <?php endif; ?>
        </div>

        <!-- User + Logout -->
        <div class="flex items-center gap-3">
            <span class="text-blue-200 text-sm hidden sm:block">
                <?= htmlspecialchars($currentUser['name']) ?>
                <span class="ml-1 bg-blue-600 text-blue-100 text-xs px-2 py-0.5 rounded-full capitalize">
                    <?= htmlspecialchars($currentUser['role']) ?>
                </span>
            </span>
            <a href="<?= url('/logout.php') ?>" class="text-blue-200 hover:text-white transition" title="Sign Out">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
            </a>
        </div>

    </div>
</nav>

<script>
function esc(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

// ── Dropdown menus ──
document.querySelectorAll('[data-dropdown]').forEach(wrap => {
    const btn = wrap.querySelector('button');
    const menu = wrap.querySelector('.nav-dropdown');
    btn.addEventListener('click', e => {
        e.stopPropagation();
        // Close other open dropdowns
        document.querySelectorAll('.nav-dropdown').forEach(m => { if (m !== menu) m.classList.add('hidden'); });
        menu.classList.toggle('hidden');
    });
});
document.addEventListener('click', () => {
    document.querySelectorAll('.nav-dropdown').forEach(m => m.classList.add('hidden'));
});
</script>
