<?php
$pageTitle = 'Migration: Scheduling Tables — Church Facility Manager';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if (!isAdmin()) { die('Admin access required.'); }
require_once __DIR__ . '/../config/database.php';
$db = getDB();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $pageTitle ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
<div class="bg-white rounded-2xl shadow-sm p-8 max-w-2xl w-full">
    <h1 class="text-2xl font-bold text-gray-800 mb-2">Migration: Scheduling & Settings Tables</h1>
    <p class="text-gray-500 mb-6">This will create the cleaning schedule system and settings tables.</p>

<?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'yes'):

    $statements = [
        "CREATE TABLE IF NOT EXISTS cleaning_schedules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            frequency ENUM('daily','weekdays','specific_days','weekly','biweekly','monthly','yearly') NOT NULL DEFAULT 'weekly',
            frequency_config JSON DEFAULT NULL COMMENT 'e.g. {\"days\":[1,3,5]} for specific weekdays, {\"day_of_month\":15}, {\"month\":3,\"day\":1}',
            assign_to_type ENUM('user','role') NOT NULL DEFAULT 'user',
            assign_to_user_id INT DEFAULT NULL,
            assign_to_role VARCHAR(50) DEFAULT NULL COMMENT 'e.g. custodial',
            deadline_time TIME DEFAULT NULL COMMENT 'optional deadline time of day',
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_by INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (assign_to_user_id) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS cleaning_schedule_rooms (
            id INT AUTO_INCREMENT PRIMARY KEY,
            schedule_id INT NOT NULL,
            room_id INT NOT NULL,
            UNIQUE KEY uq_schedule_room (schedule_id, room_id),
            FOREIGN KEY (schedule_id) REFERENCES cleaning_schedules(id) ON DELETE CASCADE,
            FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS cleaning_schedule_task_groups (
            id INT AUTO_INCREMENT PRIMARY KEY,
            schedule_id INT NOT NULL,
            task_group_id INT NOT NULL,
            UNIQUE KEY uq_schedule_tg (schedule_id, task_group_id),
            FOREIGN KEY (schedule_id) REFERENCES cleaning_schedules(id) ON DELETE CASCADE,
            FOREIGN KEY (task_group_id) REFERENCES task_groups(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "ALTER TABLE janitor_task_assignments ADD COLUMN IF NOT EXISTS schedule_id INT DEFAULT NULL",
        "ALTER TABLE janitor_task_assignments ADD CONSTRAINT fk_assignment_schedule FOREIGN KEY (schedule_id) REFERENCES cleaning_schedules(id) ON DELETE SET NULL",

        "CREATE TABLE IF NOT EXISTS settings (
            setting_key VARCHAR(100) PRIMARY KEY,
            setting_value TEXT NOT NULL,
            description VARCHAR(255) DEFAULT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "INSERT IGNORE INTO settings (setting_key, setting_value, description) VALUES
            ('schedule_generation_days', '14', 'How many days ahead to generate assignments'),
            ('scheduling_mode', 'deadline', 'deadline or timeslot'),
            ('default_deadline_time', '08:00', 'Default deadline time for scheduled tasks')",
    ];

    echo '<div class="space-y-2">';
    foreach ($statements as $sql) {
        try {
            $db->exec($sql);
            $short = htmlspecialchars(substr(trim($sql), 0, 80)) . '…';
            echo "<div class='flex items-center gap-2 text-sm'><span class='text-green-600 font-bold'>✓</span> <span class='text-gray-600'>{$short}</span></div>";
        } catch (PDOException $e) {
            $msg = htmlspecialchars($e->getMessage());
            $short = htmlspecialchars(substr(trim($sql), 0, 80)) . '…';
            // Skip "already exists" type errors
            if (str_contains($msg, 'Duplicate') || str_contains($msg, 'already exists')) {
                echo "<div class='flex items-center gap-2 text-sm'><span class='text-yellow-500 font-bold'>⚠</span> <span class='text-gray-500'>{$short} — already exists, skipped</span></div>";
            } else {
                echo "<div class='flex items-center gap-2 text-sm'><span class='text-red-600 font-bold'>✗</span> <span class='text-red-700'>{$short}</span><br><span class='text-red-500 text-xs'>{$msg}</span></div>";
            }
        }
    }
    echo '</div>';
    echo '<div class="mt-6 flex gap-3">';
    echo '<a href="/pages/scheduling.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg px-4 py-2 text-sm transition">Go to Scheduling</a>';
    echo '<a href="/pages/settings.php" class="border border-gray-300 hover:bg-gray-50 text-gray-600 rounded-lg px-4 py-2 text-sm transition">Go to Settings</a>';
    echo '</div>';

else: ?>
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <h3 class="font-bold text-blue-800 text-sm mb-2">This migration will create:</h3>
        <ul class="text-sm text-blue-700 space-y-1 ml-4 list-disc">
            <li><strong>cleaning_schedules</strong> — recurring schedule rules (frequency, assignment, deadline)</li>
            <li><strong>cleaning_schedule_rooms</strong> — rooms assigned to each schedule</li>
            <li><strong>cleaning_schedule_task_groups</strong> — task groups assigned to each schedule</li>
            <li><strong>settings</strong> — system-wide key/value settings</li>
            <li>Add <strong>schedule_id</strong> column to janitor_task_assignments</li>
            <li>Seed default settings (14-day generation window, deadline mode)</li>
        </ul>
    </div>
    <form method="POST">
        <input type="hidden" name="confirm" value="yes">
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg px-6 py-2 text-sm transition">
            Run Migration
        </button>
    </form>
<?php endif; ?>
</div>
</body>
</html>
