<?php
/**
 * Panou de administrare la nivel de platforma (doar super-admin):
 * statistici, listare useri, ban/unban si calcul stocare /uploads.
 * @author Tarpescu Sergiu
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Core\SessionManager;
use App\Config\Database;
use App\Models\UserModel;
use App\Services\StorageService;

class AdminController extends Controller
{
    public function stats(array $params): void
    {
        $this->requireSuperAdmin();
        $db = Database::getConnection();

        $count = static fn (string $sql): int => (int) $db->query($sql)->fetchColumn();

        Response::json([
            'users' => [
                'total'  => $count("SELECT COUNT(*) FROM users"),
                'banned' => $count("SELECT COUNT(*) FROM users WHERE banned_at IS NOT NULL"),
                'admins' => $count("SELECT COUNT(*) FROM users WHERE is_superadmin = TRUE"),
            ],
            'children' => $count("SELECT COUNT(*) FROM children"),
            'moments'  => $count("SELECT COUNT(*) FROM moments"),
            'media'    => $count("SELECT COUNT(*) FROM media"),
            'comments' => $count("SELECT COUNT(*) FROM comments"),
        ]);
    }

    public function users(array $params): void
    {
        $this->requireSuperAdmin();
        $users = (new UserModel())->getAll();
        Response::json($users);
    }

    public function ban(array $params): void
    {
        $this->requireSuperAdmin();
        $targetId = (int) ($params['id'] ?? 0);

        $model = new UserModel();
        $target = $model->findById($targetId);
        if (!$target) {
            Response::error('User not found', 404);
        }

        // Protectie: nu poti bana propriul cont sau alt super-admin.
        if ($targetId === SessionManager::userId()) {
            Response::error('Nu iti poti bana propriul cont', 422);
        }
        if (!empty($target['is_superadmin'])) {
            Response::error('Nu poti bana un super-admin', 422);
        }

        $reason = trim((string) ($this->request->body['reason'] ?? ''));
        if ($reason === '') {
            $reason = 'No reason provided';
        }

        $model->ban($targetId, $reason);
        Response::json(['success' => true, 'banned' => $targetId, 'reason' => $reason]);
    }

    public function unban(array $params): void
    {
        $this->requireSuperAdmin();
        $targetId = (int) ($params['id'] ?? 0);

        $model = new UserModel();
        if (!$model->findById($targetId)) {
            Response::error('User not found', 404);
        }

        $model->unban($targetId);
        Response::json(['success' => true, 'unbanned' => $targetId]);
    }

    public function storage(array $params): void
    {
        $this->requireSuperAdmin();
        $storage = new StorageService();

        // 1. Total real de pe disc (parcurgere recursiva /uploads).
        $disk = $storage->getUsage();

        // 2. Breakdown din DB pe tip de media.
        $db = Database::getConnection();
        $rows = $db->query("
            SELECT type,
                   COUNT(*)                 AS files,
                   COALESCE(SUM(size_bytes), 0) AS bytes
            FROM media
            GROUP BY type
        ")->fetchAll();

        $byType = [];
        $dbTotalBytes = 0;
        $dbTotalFiles = 0;
        foreach ($rows as $r) {
            $bytes = (int) $r['bytes'];
            $byType[$r['type']] = [
                'files' => (int) $r['files'],
                'bytes' => $bytes,
                'human' => $storage->humanBytes($bytes),
            ];
            $dbTotalBytes += $bytes;
            $dbTotalFiles += (int) $r['files'];
        }

        Response::json([
            'disk' => $disk,
            'database' => [
                'bytes'   => $dbTotalBytes,
                'files'   => $dbTotalFiles,
                'human'   => $storage->humanBytes($dbTotalBytes),
                'by_type' => $byType,
            ],
            // Diferenta = fisiere orfane pe disc fara rand in DB (sau invers).
            'orphan_bytes' => $disk['bytes'] - $dbTotalBytes,
        ]);
    }
}
