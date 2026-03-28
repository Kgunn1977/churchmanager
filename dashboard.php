<?php
require_once 'includes/auth.php';
requireLogin();

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Church Facility Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">

    <!-- Top Navigation -->
    <nav class="bg-blue-800 text-white shadow-lg">
        <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <svg class="w-7 h-7 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <span class="font-bold text-lg">Church Facility Manager</span>
            </div>
            <div class="flex items-center gap-4">
                <span class="text-blue-200 text-sm hidden sm:block">
                    <?= htmlspecialchars($user['name']) ?>
                    <span class="ml-1 bg-blue-600 text-blue-100 text-xs px-2 py-0.5 rounded-full capitalize">
                        <?= htmlspecialchars($user['role']) ?>
                    </span>
                </span>
                <a href="/logout.php"
                   class="text-blue-200 hover:text-white text-sm flex items-center gap-1 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Sign Out
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-6xl mx-auto px-4 py-8">

        <!-- Welcome Banner -->
        <div class="bg-white rounded-2xl shadow-sm p-6 mb-8 border-l-4 border-blue-600">
            <h2 class="text-xl font-bold text-gray-800">
                Good <?= (date('H') < 12) ? 'morning' : ((date('H') < 17) ? 'afternoon' : 'evening') ?>,
                <?= htmlspecialchars(explode(' ', $user['name'])[0]) ?>!
            </h2>
            <p class="text-gray-500 text-sm mt-1">
                <?= date('l, F j, Y') ?> &mdash; Here's your facility overview.
            </p>
        </div>

        <!-- Module Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">

            <!-- Facilities -->
            <a href="/pages/facilities.php" class="group bg-white rounded-2xl shadow-sm p-6 hover:shadow-md transition border border-transparent hover:border-blue-200">
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center mb-4 group-hover:bg-blue-200 transition">
                    <svg class="w-6 h-6 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <h3 class="font-bold text-gray-800 mb-1">Facilities</h3>
                <p class="text-gray-500 text-sm">Manage buildings, floors, and rooms.</p>
            </a>

            <!-- Reservations -->
            <a href="/pages/reservations.php" class="group bg-white rounded-2xl shadow-sm p-6 hover:shadow-md transition border border-transparent hover:border-blue-200">
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center mb-4 group-hover:bg-blue-200 transition">
                    <svg class="w-6 h-6 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="font-bold text-gray-800 mb-1">Reservations</h3>
                <p class="text-gray-500 text-sm">View and manage room bookings across all buildings.</p>
            </a>

            <!-- Cleaning -->
            <a href="#" class="group bg-white rounded-2xl shadow-sm p-6 hover:shadow-md transition border border-transparent hover:border-green-200">
                <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center mb-4 group-hover:bg-green-200 transition">
                    <svg class="w-6 h-6 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>
                <h3 class="font-bold text-gray-800 mb-1">Cleaning</h3>
                <p class="text-gray-500 text-sm">Daily, weekly, and recurring custodial task schedules.</p>
                <span class="inline-block mt-3 text-xs text-green-400 font-medium">Coming soon</span>
            </a>

            <!-- Maintenance -->
            <a href="#" class="group bg-white rounded-2xl shadow-sm p-6 hover:shadow-md transition border border-transparent hover:border-orange-200">
                <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center mb-4 group-hover:bg-orange-200 transition">
                    <svg class="w-6 h-6 text-orange-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <h3 class="font-bold text-gray-800 mb-1">Maintenance</h3>
                <p class="text-gray-500 text-sm">Track repair requests and maintenance work orders.</p>
                <span class="inline-block mt-3 text-xs text-orange-400 font-medium">Coming soon</span>
            </a>

            <!-- Room Setup -->
            <a href="#" class="group bg-white rounded-2xl shadow-sm p-6 hover:shadow-md transition border border-transparent hover:border-purple-200">
                <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center mb-4 group-hover:bg-purple-200 transition">
                    <svg class="w-6 h-6 text-purple-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                    </svg>
                </div>
                <h3 class="font-bold text-gray-800 mb-1">Room Setup</h3>
                <p class="text-gray-500 text-sm">Manage default and event-specific room configurations.</p>
                <span class="inline-block mt-3 text-xs text-purple-400 font-medium">Coming soon</span>
            </a>

            <!-- Equipment -->
            <a href="#" class="group bg-white rounded-2xl shadow-sm p-6 hover:shadow-md transition border border-transparent hover:border-yellow-200">
                <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center mb-4 group-hover:bg-yellow-200 transition">
                    <svg class="w-6 h-6 text-yellow-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                    </svg>
                </div>
                <h3 class="font-bold text-gray-800 mb-1">Equipment</h3>
                <p class="text-gray-500 text-sm">Track inventory, borrowing, and equipment repairs.</p>
                <span class="inline-block mt-3 text-xs text-yellow-500 font-medium">Coming soon</span>
            </a>

            <!-- Users (admin only) -->
            <?php if (isAdmin()): ?>
            <a href="/pages/users.php" class="group bg-white rounded-2xl shadow-sm p-6 hover:shadow-md transition border border-transparent hover:border-gray-300">
                <div class="w-12 h-12 bg-gray-100 rounded-xl flex items-center justify-center mb-4 group-hover:bg-gray-200 transition">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <h3 class="font-bold text-gray-800 mb-1">Users</h3>
                <p class="text-gray-500 text-sm">Manage staff accounts and access roles.</p>
                <span class="inline-block mt-3 text-xs text-gray-400 font-medium">Coming soon</span>
            </a>
            <?php endif; ?>

        </div>

    </main>

</body>
</html>
