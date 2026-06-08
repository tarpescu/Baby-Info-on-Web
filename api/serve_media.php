<?php
/**
 * Serveste fisierele media stocate IN AFARA webroot-ului (storage/uploads),
 * printr-un script PHP. URL public: /uploads/<cale>, fisier real: storage/uploads/<cale>.
 *
 * Securitate: nu serveste decat fisiere din interiorul lui storage/uploads
 * (protectie anti path-traversal cu realpath), si valideaza MIME-ul real.
 *
 * @author Tarpescu Sergiu
 */

declare(strict_types=1);

$base = realpath(__DIR__ . '/../storage/uploads');
if ($base === false) {
    http_response_code(404);
    exit;
}

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$uri = urldecode((string) $uri);

// Doar caile sub /uploads/ sunt servite de acest script.
$prefix = '/uploads/';
if (!str_starts_with($uri, $prefix)) {
    http_response_code(404);
    exit;
}

$relative = substr($uri, strlen($prefix));

// Respinge incercarile evidente de path-traversal / byte-uri nule.
if ($relative === '' || str_contains($relative, '..') || str_contains($relative, "\0")) {
    http_response_code(403);
    exit;
}

$full = realpath($base . '/' . $relative);

// Fisierul trebuie sa existe SI sa fie strict in interiorul lui storage/uploads.
if ($full === false || !is_file($full) || !str_starts_with($full, $base . DIRECTORY_SEPARATOR)) {
    http_response_code(404);
    exit;
}

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($full) ?: 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($full));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=86400');
readfile($full);
exit;
