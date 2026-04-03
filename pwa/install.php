<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/app.php';
if (!isLoggedIn()) {
    header('Location: ' . url('/pwa/login.php'));
    exit;
}
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#1e40af">
    <title>Install My Tasks</title>
    <link rel="manifest" href="<?= url('/pwa/manifest.php') ?>">
    <link rel="apple-touch-icon" href="<?= url('/pwa/icons/icon-192.svg') ?>">
    <style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
    min-height: 100vh; min-height: 100dvh;
    display: flex; align-items: center; justify-content: center;
    padding: 24px;
    padding-top: max(24px, env(safe-area-inset-top));
    color: #fff;
}
.wrap { max-width: 380px; width: 100%; text-align: center; }

.icon-box {
    width: 80px; height: 80px; background: #fff; border-radius: 20px;
    display: inline-flex; align-items: center; justify-content: center;
    box-shadow: 0 8px 24px rgba(0,0,0,.2); margin-bottom: 20px;
}

h1 { font-size: 24px; font-weight: 800; margin-bottom: 6px; }
.sub { font-size: 14px; color: rgba(255,255,255,.7); margin-bottom: 32px; }

/* ── Install button (Android / desktop Chrome) ───────────── */
.install-btn {
    display: none; width: 100%; padding: 16px;
    background: #fff; color: #1e40af; border: none; border-radius: 14px;
    font-size: 16px; font-weight: 700; cursor: pointer;
    box-shadow: 0 4px 16px rgba(0,0,0,.15);
    transition: transform .1s;
    font-family: inherit;
    margin-bottom: 16px;
}
.install-btn:active { transform: scale(.97); }

