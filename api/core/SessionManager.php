<?php
/**
 * @author Romila Raluca
 */

declare(strict_types=1);

namespace App\Core;

class SessionManager
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key): mixed
    {
        return $_SESSION[$key] ?? null;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => '/']);
        }
        session_destroy();
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function userId(): ?int
    {
        return self::get('user_id') ? (int) self::get('user_id') : null;
    }

    public static function isAuthenticated(): bool
    {
        return self::has('user_id') && self::get('user_id') !== null;
    }

    public static function isSuperAdmin(): bool
    {
        return self::get('is_superadmin') === true;
    }
}