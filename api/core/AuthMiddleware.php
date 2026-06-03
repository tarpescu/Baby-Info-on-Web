<?php
/**
 * @author Romila Raluca
 */

declare(strict_types=1);

namespace App\Core;

class AuthMiddleware
{
    public static function check(): void
    {
        if (!SessionManager::isAuthenticated()) {
            Response::error('Unauthorized', 401);
        }
    }

    public static function checkSuperAdmin(): void
    {
        self::check();
        if (!SessionManager::isSuperAdmin()) {
            Response::error('Forbidden', 403);
        }
    }
}