<?php
/**
 * Middleware de autentificare.
 * Suporta doua mecanisme:
 *   1. Session PHP  — folosit de frontend (cookie de sesiune)
 *   2. Bearer token — folosit de REST API v1 (header Authorization: Bearer <token>)
 *
 * resolveBearer() este apelata o singura data la inceputul fiecarui request
 * (din Router::dispatch). Daca token-ul este valid, populeaza $bearerUser,
 * iar SessionManager::isAuthenticated() / userId() / isSuperAdmin() il citesc
 * transparent — controllere-le existente functioneaza fara modificare.
 *
 * @author Romila Raluca
 */

declare(strict_types=1);

namespace App\Core;

use App\Models\ApiTokenModel;

class AuthMiddleware
{
    /**
     * Date despre userul autentificat via Bearer token.
     * null daca request-ul nu contine un token valid.
     *
     * @var array{user_id: int, is_superadmin: bool}|null
     */
    private static ?array $bearerUser = null;

    /**
     * Citeste headerul "Authorization: Bearer <token>", valideaza token-ul
     * din baza de date si populeaza $bearerUser.
     * Apelata automat de Router::dispatch() inainte de orice controller.
     *
     * @return void
     */
    public static function resolveBearer(): void
    {
        // Compatibilitate Apache / Nginx / FastCGI
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if ($header === '' && function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $header  = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }

        if (!str_starts_with($header, 'Bearer ')) {
            return;
        }

        $raw = trim(substr($header, 7));
        if ($raw === '') {
            return;
        }
        $hash = hash('sha256', $raw);

        $model = new ApiTokenModel();
        $token = $model->findByHash($hash);

        if (!$token)                                      return;
        if ((bool) $token['revoked'])                    return;
        if (!empty($token['banned_at']))                 return;
        if (
            $token['expires_at'] !== null &&
            strtotime((string) $token['expires_at']) < time()
        )                                                 return;

        self::$bearerUser = [
            'user_id'      => (int)  $token['user_id'],
            'is_superadmin' => (bool) $token['is_superadmin'],
        ];

        $model->touch((int) $token['id']);
    }

    /**
     * Returneaza user_id din Bearer token, sau null daca nu exista.
     *
     * @return int|null
     */
    public static function bearerUserId(): ?int
    {
        return self::$bearerUser['user_id'] ?? null;
    }

    /**
     * Returneaza true daca request-ul contine un Bearer token valid.
     *
     * @return bool
     */
    public static function hasBearerAuth(): bool
    {
        return self::$bearerUser !== null;
    }

    /**
     * Returneaza is_superadmin din Bearer token.
     *
     * @return bool
     */
    public static function bearerIsSuperAdmin(): bool
    {
        return (bool) (self::$bearerUser['is_superadmin'] ?? false);
    }

    /**
     * Verifica ca userul este autentificat (sesiune SAU Bearer token).
     * Trimite 401 daca nu.
     *
     * @return void
     */
    public static function check(): void
    {
        if (!SessionManager::isAuthenticated()) {
            Response::error('Unauthorized', 401);
        }
    }

    /**
     * Verifica ca userul este autentificat SI este super-admin.
     * Trimite 401 sau 403 dupa caz.
     *
     * @return void
     */
    public static function checkSuperAdmin(): void
    {
        self::check();
        if (!SessionManager::isSuperAdmin()) {
            Response::error('Forbidden', 403);
        }
    }
}
