<?php
/**
 * @author Romila Raluca
 */

declare(strict_types=1);

namespace App\Core;

use App\Config\Constants;

class Security
{
    public static function xss(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function generateCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(Constants::CSRF_TOKEN_LENGTH));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validateCsrfToken(?string $token): bool
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
    }

    public static function sanitizeInput(string $input): string
    {
        return trim(strip_tags($input));
    }

    public static function generateInviteToken(): string
    {
        return bin2hex(random_bytes(Constants::INVITE_TOKEN_LENGTH));
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}