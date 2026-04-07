<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if (!isAdmin()) { echo 'Admin only'; exit; }
require_once __DIR__ . '/../config/database.php';
$db = getDB();

$confirmed = ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'yes');

// Count duplicates
$dupeCount = $db->query("
    SELECT COUNT(*) FROM janitor_task_assignments ja
    WHERE ja.id NOT IN (
        SELECT MIN(sub.id) FROM janitor_task_assignments sub
        GROUP BY sub.assigned_date, sub.assigned_to, sub.room_id,
                 COALESCE(sub.task_group_id, 0), COALESCE(sub.task_id, 0)
    )
")->fetchColumn();

if (!$confirmed) {
?>
<!DOCTYPE html>
<html><head><title>Dedup Assignments</title>
<style>body{font-family:sans-serif;max-width:700px;margin:40px auto;padding:20px;}
.btn{background:#2563eb;color:#fff;padding:10px 24px;border:none;border-radius:8px;font-size:16px;cursor:pointer;}
.btn:hover{background:#1d4ed8;}</style>
</head><body>
<h1>Remove Duplicate Assignments</h1>
<p>Found <strong><?= number_format($dupeCount) ?></strong> duplicate assignment rows to remove.</p>
<p>This keeps the row with the lowest ID for each unique combination of (date, worker, room, task_group, task) and deletes the rest.</p>
<?php if ($dupeCount > 0): ?>
<form method="POST">
    <input type="hidden" name="confirm" value="yes">
    <button type="submit" class="btn">Remove Duplicates</button>
</form>
<?php else: ?>
<p style="color:green;">No duplicates found. Nothing to do.</p>
<?php endif; ?>
<p><a href="/pages/janitor.php">← Back to Janitor</a></p>
</body></html>
<?php
    exit;
}

// Execute dedup: delete all rows that aren't the MIN(id) per unique combo
$deleted = $db->exec("
    DELETE FROM janitor_task_assignments
    WHERE id NOT IN (
        SELECT keep_id FROM (
            SELECT MIN(id) AS keep_id FROM janitor_task_assignments
            GROUP BY assigned_date, assigned_to, room_id,
                     COALESCE(task_group_id, 0), COALESCE(task_id, 0)
        ) AS keeper
    )
");

// Also clean up orphaned checklist rows
$orphaned = $db->exec("
    DELETE FROM janitor_task_checklist
    WHERE assignment_id NOT IN (SELECT id FROM janitor_task_assignments)
");

?>
<!DOCTYPE html>
<html><head><title>Dedup Complete</title>
<style>body{font-family:sans-serif;max-width:700px;margin:40px auto;padding:20px;}
.ok{color:#16a34a;font-weight:bold;}</style>
</head><body>
<h1>Dedup Complete</h1>
<p class="ok">Removed <?= number_format($deleted) ?> duplicate assignment rows.</p>
<p>Cleaned up <?= number_format($orphaned) ?> orphaned checklist rows.</p>
<p><a href="/pages/janitor.php">← Back to Janitor</a></p>
</body></html>
