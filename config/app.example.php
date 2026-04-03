<?php
/**
 * Application configuration
 *
 * Copy this file to app.php and set BASE_PATH for your environment:
 *   Local dev (root):       define('BASE_PATH', '');
 *   Bluehost subdirectory:  define('BASE_PATH', '/churchmanager');
 */
define('BASE_PATH', '');

function url($path = '') {
    return BASE_PATH . $path;
}
