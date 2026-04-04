<?php
// Quick diagnostic — check what's on disk and clear OPcache
header('Content-Type: text/plain');
header('Cache-Control: no-cache');

echo "=== File check ===\n";
$content = file_get_contents(__DIR__ . '/index.php');
if (strpos($content, 'v8') !== false) {
    echo "index.php ON DISK: contains v8 ✓\n";
} else {
    echo "index.php ON DISK: does NOT contain v8 ✗\n";
}

echo "\n=== OPcache status ===\n";
if (function_exists('opcache_get_status')) {
    $status = opcache_get_status(false);
    echo "OPcache enabled: " . ($status['opcache_enabled'] ? 'YES' : 'NO') . "\n";
    echo "Cached scripts: " . $status['opcache_statistics']['num_cached_scripts'] . "\n";

    // Invalidate index.php
    $file = realpath(__DIR__ . '/index.php');
    echo "\nInvalidating: $file\n";
    $result = opcache_invalidate($file, true);
    echo "Invalidate result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
} else {
    echo "OPcache not available\n";
}

echo "\n=== Git info ===\n";
echo "HEAD: " . trim(shell_exec('cd ' . escapeshellarg(dirname(__DIR__)) . ' && git log --oneline -1 2>&1')) . "\n";
// Also reset entire OPcache to be thorough
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "Full OPcache reset: DONE\n";
}
echo "\nDone. Reload index.php now.\n";
