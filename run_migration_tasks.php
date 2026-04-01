<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/config/database.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'yes') {
    $results = [];
    $statements = [
        // Task types (admin-manageable)
        "CREATE TABLE IF NOT EXISTS task_types (
            id             INT AUTO_INCREMENT PRIMARY KEY,
            name           VARCHAR(100) NOT NULL,
            priority_order INT NOT NULL DEFAULT 0,
            created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // Seed default task types
        "INSERT IGNORE INTO task_types (name, priority_order) VALUES
            ('Janitorial', 1),
            ('Grounds Keeping', 2),
            ('Maintenance', 3)",

        // Resource libraries
        "CREATE TABLE IF NOT EXISTS tools (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            name       VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS supplies (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            name       VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS materials (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            name       VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS equipment (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            name       VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // Tasks
        "CREATE TABLE IF NOT EXISTS tasks (
            id                INT AUTO_INCREMENT PRIMARY KEY,
            name              VARCHAR(255) NOT NULL,
            task_type_id      INT NOT NULL,
            estimated_minutes INT NOT NULL DEFAULT 5,
            created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (task_type_id) REFERENCES task_types(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // Task ↔ Resource many-to-many
        "CREATE TABLE IF NOT EXISTS task_tools (
            task_id INT NOT NULL,
            tool_id INT NOT NULL,
            PRIMARY KEY (task_id, tool_id),
            FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
            FOREIGN KEY (tool_id) REFERENCES tools(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS task_supplies (
            task_id   INT NOT NULL,
            supply_id INT NOT NULL,
            PRIMARY KEY (task_id, supply_id),
            FOREIGN KEY (task_id)   REFERENCES tasks(id)    ON DELETE CASCADE,
            FOREIGN KEY (supply_id) REFERENCES supplies(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS task_materials (
            task_id     INT NOT NULL,
            material_id INT NOT NULL,
            PRIMARY KEY (task_id, material_id),
            FOREIGN KEY (task_id)     REFERENCES tasks(id)     ON DELETE CASCADE,
            FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS task_equipment (
            task_id      INT NOT NULL,
            equipment_id INT NOT NULL,
            PRIMARY KEY (task_id, equipment_id),
            FOREIGN KEY (task_id)      REFERENCES tasks(id)     ON DELETE CASCADE,
            FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // Task groups
        "CREATE TABLE IF NOT EXISTS task_groups (
            id                INT AUTO_INCREMENT PRIMARY KEY,
            name              VARCHAR(255) NOT NULL,
            task_type_id      INT NOT NULL,
            estimated_minutes INT NOT NULL DEFAULT 15,
            created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (task_type_id) REFERENCES task_types(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // Task group ↔ Tasks many-to-many
        "CREATE TABLE IF NOT EXISTS task_group_tasks (
            task_group_id INT NOT NULL,
            task_id       INT NOT NULL,
            sort_order    INT NOT NULL DEFAULT 0,
            PRIMARY KEY (task_group_id, task_id),
            FOREIGN KEY (task_group_id) REFERENCES task_groups(id) ON DELETE CASCADE,
            FOREIGN KEY (task_id)       REFERENCES tasks(id)       ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // Room default task groups
        "CREATE TABLE IF NOT EXISTS room_default_task_groups (
            room_id       INT NOT NULL,
            task_group_id INT NOT NULL,
            PRIMARY KEY (room_id, task_group_id),
            FOREIGN KEY (room_id)       REFERENCES rooms(id)       ON DELETE CASCADE,
            FOREIGN KEY (task_group_id) REFERENCES task_groups(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // Reservation ↔ Task groups
        "CREATE TABLE IF NOT EXISTS reservation_task_groups (
            reservation_id INT NOT NULL,
            task_group_id  INT NOT NULL,
            PRIMARY KEY (reservation_id, task_group_id),
            FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
            FOREIGN KEY (task_group_id)  REFERENCES task_groups(id)  ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // Add cleanup_mode column to reservations
        "ALTER TABLE reservations ADD COLUMN IF NOT EXISTS cleanup_mode ENUM('no','auto','custom') NOT NULL DEFAULT 'auto'",

        // Janitor task assignments (for tracking completion)
        "CREATE TABLE IF NOT EXISTS janitor_task_assignments (
            id              INT AUTO_INCREMENT PRIMARY KEY,
            task_group_id   INT NOT NULL,
            room_id         INT NOT NULL,
            assigned_date   DATE NOT NULL,
            deadline        DATETIME NULL,
            reservation_id  INT NULL COMMENT 'if auto-triggered from a reservation',
            assigned_to     INT NULL COMMENT 'user id of janitor',
            status          ENUM('pending','in_progress','completed') NOT NULL DEFAULT 'pending',
            completed_at    DATETIME NULL,
            created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_date (assigned_date),
            INDEX idx_assignee (assigned_to, assigned_date),
            FOREIGN KEY (task_group_id)  REFERENCES task_groups(id)  ON DELETE CASCADE,
            FOREIGN KEY (room_id)        REFERENCES rooms(id)       ON DELETE CASCADE,
            FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE SET NULL,
            FOREIGN KEY (assigned_to)    REFERENCES users(id)       ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // Track individual task completion within an assignment
        "CREATE TABLE IF NOT EXISTS janitor_task_checklist (
            assignment_id INT NOT NULL,
            task_id       INT NOT NULL,
            completed     TINYINT(1) NOT NULL DEFAULT 0,
            completed_at  DATETIME NULL,
            PRIMARY KEY (assignment_id, task_id),
            FOREIGN KEY (assignment_id) REFERENCES janitor_task_assignments(id) ON DELETE CASCADE,
            FOREIGN KEY (task_id)       REFERENCES tasks(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];

    foreach ($statements as $sql) {
        try {
            $db->exec($sql);
            $results[] = ['sql' => substr($sql, 0, 80) . '...', 'ok' => true];
        } catch (PDOException $e) {
            $results[] = ['sql' => substr($sql, 0, 80) . '...', 'ok' => false, 'err' => $e->getMessage()];
        }
    }

    echo '<!DOCTYPE html><html><head><title>Migration Result</title>'
       . '<script src="https://cdn.tailwindcss.com"></script></head>'
       . '<body class="bg-gray-50 flex items-center justify-center min-h-screen">'
       . '<div class="bg-white rounded-2xl shadow p-8 max-w-lg w-full">'
       . '<h1 class="text-xl font-bold mb-4">Migration Complete</h1>';
    foreach ($results as $r) {
        $icon = $r['ok'] ? '✅' : '❌';
        echo "<p class='mb-2'>{$icon} <code class='text-sm'>" . htmlspecialchars($r['sql']) . "</code></p>";
        if (!$r['ok']) echo "<p class='text-red-600 text-sm ml-6'>" . htmlspecialchars($r['err']) . "</p>";
    }
    echo '<a href="/pages/tasks.php" class="inline-block mt-4 bg-blue-600 text-white rounded-lg px-4 py-2 font-bold text-sm hover:bg-blue-700">Go to Tasks</a>';
    echo '</div></body></html>';
    exit;
}
?>
<!DOCTYPE html>
<html>
<head><title>Migration — Task System</title>
<script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen">
<div class="bg-white rounded-2xl shadow p-8 max-w-lg w-full">
    <h1 class="text-xl font-bold mb-2">Task System Tables</h1>
    <p class="text-gray-600 text-sm mb-4">Creates all tables for the task/janitorial system.</p>
    <ul class="text-sm text-gray-700 mb-6 list-disc pl-5 space-y-1">
        <li><strong>task_types</strong> — Janitorial, Grounds Keeping, Maintenance (admin-manageable)</li>
        <li><strong>tools, supplies, materials, equipment</strong> — four resource libraries</li>
        <li><strong>tasks</strong> — individual task definitions with type and estimated time</li>
        <li><strong>task_tools/supplies/materials/equipment</strong> — resource links per task</li>
        <li><strong>task_groups</strong> — named bundles of tasks with override time estimate</li>
        <li><strong>task_group_tasks</strong> — tasks within each group</li>
        <li><strong>room_default_task_groups</strong> — default cleanup groups per room</li>
        <li><strong>reservation_task_groups</strong> — cleanup groups attached to reservations</li>
        <li><strong>cleanup_mode</strong> — new column on reservations (no/auto/custom)</li>
        <li><strong>janitor_task_assignments</strong> — scheduled task instances with status tracking</li>
        <li><strong>janitor_task_checklist</strong> — per-task completion within an assignment</li>
    </ul>
    <form method="POST">
        <input type="hidden" name="confirm" value="yes">
        <button type="submit" class="bg-blue-600 text-white rounded-lg px-6 py-2 font-bold text-sm hover:bg-blue-700 transition">Run Migration</button>
    </form>
</div>
</body>
</html>
