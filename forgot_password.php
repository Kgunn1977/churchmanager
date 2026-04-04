<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'config/app.php';

// Already logged in
if (isLoggedIn()) {
    header('Location: ' . url('/pages/reservations.php'));
    exit;
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!$email) {
        $message = 'Please enter your email address.';
        $messageType = 'error';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, name, email FROM users WHERE email = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Always show success message to prevent email enumeration
        $message = 'If an account with that email exists, a password reset link has been sent.';
        $messageType = 'success';

        if ($user) {
            try {
                // Check if reset_token column exists (migration may not have run)
                $colCheck = $db->query("SHOW COLUMNS FROM users LIKE 'reset_token'")->fetch();
                if (!$colCheck) {
                    // Auto-create the columns if missing
                    $db->exec("ALTER TABLE users ADD COLUMN reset_token VARCHAR(64) NULL DEFAULT NULL AFTER password");
                    $db->exec("ALTER TABLE users ADD COLUMN reset_token_expires DATETIME NULL DEFAULT NULL AFTER reset_token");
                }

                // Generate secure token
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour

                $db->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?")
                   ->execute([$token, $expires, $user['id']]);

                // Build reset URL
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'];
                $resetUrl = $protocol . '://' . $host . url('/reset_password.php') . '?token=' . $token;

                // Send email
                $to = $user['email'];
                $subject = 'Password Reset - Church Facility Manager';
                $body = "Hi {$user['name']},\r\n\r\n";
                $body .= "You requested a password reset. Click the link below to set a new password:\r\n\r\n";
                $body .= $resetUrl . "\r\n\r\n";
                $body .= "This link expires in 1 hour.\r\n\r\n";
                $body .= "If you didn't request this, you can safely ignore this email.\r\n\r\n";
                $body .= "- Church Facility Manager";

                $headers  = "From: noreply@kg-fire.com\r\n";
                $headers .= "Reply-To: noreply@kg-fire.com\r\n";
                $headers .= "MIME-Version: 1.0\r\n";
                $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
                $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

                mail($to, $subject, $body, $headers);
            } catch (Exception $e) {
                // Silently fail — don't reveal whether the email exists
            }
        }
    }
}

// Detect if request came from PWA
$isPwa = isset($_GET['pwa']) || (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], '/pwa/') !== false);
$backUrl = $isPwa ? url('/pwa/login.php') : url('/login.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#1e3a5f">
    <title>Forgot Password — Church Facility Manager</title>
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
        input[type=email] {
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
            <h1>Reset Password</h1>
            <p>Enter your email to receive a reset link</p>
        </div>

        <div class="card">
            <?php if ($message): ?>
                <div class="msg <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <?php if ($messageType !== 'success'): ?>
            <form method="POST" action="<?= url('/forgot_password.php') . ($isPwa ? '?pwa=1' : '') ?>" novalidate>
                <div class="field">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="you@yourchurch.org"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autocomplete="email">
                </div>
                <button type="submit" class="btn">Send Reset Link</button>
            </form>
            <?php endif; ?>

            <a href="<?= $backUrl ?>" class="back-link">← Back to Sign In</a>
        </div>

        <p class="footer">Church Facility Manager</p>
    </div>
</body>
</html>
