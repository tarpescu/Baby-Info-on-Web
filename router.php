<?php
/**
 * Router pentru PHP built-in server
 * @author Romila Raluca
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = urldecode($uri);

// API requests -> api/index.php
if (str_starts_with($uri, '/api/')) {
    require __DIR__ . '/api/index.php';
    exit;
}

// Static files -> public/
$file = __DIR__ . '/public' . $uri;
if (file_exists($file) && is_file($file)) {
    return false;
}

// SPA fallback -> public/index.html
require __DIR__ . '/public/index.html';