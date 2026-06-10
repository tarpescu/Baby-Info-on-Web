<?php
/**
 * Rate limiting pentru actiunile de autentificare (login, reset parola,
 * emitere token API). Previne brute-force pe parole si pe raspunsurile
 * de securitate.
 *
 * Functionare: fiecare incercare ESUATA se inregistreaza in tabela
 * auth_attempts (actiune + email + IP). Daca in fereastra de timp exista
 * deja MAX_ATTEMPTS esecuri pentru acelasi email SAU acelasi IP, cererea
 * e respinsa cu 429 inainte de a verifica parola. La autentificare
 * reusita, esecurile pentru acel email se sterg.
 *
 * @author Romila Raluca
 */

declare(strict_types=1);

namespace App\Core;

use App\Config\Database;

class RateLimiter
{
    /** Numarul maxim de incercari esuate in fereastra de timp. */
    private const MAX_ATTEMPTS = 5;

    /** Fereastra de timp, in minute. */
    private const WINDOW_MINUTES = 15;

    /**
     * Verifica daca actiunea este blocata pentru email-ul sau IP-ul curent.
     * Trimite direct raspuns 429 si opreste executia daca limita e atinsa.
     *
     * @param string $action     Tipul actiunii: 'login' | 'reset' | 'token'
     * @param string $identifier Email-ul incercat
     * @return void
     */
    public static function check(string $action, string $identifier): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM auth_attempts
            WHERE action = :action
              AND (identifier = :identifier OR ip = :ip)
              AND attempted_at > NOW() - INTERVAL '" . self::WINDOW_MINUTES . " minutes'
        ");
        $stmt->execute([
            ':action'     => $action,
            ':identifier' => mb_strtolower(trim($identifier)),
            ':ip'         => self::ip(),
        ]);

        if ((int) $stmt->fetchColumn() >= self::MAX_ATTEMPTS) {
            Response::error(
                'Too many attempts. Please try again in ' . self::WINDOW_MINUTES . ' minutes.',
                429
            );
        }
    }

    /**
     * Inregistreaza o incercare ESUATA (parola gresita, raspunsuri gresite etc.).
     *
     * @param string $action     Tipul actiunii: 'login' | 'reset' | 'token'
     * @param string $identifier Email-ul incercat
     * @return void
     */
    public static function recordFailure(string $action, string $identifier): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO auth_attempts (action, identifier, ip)
            VALUES (:action, :identifier, :ip)
        ");
        $stmt->execute([
            ':action'     => $action,
            ':identifier' => mb_strtolower(trim($identifier)),
            ':ip'         => self::ip(),
        ]);
    }

    /**
     * Sterge esecurile inregistrate pentru un email dupa o autentificare
     * reusita (contorul se reseteaza).
     *
     * @param string $action     Tipul actiunii
     * @param string $identifier Email-ul autentificat cu succes
     * @return void
     */
    public static function clear(string $action, string $identifier): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            DELETE FROM auth_attempts
            WHERE action = :action AND identifier = :identifier
        ");
        $stmt->execute([
            ':action'     => $action,
            ':identifier' => mb_strtolower(trim($identifier)),
        ]);
    }

    /**
     * IP-ul clientului curent.
     *
     * @return string
     */
    private static function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '';
    }
}
