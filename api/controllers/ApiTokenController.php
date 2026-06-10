<?php
/**
 * Controller pentru gestionarea token-urilor Bearer (REST API v1).
 *
 * Endpoint-uri:
 *   POST   /api/v1/auth/token   — emite un token nou pe baza email + parola
 *   DELETE /api/v1/auth/token   — revoca toate token-urile userului curent
 *   GET    /api/v1/auth/tokens  — listeaza token-urile userului curent
 *
 * @author Romila Raluca
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\RateLimiter;
use App\Core\Response;
use App\Core\SessionManager;
use App\Models\ApiTokenModel;
use App\Models\UserModel;

class ApiTokenController extends Controller
{
    /**
     * POST /api/v1/auth/token
     * Autentifica userul cu email + parola si returneaza un Bearer token.
     *
     * Body JSON:
     *   email        string  (obligatoriu)
     *   password     string  (obligatoriu)
     *   name         string  Eticheta token-ului (optional, default "API Token")
     *   expires_days int     Zile de valabilitate (optional, default 30; 0 = fara expirare)
     *
     * @return void Raspuns JSON: { token, token_type, expires_in, user_id }
     */
    public function issue(array $params): void
    {
        $email    = trim((string) ($this->request->body['email']    ?? ''));
        $password = (string)       ($this->request->body['password'] ?? '');
        $name     = trim((string) ($this->request->body['name']     ?? 'API Token'));
        $days     = isset($this->request->body['expires_days'])
            ? (int) $this->request->body['expires_days']
            : 30;

        if ($email === '' || $password === '') {
            Response::error('email and password are required', 422);
        }

        // Rate limiting: acelasi mecanism ca la login (max 5 esecuri / 15 min)
        RateLimiter::check('token', $email);

        $userModel = new UserModel();
        $user      = $userModel->findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            RateLimiter::recordFailure('token', $email);
            Response::error('Invalid credentials', 401);
        }

        if (!empty($user['banned_at'])) {
            Response::error('Account is banned', 403);
        }

        RateLimiter::clear('token', $email);
        $model = new ApiTokenModel();
        $raw   = $model->create((int) $user['id'], $name, $days > 0 ? $days : null);

        Response::json([
            'token'      => $raw,
            'token_type' => 'Bearer',
            'expires_in' => $days > 0 ? $days * 86400 : null,
            'user_id'    => (int) $user['id'],
            'name'       => $name,
        ], 201);
    }

    /**
     * DELETE /api/v1/auth/token
     * Revoca toate token-urile Bearer ale utilizatorului autentificat.
     *
     * @return void Raspuns JSON: { success, message }
     */
    public function revoke(array $params): void
    {
        $this->requireAuth();
        (new ApiTokenModel())->revokeAll(SessionManager::userId());
        Response::json(['success' => true, 'message' => 'All tokens revoked']);
    }

    /**
     * GET /api/v1/auth/tokens
     * Returneaza lista token-urilor Bearer ale utilizatorului curent.
     * Nu include token_hash — nu se expune niciodata.
     *
     * @return void Raspuns JSON: array de token-uri
     */
    public function list(array $params): void
    {
        $this->requireAuth();
        $tokens = (new ApiTokenModel())->listForUser(SessionManager::userId());
        Response::json($tokens);
    }
}
