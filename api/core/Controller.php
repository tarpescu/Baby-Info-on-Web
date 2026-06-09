<?php
/**
 * @author Romila Raluca
 */

declare(strict_types=1);

namespace App\Core;

use App\Core\Security;
use App\Core\AuthMiddleware;

abstract class Controller
{
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    protected function requireAuth(): void
    {
        if (!SessionManager::isAuthenticated()) {
            Response::error('Unauthorized', 401);
        }
    }

    protected function requireSuperAdmin(): void
    {
        $this->requireAuth();
        if (!SessionManager::isSuperAdmin()) {
            Response::error('Forbidden', 403);
        }
    }

    protected function requireFamilyAccess(int $childId): void
    {
        $this->requireAuth();
        $userId = SessionManager::userId();

        $db = \App\Config\Database::getConnection();
        $stmt = $db->prepare("
            SELECT 1 FROM family_members 
            WHERE child_id = :child_id AND user_id = :user_id
            LIMIT 1
        ");
        $stmt->execute([':child_id' => $childId, ':user_id' => $userId]);

        if (!$stmt->fetch()) {
            Response::error('Access denied to this child', 403);
        }
    }

    /**
     * Verifica prezenta si validitatea token-ului CSRF (header X-CSRF-Token).
     * Ignorata pentru cererile cu Bearer token (API v1) — acestea sunt CSRF-safe
     * prin definitie, deoarece token-ul nu este trimis automat de browser.
     *
     * @return void
     */
    protected function requireCsrf(): void
    {
        // Bearer token requests sunt CSRF-safe — nu necesita validare
        if (AuthMiddleware::hasBearerAuth()) {
            return;
        }

        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!Security::validateCsrfToken($token)) {
            Response::error('Invalid or missing CSRF token', 403);
        }
    }

    protected function requireWritePermission(int $childId): void
    {
        $this->requireFamilyAccess($childId);
        $userId = SessionManager::userId();

        $db = \App\Config\Database::getConnection();
        $stmt = $db->prepare("
            SELECT permission FROM family_members 
            WHERE child_id = :child_id AND user_id = :user_id
            LIMIT 1
        ");
        $stmt->execute([':child_id' => $childId, ':user_id' => $userId]);
        $row = $stmt->fetch();

        $writeRoles = ['owner', 'coparent', 'caregiver'];
        if (!$row || !in_array($row['permission'], $writeRoles, true)) {
            Response::error('Write permission required', 403);
        }
    }
}