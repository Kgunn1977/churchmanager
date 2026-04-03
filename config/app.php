<?php
/**
 * Application configuration
 *
 * BASE_PATH: Set to '' for root installs (localhost), or '/churchmanager' for subdirectory installs.
 * No trailing slash.
 */
define('BASE_PATH', '');

/**
 * Helper: prepend BASE_PATH to a URL path.
 * Usage: url('/pages/tasks.php') → '/churchmanager/pages/tasks.php' (or '/pages/tasks.php' locally)
 */
function url($path = '') {
    return BASE_PATH . $path;
}
