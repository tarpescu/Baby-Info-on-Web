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

    /**
     * Verifica permisiunea de scriere asupra unui copil.
     * Doar 'owner' si 'coparent' pot scrie — 'caregiver' si 'viewer'
     * sunt read-only (cerinta de curs).
     *
     * @return void Trimite 403 daca userul nu are drept de scriere.
     */
    protected function requireWritePermission(int $childId): void
    {
        $this->requireFamilyAccess($childId);

        $permission = $this->getPermission($childId);
        $writeRoles = ['owner', 'coparent'];
        if (!in_array($permission, $writeRoles, true)) {
            Response::error('Write permission required', 403);
        }
    }

    /**
     * Verifica faptul ca userul este 'owner' al copilului.
     * Folosita pentru actiuni administrative pe familie:
     * invitatii, schimbarea permisiunilor, eliminarea membrilor.
     *
     * @return void Trimite 403 daca userul nu este owner.
     */
    protected function requireOwner(int $childId): void
    {
        $this->requireFamilyAccess($childId);

        if ($this->getPermission($childId) !== 'owner') {
            Response::error('Owner permission required', 403);
        }
    }

    /**
     * Returneaza permisiunea userului curent pentru un copil
     * (owner / coparent / caregiver / viewer) sau null daca nu e membru.
     *
     * @return string|null
     */
    private function getPermission(int $childId): ?string
    {
        $userId = SessionManager::userId();

        $db = \App\Config\Database::getConnection();
        $stmt = $db->prepare("
            SELECT permission FROM family_members
            WHERE child_id = :child_id AND user_id = :user_id
            LIMIT 1
        ");
        $stmt->execute([':child_id' => $childId, ':user_id' => $userId]);
        $row = $stmt->fetch();

        return $row ? (string) $row['permission'] : null;
    }
}