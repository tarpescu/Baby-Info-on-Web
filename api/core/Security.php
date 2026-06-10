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

    /**
     * Genereaza (sau citeste) un token CSRF folosind double-submit cookie pattern.
     * Token-ul este stocat intr-un cookie accesibil JS (non-HttpOnly) si
     * returnat si in raspunsul JSON. Nu depinde de sesiunea PHP.
     *
     * @return string Token hex de 64 de caractere
     */
    public static function generateCsrfToken(): string
    {
        // Refoloseste token-ul din cookie daca exista deja
        $token = $_COOKIE['csrf_token'] ?? '';

        if (empty($token)) {
            $token = bin2hex(random_bytes(Constants::CSRF_TOKEN_LENGTH));

            // Seteaza cookie non-HttpOnly (JS trebuie sa il poata trimite ca header)
            // SameSite=Strict previne trimiterea de pe alte origini
            setcookie('csrf_token', $token, [
                'expires'  => 0,        // Session cookie (dispare la inchiderea browserului)
                'path'     => '/',
                'samesite' => 'Strict',
                'httponly' => false,    // Accesibil JS intentionat — necesar pentru double-submit
                // Secure automat cand serverul ruleaza pe HTTPS (in dev/localhost ramane false)
                'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            ]);

            // Actualizam superglobalul pentru cererea curenta
            $_COOKIE['csrf_token'] = $token;
        }

        return $token;
    }

    /**
     * Valideaza un token CSRF folosind double-submit cookie pattern.
     * Compara valoarea din cookie-ul de request cu headerul X-CSRF-Token.
     * Nu depinde de sesiunea PHP.
     *
     * @param  string|null $token Valoarea din headerul X-CSRF-Token
     * @return bool True daca token-ul este valid
     */
    public static function validateCsrfToken(?string $token): bool
    {
        if (empty($token)) {
            return false;
        }
        $cookieToken = $_COOKIE['csrf_token'] ?? '';
        if (empty($cookieToken)) {
            return false;
        }
        return hash_equals($cookieToken, $token);
    }

    public static function sanitizeInput(string $input): string
    {
        return trim(strip_tags($input));
    }

    public static function generateInviteToken(): string
    {
        return bin2hex(random_bytes(Constants::INVITE_TOKEN_LENGTH));
    }

    /** Token public, neghicibil, pentru URL-ul de partajare al unui moment. */
    public static function generateShareToken(): string
    {
        return bin2hex(random_bytes(Constants::SHARE_TOKEN_LENGTH));
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