/* ── iOS instructions ─────────────────────────────────────── */
.ios-instructions {
    display: none; background: rgba(255,255,255,.12);
    border-radius: 16px; padding: 24px 20px;
    backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
    text-align: left; margin-bottom: 16px;
}
.ios-instructions h2 {
    font-size: 15px; font-weight: 700; margin-bottom: 16px; text-align: center;
}
.step {
    display: flex; align-items: flex-start; gap: 12px;
    margin-bottom: 16px;
}
.step:last-child { margin-bottom: 0; }
.step-num {
    flex-shrink: 0; width: 28px; height: 28px; border-radius: 50%;
    background: rgba(255,255,255,.2); display: flex; align-items: center;
    justify-content: center; font-size: 13px; font-weight: 800;
}
.step-text { font-size: 14px; line-height: 1.5; padding-top: 3px; }
.step-text strong { color: #fff; }
.step-text .icon-inline {
    display: inline-block; vertical-align: middle; margin: 0 2px;
}

/* ── Already installed state ──────────────────────────────── */
.installed-msg {
    display: none; background: rgba(34,197,94,.15);
    border: 1px solid rgba(34,197,94,.3); border-radius: 14px;
    padding: 16px 20px; margin-bottom: 16px;
    font-size: 14px; font-weight: 600;
}

/* ── Skip link ────────────────────────────────────────────── */
.skip {
    display: inline-block; color: rgba(255,255,255,.6);
    font-size: 13px; text-decoration: none; padding: 8px;
    transition: color .15s;
}
.skip:hover { color: #fff; }
    </style>
</head>
<body>
<div class="wrap">

    <div class="icon-box">
        <svg width="40" height="40" fill="none" stroke="#1e40af" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
        </svg>
    </div>

    <h1>Install My Tasks</h1>
    <p class="sub">Add the app to your home screen for quick access</p>

    <!-- Android / Desktop Chrome: native install prompt -->
    <button class="install-btn" id="installBtn" onclick="doInstall()">
        Install App
    </button>

    <!-- iOS: manual instructions -->
    <div class="ios-instructions" id="iosInstructions">
        <h2>Add to Home Screen</h2>
        <div class="step">
            <div class="step-num">1</div>
            <div class="step-text">
                Tap the <strong>Share</strong> button
                <span class="icon-inline">
                    <svg width="20" height="20" fill="none" stroke="#93c5fd" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 12v8a2 2 0 002 2h12a2 2 0 002-2v-8"/>
                        <polyline points="16 6 12 2 8 6"/>
                        <line x1="12" y1="2" x2="12" y2="15"/>
                    </svg>
                </span>
                at the bottom of Safari
            </div>
        </div>
        <div class="step">
            <div class="step-num">2</div>
            <div class="step-text">
                Scroll down and tap <strong>"Add to Home Screen"</strong>
            </div>
        </div>
        <div class="step">
            <div class="step-num">3</div>
            <div class="step-text">
                Tap <strong>"Add"</strong> in the top right corner
            </div>
        </div>
    </div>

    <!-- Already installed -->
    <div class="installed-msg" id="installedMsg">
        ✓ App is already installed — open it from your home screen
    </div>

    <a href="<?= url('/pwa/') ?>" class="skip" onclick="document.cookie='cfm_pwa_seen=1;path=<?= url('/pwa/') ?>;max-age=31536000'">Continue in browser →</a>
</div>

<script>
// ═══════════════════════════════════════════════════════════
// CONFIG
// ═══════════════════════════════════════════════════════════
const BASE_PATH = <?= json_encode(BASE_PATH) ?>;

// ═══════════════════════════════════════════════════════════
// DETECT PLATFORM & INSTALL STATE
// ═══════════════════════════════════════════════════════════
const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
const isStandalone = window.matchMedia('(display-mode: standalone)').matches
                  || window.navigator.standalone === true;

let deferredPrompt = null;

if (isStandalone) {
    // Already running as installed PWA — redirect to app
    document.getElementById('installedMsg').style.display = 'block';
    setTimeout(() => { location.href = BASE_PATH + '/pwa/'; }, 2000);
} else if (isIOS) {
    // Show iOS manual instructions
    document.getElementById('iosInstructions').style.display = 'block';
} else {
    // Android / Desktop Chrome — wait for beforeinstallprompt
    window.addEventListener('beforeinstallprompt', e => {
        e.preventDefault();
        deferredPrompt = e;
        document.getElementById('installBtn').style.display = 'block';
    });

    // If no prompt fires within 3 seconds, show a fallback message
    setTimeout(() => {
        if (!deferredPrompt) {
            // Show generic instructions for browsers that don't support install prompt
            const btn = document.getElementById('installBtn');
            if (btn.style.display !== 'block') {
                document.getElementById('iosInstructions').style.display = 'block';
                // Update text for non-iOS
                const h2 = document.querySelector('.ios-instructions h2');
                if (h2) h2.textContent = 'Add to Home Screen';
                const steps = document.querySelectorAll('.step-text');
                if (steps[0]) steps[0].innerHTML = 'Tap the <strong>menu</strong> (⋮) in your browser\'s top right corner';
                if (steps[1]) steps[1].innerHTML = 'Tap <strong>"Add to Home Screen"</strong> or <strong>"Install App"</strong>';
                if (steps[2]) steps[2].innerHTML = 'Tap <strong>"Install"</strong> to confirm';
            }
        }
    }, 3000);
}

function doInstall() {
    if (!deferredPrompt) return;
    deferredPrompt.prompt();
    deferredPrompt.userChoice.then(result => {
        if (result.outcome === 'accepted') {
            document.getElementById('installBtn').style.display = 'none';
            document.getElementById('installedMsg').style.display = 'block';
            setTimeout(() => { location.href = BASE_PATH + '/pwa/'; }, 1500);
        }
        deferredPrompt = null;
    });
}

// Listen for successful install
window.addEventListener('appinstalled', () => {
    document.getElementById('installBtn').style.display = 'none';
    document.getElementById('installedMsg').style.display = 'block';
});

// Register service worker so the install prompt can fire
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register(BASE_PATH + '/pwa/sw.js?base=' + encodeURIComponent(BASE_PATH));
}
</script>
</body>
</html>
