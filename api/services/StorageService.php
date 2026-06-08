<?php
/**
 * Calculeaza recursiv spatiul ocupat pe disc de folderul de uploads.
 * @author Tarpescu Sergiu
 */

declare(strict_types=1);

namespace App\Services;

class StorageService
{
    /** Radacina implicita pentru fisierele incarcate (in afara webroot-ului). */
    private const UPLOAD_DIR = __DIR__ . '/../../storage/uploads';

    private string $baseDir;

    public function __construct(?string $baseDir = null)
    {
        $this->baseDir = $baseDir ?? self::UPLOAD_DIR;
    }

    /**
     * Spatiul total ocupat de folderul de uploads.
     * @return array{bytes:int, files:int, human:string}
     */
    public function getUsage(): array
    {
        return $this->scan($this->baseDir);
    }

    /**
     * Parcurge recursiv un director si insumeaza dimensiunea fisierelor.
     * Daca directorul nu exista, intoarce zero (nu arunca eroare).
     * @return array{bytes:int, files:int, human:string}
     */
    public function scan(string $dir): array
    {
        if (!is_dir($dir)) {
            return ['bytes' => 0, 'files' => 0, 'human' => $this->humanBytes(0)];
        }

        $bytes = 0;
        $files = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $bytes += $file->getSize();
                $files++;
            }
        }

        return [
            'bytes' => $bytes,
            'files' => $files,
            'human' => $this->humanBytes($bytes),
        ];
    }

    /**
     * Formateaza un numar de bytes intr-un sir prietenos (B/KB/MB/GB/TB).
     */
    public function humanBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        $value = (float) $bytes;
        while ($value >= 1024 && $i < count($units) - 1) {
            $value /= 1024;
            $i++;
        }
        return round($value, 2) . ' ' . $units[$i];
    }
}
