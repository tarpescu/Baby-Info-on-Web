<?php
/**
 * Router pentru PHP built-in server
 * Mapare URL curate -> fisiere HTML din public/
 * @author Romila Raluca
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = urldecode($uri);

// API requests -> api/index.php
if (str_starts_with($uri, '/api/')) {
    require __DIR__ . '/api/index.php';
    exit;
}

// Static files (css, js, images, uploads etc.) -> public/
$file = __DIR__ . '/public' . $uri;
if (file_exists($file) && is_file($file)) {
    return false;
}

// Mapare URL curate -> pagini HTML
$pageMap = [
    '/'                => 'landingpage.html',
    '/login'           => 'login.html',
    '/register'        => 'register.html',
    '/forgot-password' => 'forgotpassword.html',
    '/dashboard'       => 'dashboard.html',
];

if (isset($pageMap[$uri])) {
    readfile(__DIR__ . '/public/' . $pageMap[$uri]);
    exit;
}

// Fallback -> landing page
readfile(__DIR__ . '/public/landingpage.html');