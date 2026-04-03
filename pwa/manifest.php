<?php
require_once __DIR__ . '/../config/app.php';
header('Content-Type: application/json');
echo json_encode([
    'name' => 'Church Facility Manager — Tasks',
    'short_name' => 'My Tasks',
    'description' => 'View and complete your assigned facility tasks',
    'start_url' => url('/pwa/index.php'),
    'display' => 'standalone',
    'background_color' => '#1e40af',
    'theme_color' => '#1e40af',
    'orientation' => 'portrait',
    'icons' => [
        [
            'src' => url('/pwa/icons/icon-192.svg'),
            'sizes' => '192x192',
            'type' => 'image/svg+xml',
            'purpose' => 'any maskable'
        ],
        [
            'src' => url('/pwa/icons/icon-512.svg'),
            'sizes' => '512x512',
            'type' => 'image/svg+xml',
            'purpose' => 'any maskable'
        ]
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
