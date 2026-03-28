<?php
require_once 'includes/auth.php';
requireLogin();

// Admin only
if (!isAdmin()) {
    die('<p style="font-family:sans-serif;padding:2rem;color:#dc2626;">Access denied — admins only.</p>');
}

require_once 'config/database.php';

// ── SQL statements to run ────────────────────────────────────────────────────
$migrations = [

    'organizations' => "CREATE TABLE IF NOT EXISTS organizations (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        name       VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_name (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'reservations' => "CREATE TABLE IF NOT EXISTS reservations (
        id                    INT AUTO_INCREMENT PRIMARY KEY,
        title                 VARCHAR(255)  NULL,
        organization_id       INT           NULL,
        start_datetime        DATETIME      NOT NULL,
        end_datetime          DATETIME      NOT NULL,
        notes                 TEXT          NULL,
        is_recurring          TINYINT(1)    NOT NULL DEFAULT 0,
        recurrence_rule       VARCHAR(50)   NULL COMMENT 'weekly | biweekly | monthly | daily',
        recurrence_end_date   DATE          NULL,
        parent_reservation_id INT           NULL,
        created_by            INT           NULL,
        created_at            TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
        updated_at            TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_start (start_datetime),
        INDEX idx_org   (organization_id),
        FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'reservation_rooms' => "CREATE TABLE IF NOT EXISTS reservation_rooms (
        reservation_id INT NOT NULL,
        room_id        INT NOT NULL,
        PRIMARY KEY (reservation_id, room_id),
        FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
        FOREIGN KEY (room_id)        REFERENCES rooms(id)         ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

];

$results = [];
$ran     = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'yes') {
    $ran = true;
    $db  = getDB();
    foreach ($migrations as $table => $sql) {
        try {
            $db->exec($sql);
            $results[$table] = ['ok' => true, 'msg' => 'Created (or already exists)'];
        } catch (PDOException $e) {
            $results[$table] = ['ok' => false, 'msg' => $e->getMessage()];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Run Migrations — Church Facility Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-6">

<div class="bg-white rounded-2xl shadow-md w-full max-w-lg p-8">

    <div class="flex items-center gap-3 mb-6">
        <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
            <svg class="w-5 h-5 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 7v10c0 2 1 3 3 3h10c2 0 3-1 3-3V7M4 7c0-2 1-3 3-3h10c2 0 3 1 3 3M4 7h16"/>
            </svg>
        </div>
        <div>
            <h1 class="text-lg font-bold text-gray-800">Database Migration</h1>
            <p class="text-sm text-gray-500">Reservations module — one-time setup</p>
        </div>
    </div>

    <?php if (!$ran): ?>

        <!-- Pre-run: show what will be created -->
        <p class="text-sm text-gray-600 mb-4">
            This will create the following tables in the <strong>facilitymanager</strong> database.
            It is safe to run more than once — tables are only created if they don't already exist.
        </p>

        <ul class="space-y-2 mb-6">
            <?php foreach (array_keys($migrations) as $table): ?>
            <li class="flex items-center gap-2 text-sm text-gray-700 bg-gray-50 rounded-lg px-4 py-2">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 10h18M3 6h18M3 14h18M3 18h18"/>
                </svg>
                <code class="font-mono font-semibold"><?= htmlspecialchars($table) ?></code>
            </li>
            <?php endforeach; ?>
        </ul>

        <form method="POST">
            <input type="hidden" name="confirm" value="yes">
            <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl transition text-sm">
                Run Migration
            </button>
        </form>

        <p class="text-center mt-4">
            <a href="/dashboard.php" class="text-sm text-gray-400 hover:text-gray-600 transition">← Back to dashboard</a>
        </p>

    <?php else: ?>

        <!-- Post-run: show results -->
        <?php $allOk = array_reduce($results, fn($c, $r) => $c && $r['ok'], true); ?>

        <div class="mb-5 rounded-xl px-4 py-3 text-sm font-semibold
            <?= $allOk ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200' ?>">
            <?= $allOk ? '✓ All migrations completed successfully.' : '✗ One or more migrations failed.' ?>
        </div>

        <ul class="space-y-2 mb-6">
            <?php foreach ($results as $table => $r): ?>
            <li class="flex items-start gap-3 rounded-lg px-4 py-3
                <?= $r['ok'] ? 'bg-green-50' : 'bg-red-50' ?>">
                <span class="mt-0.5 text-base leading-none"><?= $r['ok'] ? '✓' : '✗' ?></span>
                <div>
                    <p class="text-sm font-bold <?= $r['ok'] ? 'text-green-800' : 'text-red-800' ?>">
                        <?= htmlspecialchars($table) ?>
                    </p>
                    <p class="text-xs <?= $r['ok'] ? 'text-green-600' : 'text-red-600' ?> mt-0.5">
                        <?= htmlspecialchars($r['msg']) ?>
                    </p>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>

        <?php if ($allOk): ?>
        <a href="/pages/reservations.php"
           class="block w-full text-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl transition text-sm">
            Go to Reservations →
        </a>
        <?php else: ?>
        <form method="POST">
            <input type="hidden" name="confirm" value="yes">
            <button type="submit"
                    class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 rounded-xl transition text-sm">
                Retry Migration
            </button>
        </form>
        <?php endif; ?>

        <p class="text-center mt-4">
            <a href="/dashboard.php" class="text-sm text-gray-400 hover:text-gray-600 transition">← Back to dashboard</a>
        </p>

    <?php endif; ?>

</div>

</body>
</html>
