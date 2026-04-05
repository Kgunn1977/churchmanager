<?php
$pageTitle = 'Add Nickname to Resources — Church Facility Manager';
require_once __DIR__ . '/../includes/nav.php';
require_once __DIR__ . '/../config/database.php';
requireLogin();
if (!isAdmin()) { echo '<p style="padding:40px;color:red;">Admin only.</p></body></html>'; exit; }
$db = getDB();
?>

<div style="max-width:600px;margin:40px auto;font-family:ui-sans-serif,system-ui,sans-serif;padding:0 16px;">
<?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'yes'): ?>
    <h2 style="font-size:20px;font-weight:700;margin-bottom:16px;">Migration Results</h2>
    <?php
    $statements = [
        "ALTER TABLE supplies ADD COLUMN IF NOT EXISTS nickname VARCHAR(100) DEFAULT NULL AFTER name",
        "ALTER TABLE tools ADD COLUMN IF NOT EXISTS nickname VARCHAR(100) DEFAULT NULL AFTER name",
        "ALTER TABLE materials ADD COLUMN IF NOT EXISTS nickname VARCHAR(100) DEFAULT NULL AFTER name",
        "ALTER TABLE equipment ADD COLUMN IF NOT EXISTS nickname VARCHAR(100) DEFAULT NULL AFTER name",
        "ALTER TABLE equipment_catalog ADD COLUMN IF NOT EXISTS nickname VARCHAR(100) DEFAULT NULL AFTER name",
    ];
    foreach ($statements as $sql) {
        try {
            $db->exec($sql);
            echo '<div style="padding:8px 12px;margin-bottom:6px;background:#ecfdf5;border-radius:8px;color:#065f46;font-size:13px;">✅ ' . htmlspecialchars($sql) . '</div>';
        } catch (PDOException $e) {
            echo '<div style="padding:8px 12px;margin-bottom:6px;background:#fef2f2;border-radius:8px;color:#991b1b;font-size:13px;">❌ ' . htmlspecialchars($sql) . '<br><small>' . htmlspecialchars($e->getMessage()) . '</small></div>';
        }
    }
    ?>
    <a href="/pages/catalog.php?type=supplies" style="display:inline-block;margin-top:16px;background:#2563eb;color:white;text-decoration:none;padding:10px 20px;border-radius:8px;font-size:13px;font-weight:700;">Go to Catalog →</a>
<?php else: ?>
    <h2 style="font-size:20px;font-weight:700;margin-bottom:8px;">Add Nickname Field to All Resources</h2>
    <p style="color:#6b7280;font-size:14px;margin-bottom:20px;">This migration adds a <code>nickname</code> column to all resource tables. Nicknames are short display names shown in place of the full product name.</p>
    <div style="background:#f9fafb;border-radius:12px;padding:16px;margin-bottom:20px;">
        <p style="font-size:13px;font-weight:600;color:#374151;margin:0 0 8px;">Tables to update:</p>
        <ul style="margin:0;padding-left:20px;font-size:13px;color:#6b7280;">
            <li><strong>supplies</strong> — ADD nickname VARCHAR(100)</li>
            <li><strong>tools</strong> — ADD nickname VARCHAR(100)</li>
            <li><strong>materials</strong> — ADD nickname VARCHAR(100)</li>
            <li><strong>equipment</strong> — ADD nickname VARCHAR(100)</li>
            <li><strong>equipment_catalog</strong> — ADD nickname VARCHAR(100)</li>
        </ul>
    </div>
    <form method="POST">
        <input type="hidden" name="confirm" value="yes">
        <button type="submit" style="background:#2563eb;color:white;border:none;border-radius:10px;padding:10px 24px;font-size:14px;font-weight:700;cursor:pointer;">Run Migration</button>
    </form>
<?php endif; ?>
</div>
</body>
</html>
