<?php
$pageTitle = 'Mobile App — Church Facility Manager';
require_once __DIR__ . '/../includes/nav.php';
?>

<div class="max-w-4xl mx-auto px-4 py-8">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Mobile App</h1>
        <p class="text-gray-500 text-sm mt-1">Install the "My Tasks" app on your phone for quick access to daily assignments.</p>
    </div>

    <!-- QR Code Card -->
    <div class="bg-white rounded-2xl shadow-sm p-8 border border-transparent hover:border-blue-200 mb-6">
        <div class="flex flex-col md:flex-row items-center gap-8">

            <!-- QR Code -->
            <div class="flex-shrink-0 text-center">
                <div id="qr" class="bg-white p-4 rounded-xl border border-gray-100 inline-block"></div>
                <p class="text-xs text-gray-400 mt-3">Scan with your phone camera</p>
            </div>

            <!-- Instructions -->
            <div class="flex-1">
                <h2 class="text-lg font-bold text-gray-800 mb-3">How to Install</h2>

                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        <span class="flex-shrink-0 w-7 h-7 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-sm font-bold">1</span>
                        <div>
                            <p class="text-sm font-semibold text-gray-700">Scan the QR code</p>
                            <p class="text-xs text-gray-400 mt-0.5">Open your phone's camera and point it at the code. Tap the link that appears.</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <span class="flex-shrink-0 w-7 h-7 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-sm font-bold">2</span>
                        <div>
                            <p class="text-sm font-semibold text-gray-700">Sign in</p>
                            <p class="text-xs text-gray-400 mt-0.5">Use the same email and password you use for this site.</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <span class="flex-shrink-0 w-7 h-7 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-sm font-bold">3</span>
                        <div>
                            <p class="text-sm font-semibold text-gray-700">Add to Home Screen</p>
                            <p class="text-xs text-gray-400 mt-0.5">
                                <strong>iPhone:</strong> Tap Share → "Add to Home Screen"<br>
                                <strong>Android:</strong> Tap "Install App" when prompted, or use the browser menu
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-5 flex gap-3">
                    <button onclick="window.print()" class="border border-gray-300 hover:bg-gray-50 text-gray-600 rounded-lg px-4 py-2 text-sm transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Print QR Code
                    </button>
                    <a href="<?= url('/pwa/install.php') ?>" target="_blank"
                       class="bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg px-4 py-2 text-sm transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        Open Install Page
                    </a>
                </div>
            </div>

        </div>
    </div>

    <!-- Direct Link Card -->
    <div class="bg-white rounded-2xl shadow-sm p-6 border border-transparent hover:border-blue-200">
        <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Direct Link</h2>
        <div class="flex items-center gap-3">
            <input id="pwa-url" type="text" readonly
                   value="<?= htmlspecialchars((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . url('/pwa/install.php')) ?>"
                   class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-600 bg-gray-50 font-mono">
            <button onclick="copyLink()" id="copy-btn"
                    class="border border-gray-300 hover:bg-gray-50 text-gray-600 rounded-lg px-4 py-2 text-sm transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                Copy
            </button>
        </div>
        <p class="text-xs text-gray-400 mt-2">Share this link directly with staff who need the app.</p>
    </div>

</div>

<!-- Print Styles -->
<style>
@media print {
    nav, .no-print, button, a.bg-blue-600, .bg-white:last-child { display: none !important; }
    body { background: #fff !important; }
    .bg-white { box-shadow: none !important; border: none !important; }
    .max-w-4xl { max-width: 100% !important; }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
<script>
// Generate QR code
const pwaUrl = document.getElementById('pwa-url').value;
const qr = qrcode(0, 'M');
qr.addData(pwaUrl);
qr.make();
document.getElementById('qr').innerHTML = qr.createSvgTag(6, 0);

function copyLink() {
    const input = document.getElementById('pwa-url');
    navigator.clipboard.writeText(input.value).then(() => {
        const btn = document.getElementById('copy-btn');
        btn.innerHTML = '<svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Copied!';
        setTimeout(() => {
            btn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg> Copy';
        }, 2000);
    });
}
</script>
</body>
</html>
