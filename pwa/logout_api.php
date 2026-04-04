<?php
/**
 * PWA Logout API — destroys the session without any redirect.
 * Called via fetch() from the PWA so the app stays on index.php.
 */
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

session_unset();
session_destroy();

echo json_encode(['success' => true]);
