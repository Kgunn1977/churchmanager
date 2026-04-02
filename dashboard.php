<?php
$pageTitle = 'Dashboard — Church Facility Manager';
require_once __DIR__ . '/includes/nav.php';
require_once __DIR__ . '/config/database.php';
$db = getDB();
?>

    <!-- Main Content -->
    <main class="max-w-6xl mx-auto px-4 py-8">

        <!-- Welcome Banner -->
        <div class="bg-white rounded-2xl shadow-sm p-6 mb-8 border-l-4 border-blue-600">
            <h2 class="text-xl font-bold text-gray-800">
                Good <?= (date('H') < 12) ? 'morning' : ((date('H') < 17) ? 'afternoon' : 'evening') ?>,
                <?= htmlspecialchars(explode(' ', $currentUser['name'])[0]) ?>!
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

            <!-- Tasks -->
            <a href="/pages/tasks.php" class="group bg-white rounded-2xl shadow-sm p-6 hover:shadow-md transition border border-transparent hover:border-red-200">
                <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center mb-4 group-hover:bg-red-200 transition">
                    <svg class="w-6 h-6 text-red-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                </div>
                <h3 class="font-bold text-gray-800 mb-1">Tasks</h3>
                <p class="text-gray-500 text-sm">Manage work orders and task assignments.</p>
            </a>

            <!-- Janitor -->
            <a href="/pages/janitor.php" class="group bg-white rounded-2xl shadow-sm p-6 hover:shadow-md transition border border-transparent hover:border-indigo-200">
                <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center mb-4 group-hover:bg-indigo-200 transition">
                    <svg class="w-6 h-6 text-indigo-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>
                <h3 class="font-bold text-gray-800 mb-1">Janitor</h3>
                <p class="text-gray-500 text-sm">Schedule cleaning and custodial tasks.</p>
            </a>

            <!-- Scheduling -->
            <a href="/pages/scheduling.php" class="group bg-white rounded-2xl shadow-sm p-6 hover:shadow-md transition border border-transparent hover:border-green-200">
                <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center mb-4 group-hover:bg-green-200 transition">
                    <svg class="w-6 h-6 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="font-bold text-gray-800 mb-1">Scheduling</h3>
                <p class="text-gray-500 text-sm">Set up recurring cleaning schedules and generate assignments.</p>
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
            <a href="/pages/equipment_catalog.php" class="group bg-white rounded-2xl shadow-sm p-6 hover:shadow-md transition border border-transparent hover:border-yellow-200">
                <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center mb-4 group-hover:bg-yellow-200 transition">
                    <svg class="w-6 h-6 text-yellow-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                    </svg>
                </div>
                <h3 class="font-bold text-gray-800 mb-1">Equipment</h3>
                <p class="text-gray-500 text-sm">Track inventory, borrowing, and equipment repairs.</p>
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
            </a>
            <?php endif; ?>

        </div>

    </main>

</body>
</html>
