<?php
$pageTitle = 'PWA Preview — Church Facility Manager';
$extraHead = '<style>
    /* Hide scrollbar on the iframe container */
    .phone-screen::-webkit-scrollbar { display: none; }
    .phone-screen { scrollbar-width: none; }
</style>';
require_once __DIR__ . '/../includes/nav.php';
require_once __DIR__ . '/../config/database.php';
$db = getDB();
$user = getCurrentUser();
?>

<div style="height:calc(100vh - 56px); display:flex; overflow:hidden;">

    <!-- ── Phone Frame ────────────────────────────────────── -->
    <div style="flex:1; display:flex; align-items:center; justify-content:center; background:#f3f4f6; padding:24px;">

        <div style="position:relative;">
            <!-- Phone bezel -->
            <div style="width:375px; height:812px; background:#1a1a1a; border-radius:44px; padding:14px; box-shadow:0 25px 60px rgba(0,0,0,.3), inset 0 0 0 2px #333;">
                <!-- Notch -->
                <div style="position:absolute; top:14px; left:50%; transform:translateX(-50%); width:150px; height:28px; background:#1a1a1a; border-radius:0 0 16px 16px; z-index:10;"></div>
                <!-- Screen -->
                <iframe id="pwa-frame"
                        src="<?= url('/pages/janitor.php') ?>?embed=1"
                        style="width:100%; height:100%; border:none; border-radius:32px; background:#fff;"
                        allow="same-origin"></iframe>
            </div>

            <!-- Device label -->
            <div style="text-align:center; margin-top:16px;">
                <span style="font-size:12px; color:#9ca3af; font-weight:500;">iPhone 14 Pro — 375 × 812</span>
            </div>
        </div>

    </div>

    <!-- ── Sidebar ────────────────────────────────────────── -->
    <div style="width:320px; background:#fff; border-left:1px solid #e5e7eb; padding:24px; overflow-y:auto; flex-shrink:0;">

        <h2 style="font-size:18px; font-weight:700; color:#111827; margin-bottom:4px;">PWA Preview</h2>
        <p style="font-size:13px; color:#6b7280; margin-bottom:20px;">This is a live preview of what the phone app looks like. Changes to the code are reflected instantly.</p>

        <!-- Device Picker -->
        <div style="margin-bottom:20px;">
            <label style="display:block; font-size:11px; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:6px;">Device</label>
            <select id="device-picker" onchange="changeDevice()" style="width:100%; border:1px solid #d1d5db; border-radius:8px; padding:8px 12px; font-size:13px; color:#374151;">
                <option value="375,812">iPhone 14 Pro (375 × 812)</option>
                <option value="390,844">iPhone 15 (390 × 844)</option>
                <option value="430,932">iPhone 15 Pro Max (430 × 932)</option>
                <option value="360,780">Android Medium (360 × 780)</option>
                <option value="412,915">Pixel 7 (412 × 915)</option>
            </select>
        </div>

        <!-- Refresh -->
        <div style="margin-bottom:20px;">
            <button onclick="document.getElementById('pwa-frame').src = document.getElementById('pwa-frame').src"
                    style="width:100%; border:1px solid #d1d5db; border-radius:8px; padding:8px 12px; font-size:13px; color:#374151; background:#fff; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:6px; transition:background .15s;"
                    onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='#fff'">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Refresh Preview
            </button>
        </div>

        <hr style="border:none; border-top:1px solid #f3f4f6; margin-bottom:20px;">

        <!-- Quick Links -->
        <label style="display:block; font-size:11px; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:8px;">Quick Links</label>

        <a href="<?= url('/pages/janitor.php') ?>" target="_blank"
           style="display:flex; align-items:center; gap:8px; padding:10px 12px; border:1px solid #e5e7eb; border-radius:8px; margin-bottom:8px; text-decoration:none; color:#374151; font-size:13px; font-weight:500; transition:background .15s;"
           onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='transparent'">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            Open Full Page
        </a>

        <a href="<?= url('/pages/app.php') ?>"
           style="display:flex; align-items:center; gap:8px; padding:10px 12px; border:1px solid #e5e7eb; border-radius:8px; margin-bottom:8px; text-decoration:none; color:#374151; font-size:13px; font-weight:500; transition:background .15s;"
           onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='transparent'">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            Install Instructions
        </a>

        <a href="<?= url('/pages/scheduling.php') ?>"
           style="display:flex; align-items:center; gap:8px; padding:10px 12px; border:1px solid #e5e7eb; border-radius:8px; text-decoration:none; color:#374151; font-size:13px; font-weight:500; transition:background .15s;"
           onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='transparent'">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            Cleaning Schedules
        </a>

    </div>

</div>

<script>
function changeDevice() {
    const [w, h] = document.getElementById('device-picker').value.split(',').map(Number);
    const bezel = document.querySelector('[style*="width:375px"]') || document.querySelector('[style*="width:390px"]') || document.querySelector('[style*="width:360px"]') || document.querySelector('[style*="width:412px"]') || document.querySelector('[style*="width:430px"]');

    // Update bezel size
    const outer = document.getElementById('pwa-frame').parentElement;
    outer.style.width = w + 'px';
    outer.style.height = h + 'px';

    // Update label
    const label = outer.parentElement.querySelector('span');
    const option = document.getElementById('device-picker').selectedOptions[0];
    label.textContent = option.text;
}
</script>

</body>
</html>
