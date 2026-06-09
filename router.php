<?php
/**
 * Router pentru PHP built-in server
 * Mapare URL curate -> fisiere HTML din public/
 * @author Romila Raluca
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = urldecode($uri);

// API requests -> api/index.php
// (si paginile publice din afara prefixului /api/: feed RSS si pagina de share per moment)
if (str_starts_with($uri, '/api/') || str_starts_with($uri, '/feed/') || str_starts_with($uri, '/share/')) {
    require __DIR__ . '/api/index.php';
    exit;
}

// Media uploads: fisierele sunt stocate in AFARA webroot (storage/uploads) si
// servite printr-un script PHP, nu direct ca fisiere statice din public/.
if (str_starts_with($uri, '/uploads/')) {
    require __DIR__ . '/api/serve_media.php';
    exit;
}

// Static files (css, js, images etc.) -> public/
// IMPORTANT: nu folosim "return false" pentru ca PHP built-in server ar servi
// fisierul din document root (/), nu din /public/. Servim manual cu readfile().
$file = __DIR__ . '/public' . $uri;
if (file_exists($file) && is_file($file)) {
    $extMap = [
        'css'  => 'text/css',
        'js'   => 'application/javascript',
        'html' => 'text/html',
        'json' => 'application/json',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'svg'  => 'image/svg+xml',
        'webp' => 'image/webp',
        'ico'  => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2'=> 'font/woff2',
        'ttf'  => 'font/ttf',
    ];
    $ext  = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $mime = $extMap[$ext] ?? (mime_content_type($file) ?: 'application/octet-stream');
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
    '/family'          => 'family.html',
];

if (isset($pageMap[$uri])) {
    readfile(__DIR__ . '/public/' . $pageMap[$uri]);
    exit;
}

// Fallback -> landing page
readfile(__DIR__ . '/public/landingpage.html');