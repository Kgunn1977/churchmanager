<?php
require_once __DIR__ . '/auth.php';
requireLogin();
$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Church Facility Manager' ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?= $extraHead ?? '' ?>
</head>
<body class="bg-gray-100 min-h-screen"<?= isset($bodyAttr) ? ' '.$bodyAttr : '' ?>>

<nav class="bg-blue-800 text-white shadow-lg">
    <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">

        <!-- Logo -->
        <a href="/dashboard.php" class="flex items-center gap-2 hover:opacity-80 transition">
            <svg class="w-6 h-6 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            <span class="font-bold text-sm hidden sm:block">Church Facility Manager</span>
        </a>

        <!-- Nav Links -->
        <div class="hidden md:flex items-center gap-1 text-sm">
            <a href="/pages/facilities.php"
               class="px-3 py-1.5 rounded-lg transition <?= $currentPage === 'facilities.php' ? 'bg-blue-600 text-white' : 'text-blue-200 hover:text-white hover:bg-blue-700' ?>">
                Facilities
            </a>
            <a href="/pages/reservations.php"
               class="px-3 py-1.5 rounded-lg transition <?= $currentPage === 'reservations.php' ? 'bg-blue-600 text-white' : 'text-blue-200 hover:text-white hover:bg-blue-700' ?>">
                Reservations
            </a>
            <a href="#"
               class="px-3 py-1.5 rounded-lg text-blue-200 hover:text-white hover:bg-blue-700 transition">
                Cleaning
            </a>
            <a href="#"
               class="px-3 py-1.5 rounded-lg text-blue-200 hover:text-white hover:bg-blue-700 transition">
                Maintenance
            </a>
            <a href="/pages/equipment_catalog.php"
               class="px-3 py-1.5 rounded-lg transition <?= $currentPage === 'equipment_catalog.php' ? 'bg-blue-600 text-white' : 'text-blue-200 hover:text-white hover:bg-blue-700' ?>">
                Equipment
            </a>
            <a href="/pages/task_catalog.php"
               class="px-3 py-1.5 rounded-lg transition <?= $currentPage === 'task_catalog.php' ? 'bg-blue-600 text-white' : 'text-blue-200 hover:text-white hover:bg-blue-700' ?>">
                Tasks
            </a>
            <a href="/pages/materials_catalog.php"
               class="px-3 py-1.5 rounded-lg transition <?= $currentPage === 'materials_catalog.php' ? 'bg-blue-600 text-white' : 'text-blue-200 hover:text-white hover:bg-blue-700' ?>">
                Supplies
            </a>
            <?php if (isAdmin()): ?>
            <a href="/pages/users.php"
               class="px-3 py-1.5 rounded-lg transition <?= $currentPage === 'users.php' ? 'bg-blue-600 text-white' : 'text-blue-200 hover:text-white hover:bg-blue-700' ?>">
                Users
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
            <a href="/logout.php" class="text-blue-200 hover:text-white transition" title="Sign Out">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
            </a>
        </div>

    </div>
</nav>
