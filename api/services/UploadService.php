<?php
/**
 * @author Romila Raluca
 */

declare(strict_types=1);

namespace App\Services;

use App\Config\Constants;

class UploadService
{
    /** Stocare in AFARA webroot-ului (storage/), servita printr-un script PHP. */
    private const STORAGE_DIR = __DIR__ . '/../../storage/uploads/photos/';

    /**
     * Salveaza un fisier media in storage/uploads/photos/ (in afara webroot),
     * cu validare MIME reala (finfo). Returneaza filename + URL public ('/uploads/photos/...').
     */
    public function handlePhoto(array $file, int $childId): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Upload failed'];
        }

        if (($file['size'] ?? 0) > Constants::UPLOAD_MAX_SIZE) {
            return ['success' => false, 'error' => 'File too large'];
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']) ?: '';

        if (!in_array($mime, Constants::UPLOAD_ALLOWED_TYPES, true)) {
            return ['success' => false, 'error' => 'Invalid file type'];
        }

        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'video/mp4'  => 'mp4',
            'audio/mpeg' => 'mp3',
            default      => 'bin',
        };

        $filename = $childId . '_' . uniqid('', true) . '.' . $ext;
        $uploadDir = self::STORAGE_DIR;

        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            return ['success' => false, 'error' => 'Could not create upload directory'];
        }

        if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
            return ['success' => false, 'error' => 'Move failed'];
        }

        return [
            'success'       => true,
            'filename'      => $filename,
            'original_name' => $file['name'] ?? $filename,
            'size_bytes'    => $file['size'] ?? 0,
            'mime_type'     => $mime,
            'path'          => '/uploads/photos/' . $filename,
        ];
    }

    public function delete(string $filename): bool
    {
        $path = self::STORAGE_DIR . basename($filename);
        if (file_exists($path)) {
            return unlink($path);
        }
        return false;
    }
}