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
// IMPORTANT: nu folosim "return false" pentru ca PHP built-in server ar servi
// fisierul din document root (/), nu din /public/. Servim manual cu readfile().
$file = __DIR__ . '/public' . $uri;
if (file_exists($file) && is_file($file)) {
    $mime = mime_content_type($file) ?: 'application/octet-stream';
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($file));
    readfile($file);
    exit;
}

// Mapare URL curate -> pagini HTML
$pageMap = [
    '/'                => 'landingpage.html',
    '/login'           => 'login.html',
    '/register'        => 'register.html',
    '/forgot-password' => 'forgotpassword.html',
    '/dashboard'       => 'dashboard.html',
    '/join'            => 'join.html',
    '/gallery'         => 'gallery.html',
    '/admin'           => 'admin.html',
    '/medical'         => 'medical.html',
];

if (isset($pageMap[$uri])) {
    readfile(__DIR__ . '/public/' . $pageMap[$uri]);
    exit;
}

// Fallback -> landing page
readfile(__DIR__ . '/public/landingpage.html');