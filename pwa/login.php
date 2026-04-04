<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

// Prevent caching of login page
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Handle logout — clear session manually so we can redirect to PWA login
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    // Redirect to clean login URL (no ?logout param) via JS to avoid iOS PWA issues
    echo '<!DOCTYPE html><html><head><meta http-equiv="Cache-Control" content="no-cache,no-store,must-revalidate"></head>';
    echo '<body style="background:#2563eb;"></body>';
    echo '<script>window.location.replace(' . json_encode(url('/pwa/login.php')) . ');</script></html>';
    exit;
}

// Already logged in — go to PWA home
if (isLoggedIn()) {
    $dest = url('/pwa/index.php') . '?_t=' . time();
    echo '<!DOCTYPE html><html><head><meta http-equiv="Cache-Control" content="no-cache,no-store,must-revalidate"></head>';
    echo '<body style="background:#1e40af;"></body>';
    echo '<script>window.location.replace(' . json_encode($dest) . ');</script></html>';
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$email || !$password) {
        $error = 'Please enter your email and password.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            loginUser($user);
            // Use JS redirect instead of HTTP 302 — iOS standalone PWAs
            // lose page state (top bar) after server redirect chains.
            $dest = url(isset($_COOKIE['cfm_pwa_seen']) ? '/pwa/index.php' : '/pwa/install.php');
            $dest .= (strpos($dest, '?') !== false ? '&' : '?') . '_t=' . time();
            echo '<!DOCTYPE html><html><head>';
            echo '<meta name="viewport" content="width=device-width,initial-scale=1.0,viewport-fit=cover">';
            echo '<meta http-equiv="Cache-Control" content="no-cache,no-store,must-revalidate">';
            echo '</head><body style="background:#1e40af;"></body>';
            echo '<script>window.location.replace(' . json_encode($dest) . ');</script>';
            echo '</html>';
            exit;
        } else {
            $error = 'Incorrect email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#1e3a5f">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Sign In — My Tasks</title>
    <link rel="manifest" href="<?= url('/pwa/manifest.php') ?>">
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
        .login-wrap { width: 100%; max-width: 380px; }
        .logo {
            text-align: center; margin-bottom: 32px;
        }
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
        .error {
            background: #fef2f2; border: 1px solid #fecaca; color: #dc2626;
            border-radius: 12px; padding: 10px 14px; font-size: 13px;
            margin-bottom: 16px; display: flex; align-items: center; gap: 8px;
        }
        label {
            display: block; font-size: 13px; font-weight: 600; color: #374151;
            margin-bottom: 6px;
        }
        input[type=email], input[type=password] {
            width: 100%; padding: 12px 14px; border: 1px solid #d1d5db;
            border-radius: 12px; font-size: 15px; color: #111827;
            outline: none; transition: border-color .15s;
            font-family: inherit;
        }
        input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.15); }
        .field { margin-bottom: 16px; }
        .btn {
            width: 100%; padding: 14px; background: #2563eb; color: #fff;
            border: none; border-radius: 12px; font-size: 15px; font-weight: 600;
            cursor: pointer; transition: background .15s;
            font-family: inherit;
        }
        .btn:active { background: #1d4ed8; }
        .footer {
            text-align: center; color: rgba(255,255,255,.5);
            font-size: 12px; margin-top: 24px;
        }
    </style>
</head>
<body>
    <div class="login-wrap">
        <div class="logo">
            <div class="logo-icon">
                <svg width="32" height="32" fill="none" stroke="#1e40af" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
            <h1>My Tasks</h1>
            <p>Sign in to view your assignments</p>
        </div>

        <div class="card">
            <?php if ($error): ?>
                <div class="error">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= url('/pwa/login.php') ?>" novalidate>
                <div class="field">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="you@yourchurch.org"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autocomplete="email">
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn">Sign In</button>

                <a href="<?= url('/forgot_password.php?pwa=1') ?>" style="display:block;text-align:center;margin-top:16px;color:#3b82f6;font-size:13px;font-weight:600;text-decoration:none;">
                    Forgot your password?
                </a>
            </form>
        </div>

        <p class="footer">Church Facility Manager</p>
    </div>
</body>
</html>
