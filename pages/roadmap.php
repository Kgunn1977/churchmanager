<?php
$pageTitle = 'Roadmap — Church Facility Manager';
require_once __DIR__ . '/../includes/nav.php';
require_once __DIR__ . '/../config/database.php';
if (!isAdmin()) { header('Location: ' . url('/pages/reservations.php')); exit; }
?>

<div class="max-w-4xl mx-auto px-4 py-8">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">V1.0 Roadmap</h1>
            <p class="text-gray-500 text-sm mt-1">Development phases to reach beta release.</p>
        </div>
        <a href="<?= url('/Church_Facility_Manager_Roadmap_v1.0.docx') ?>" download class="border border-gray-300 hover:bg-gray-50 text-gray-600 rounded-lg px-4 py-2 text-sm transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Download .docx
        </a>
    </div>

    <!-- Phase 1 -->
    <div class="bg-white rounded-2xl shadow-sm p-6 border border-transparent hover:border-blue-200 mb-4">
        <div class="flex items-center gap-3 mb-4">
            <span class="bg-blue-100 text-blue-700 text-xs font-bold px-2.5 py-1 rounded-full">Phase 1</span>
            <h2 class="font-bold text-gray-800">Scheduled Cleaning Tasks</h2>
        </div>
        <div class="space-y-2 text-sm">
            <div class="flex items-center gap-2 text-gray-600"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> Facilities map</div>
            <div class="flex items-center gap-2 text-gray-600"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> Tasks and task lists</div>
            <div class="flex items-center gap-2 text-gray-600"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> Supplies catalog</div>
            <div class="flex items-center gap-2 text-gray-600"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> Tools catalog</div>
            <div class="flex items-center gap-2 text-gray-600"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> Users</div>
            <div class="flex items-center gap-2 text-gray-700 font-medium"><svg class="w-4 h-4 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> Cleaning Scheduler</div>
            <div class="flex items-center gap-2 text-gray-700 font-medium"><svg class="w-4 h-4 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> PWA app — daily task list, check-off, history, sorting, subtasks</div>
        </div>
    </div>

    <!-- Phase 2 -->
    <div class="bg-white rounded-2xl shadow-sm p-6 border border-transparent hover:border-gray-200 mb-4 opacity-75">
        <div class="flex items-center gap-3 mb-4">
            <span class="bg-gray-100 text-gray-500 text-xs font-bold px-2.5 py-1 rounded-full">Phase 2</span>
            <h2 class="font-bold text-gray-800">Scheduled Maintenance</h2>
        </div>
        <div class="space-y-2 text-sm text-gray-500">
            <div class="flex items-center gap-2"><span class="w-4 h-4 flex-shrink-0 inline-block"></span> Maintenance Scheduler</div>
            <div class="flex items-center gap-2"><span class="w-4 h-4 flex-shrink-0 inline-block"></span> Materials Catalog</div>
        </div>
    </div>

    <!-- Phase 3 -->
    <div class="bg-white rounded-2xl shadow-sm p-6 border border-transparent hover:border-gray-200 mb-4 opacity-75">
        <div class="flex items-center gap-3 mb-4">
            <span class="bg-gray-100 text-gray-500 text-xs font-bold px-2.5 py-1 rounded-full">Phase 3</span>
            <h2 class="font-bold text-gray-800">Reservations Enhancements</h2>
        </div>
        <div class="space-y-2 text-sm">
            <div class="flex items-center gap-2 text-gray-600"><svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> Core reservations system</div>
            <div class="flex items-center gap-2 text-gray-500"><span class="w-4 h-4 flex-shrink-0 inline-block"></span> Task integration with reservations</div>
        </div>
    </div>

    <!-- Phase 4 -->
    <div class="bg-white rounded-2xl shadow-sm p-6 border border-transparent hover:border-gray-200 mb-4 opacity-75">
        <div class="flex items-center gap-3 mb-4">
            <span class="bg-gray-100 text-gray-500 text-xs font-bold px-2.5 py-1 rounded-full">Phase 4</span>
            <h2 class="font-bold text-gray-800">Automatic Task Scheduling</h2>
        </div>
        <p class="text-sm text-gray-500">AI/rules-based scheduling from the design spec.</p>
    </div>

    <!-- Phase 5 -->
    <div class="bg-white rounded-2xl shadow-sm p-6 border border-transparent hover:border-gray-200 mb-4 opacity-75">
        <div class="flex items-center gap-3 mb-4">
            <span class="bg-gray-100 text-gray-500 text-xs font-bold px-2.5 py-1 rounded-full">Phase 5</span>
            <h2 class="font-bold text-gray-800">Equipment Management</h2>
        </div>
        <p class="text-sm text-gray-500">Inventory, borrowing, and repairs.</p>
    </div>

    <!-- Phase 6 -->
    <div class="bg-white rounded-2xl shadow-sm p-6 border border-transparent hover:border-gray-200 mb-4 opacity-75">
        <div class="flex items-center gap-3 mb-4">
            <span class="bg-gray-100 text-gray-500 text-xs font-bold px-2.5 py-1 rounded-full">Phase 6</span>
            <h2 class="font-bold text-gray-800">Integrations</h2>
        </div>
        <div class="space-y-2 text-sm text-gray-500">
            <div class="flex items-center gap-2"><span class="w-4 h-4 flex-shrink-0 inline-block"></span> HVAC Controls</div>
            <div class="flex items-center gap-2"><span class="w-4 h-4 flex-shrink-0 inline-block"></span> Door Controls</div>
        </div>
    </div>

</div>

</body>
</html>
