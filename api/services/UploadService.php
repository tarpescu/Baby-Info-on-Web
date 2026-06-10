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

    /** Subdirector separat pentru documentele PDF (evenimente medicale). */
    private const DOCUMENTS_DIR = __DIR__ . '/../../storage/uploads/documents/';

    /**
     * Salveaza un fisier media in storage/uploads/photos/ (in afara webroot),
     * cu validare MIME reala (finfo). Returneaza filename + URL public ('/uploads/photos/...').
     */
    public function handlePhoto(array $file, int $childId): array
    {
        $errorMsg = $this->uploadErrorMessage($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($errorMsg !== null) {
            return ['success' => false, 'error' => $errorMsg];
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
            'image/jpeg'      => 'jpg',
            'image/png'       => 'png',
            'image/webp'      => 'webp',
            'video/mp4'       => 'mp4',
            'video/webm'      => 'webm',
            'video/quicktime' => 'mov',
            'audio/mpeg'      => 'mp3',
            'audio/mp4'       => 'm4a',
            'audio/wav', 'audio/x-wav' => 'wav',
            'audio/ogg'       => 'ogg',
            'text/plain'      => 'txt',
            default           => 'bin',
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

    /**
     * Salveaza un document PDF in storage/uploads/documents/ (in afara webroot),
     * cu validare MIME reala (finfo) — accepta DOAR application/pdf.
     * Folosit pentru atasamentele evenimentelor medicale.
     *
     * @param array $file    Elementul din $_FILES
     * @param int   $childId Id-ul copilului (prefixeaza numele fisierului)
     * @return array{success:bool, error?:string, filename?:string, original_name?:string,
     *               size_bytes?:int, mime_type?:string, path?:string}
     */
    public function handleDocument(array $file, int $childId): array
    {
        $errorMsg = $this->uploadErrorMessage($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($errorMsg !== null) {
            return ['success' => false, 'error' => $errorMsg];
        }

        if (($file['size'] ?? 0) > Constants::UPLOAD_MAX_SIZE) {
            return ['success' => false, 'error' => 'File too large'];
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']) ?: '';

        if ($mime !== 'application/pdf') {
            return ['success' => false, 'error' => 'Only PDF documents are allowed'];
        }

        $filename  = $childId . '_' . uniqid('', true) . '.pdf';
        $uploadDir = self::DOCUMENTS_DIR;

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
            'path'          => '/uploads/documents/' . $filename,
        ];
    }

    /**
     * Traduce codul de eroare PHP de upload intr-un mesaj clar pentru user.
     * Cel mai frecvent caz: fisierul depaseste upload_max_filesize din php.ini.
     *
     * @param  int $code Una dintre constantele UPLOAD_ERR_*
     * @return string|null null daca nu e nicio eroare (UPLOAD_ERR_OK)
     */
    private function uploadErrorMessage(int $code): ?string
    {
        return match ($code) {
            UPLOAD_ERR_OK => null,
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE =>
                'File exceeds the server upload limit (' . ini_get('upload_max_filesize') .
                '). Start the dev server with: php -S localhost:8000 -d upload_max_filesize=50M -d post_max_size=52M router.php',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded — please retry',
            UPLOAD_ERR_NO_FILE => 'No file uploaded',
            default => 'Upload failed (PHP error code ' . $code . ')',
        };
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