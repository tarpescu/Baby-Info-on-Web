<?php
/**
 * @author Romila Raluca
 */

declare(strict_types=1);

namespace App\Services;

use App\Config\Constants;

class UploadService
{
    public function handle(array $file, int $childId): array
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Upload failed'];
        }

        if ($file['size'] > Constants::UPLOAD_MAX_SIZE) {
            return ['success' => false, 'error' => 'File too large'];
        }

        if (!in_array($file['type'], Constants::UPLOAD_ALLOWED_TYPES, true)) {
            return ['success' => false, 'error' => 'Invalid file type'];
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid((string) $childId . '_', true) . '.' . $ext;
        $uploadDir = __DIR__ . '/../../public/uploads/';
        $destPath = $uploadDir . $filename;

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            return ['success' => false, 'error' => 'Move failed'];
        }

        return [
            'success' => true,
            'filename' => $filename,
            'original_name' => $file['name'],
            'size_bytes' => $file['size'],
            'mime_type' => $file['type'],
            'path' => '/uploads/' . $filename,
        ];
    }

    public function delete(string $filename): bool
    {
        $path = __DIR__ . '/../../public/uploads/' . basename($filename);
        if (file_exists($path)) {
            return unlink($path);
        }
        return false;
    }
}