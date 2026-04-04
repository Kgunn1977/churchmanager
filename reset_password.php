<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'config/app.php';

// Already logged in
if (isLoggedIn()) {
    header('Location: ' . url('/pages/reservations.php'));
    exit;
}

$db = getDB();
$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$message = '';
$messageType = '';
$validToken = false;
$user = null;

// Validate token
if ($token) {
    $stmt = $db->prepare("SELECT id, name, email FROM users WHERE reset_token = ? AND reset_token_expires > NOW() AND is_active = 1 LIMIT 1");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    if ($user) {
        $validToken = true;
    } else {
        $message = 'This reset link is invalid or has expired. Please request a new one.';
        $messageType = 'error';
    }
} else {
    $message = 'No reset token provided.';
    $messageType = 'error';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 6) {
        $message = 'Password must be at least 6 characters.';
        $messageType = 'error';
    } elseif ($password !== $confirm) {
        $message = 'Passwords do not match.';
        $messageType = 'error';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $db->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?")
           ->execute([$hash, $user['id']]);

        $message = 'Your password has been reset. You can now sign in.';
        $messageType = 'success';
        $validToken = false; // Hide form
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#1e3a5f">
    <title>Reset Password — Church Facility Manager</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #1e3a5f 0%, #2d6a9f 100%);
            min-height: 100vh; min-height: 100dvh;
            display: flex; align-items: center; justify-content: center;
            padding: 24px;
            padding-top: max(24px, env(safe-area-inset-top));
        }
        .wrap { width: 100%; max-width: 400px; }
        .logo { text-align: center; margin-bottom: 32px; }
        .logo-icon {
            display: inline-flex; align-items: center; justify-content: center;
            width: 64px; height: 64px; background: #fff; border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,.15); margin-bottom: 16px;
        }
        .logo h1 { color: #fff; font-size: 22px; font-weight: 700; }
        .logo p { color: rgba(255,255,255,.7); font-size: 14px; margin-top: 4px; }
        .card {
            background: #fff; border-radius: 20px; padding: 28px 24px;
            box-shadow: 0 8px 32px rgba(0,0,0,.12);
        }
        .msg {
            border-radius: 12px; padding: 10px 14px; font-size: 13px;
            margin-bottom: 16px; display: flex; align-items: center; gap: 8px;
        }
        .msg.error { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
        .msg.success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; }
        label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; }
        input[type=password] {
            width: 100%; padding: 12px 14px; border: 1px solid #d1d5db;
            border-radius: 12px; font-size: 15px; color: #111827;
            outline: none; transition: border-color .15s; font-family: inherit;
        }
        input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.15); }
        .field { margin-bottom: 16px; }
        .btn {
            width: 100%; padding: 14px; background: #2563eb; color: #fff;
            border: none; border-radius: 12px; font-size: 15px; font-weight: 600;
            cursor: pointer; transition: background .15s; font-family: inherit;
        }
        .btn:active { background: #1d4ed8; }
        .back-link {
            display: block; text-align: center; margin-top: 16px;
            color: #3b82f6; font-size: 13px; text-decoration: none; font-weight: 600;
        }
        .footer { text-align: center; color: rgba(255,255,255,.5); font-size: 12px; margin-top: 24px; }
        .pw-rules { font-size: 11px; color: #9ca3af; margin-top: 4px; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="logo">
            <div class="logo-icon">
                <svg width="32" height="32" fill="none" stroke="#1e40af" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
            <h1>New Password</h1>
            <p><?= $validToken ? 'Choose a new password for your account' : '' ?></p>
        </div>

        <div class="card">
            <?php if ($message): ?>
                <div class="msg <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <?php if ($validToken): ?>
            <form method="POST" action="<?= url('/reset_password.php') ?>" novalidate>
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <div class="field">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required autocomplete="new-password">
                    <p class="pw-rules">At least 6 characters</p>
                </div>
                <div class="field">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••" required autocomplete="new-password">
                </div>
                <button type="submit" class="btn">Reset Password</button>
            </form>
            <?php endif; ?>

            <a href="<?= url('/login.php') ?>" class="back-link">← Back to Sign In</a>
        </div>

        <p class="footer">Church Facility Manager</p>
    </div>
</body>
</html>
