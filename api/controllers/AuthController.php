<?php
/**
 * @author Romila Raluca
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Core\Security;
use App\Core\SessionManager;
use App\Config\Constants;
use App\Models\FamilyModel;
use App\Models\InviteModel;
use App\Models\UserModel;
use App\Models\PasswordResetModel;

class AuthController extends Controller
{
    public function login(array $params): void
    {
        $body = $this->request->body;
        $email = $body['email'] ?? '';
        $password = $body['password'] ?? '';

        if (empty($email) || empty($password)) {
            Response::error('Email and password required', 400);
        }

        $model = new UserModel();
        $user = $model->findByEmail($email);

        if (!$user) {
            Response::error('Invalid credentials', 401);
        }

        if ($user['banned_at'] !== null) {
            Response::error('Account suspended', 403);
        }

        if (!Security::verifyPassword($password, $user['password_hash'])) {
            Response::error('Invalid credentials', 401);
        }

        SessionManager::regenerate();
        SessionManager::set('user_id', $user['id']);
        SessionManager::set('first_name', $user['first_name']);
        SessionManager::set('is_superadmin', (bool) $user['is_superadmin']);
        SessionManager::set('theme', $user['theme']);

        Response::json([
            'id' => $user['id'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'is_superadmin' => (bool) $user['is_superadmin'],
            'theme' => $user['theme'],
            'avatar_color' => $user['avatar_color'],
        ]);
    }

    /**
     * Inregistreaza un user nou.
     * Daca body contine invite_token, valideaza invitația, asociaza userul cu familia
     * si marcheaza tokenul ca folosit.
     */
    public function register(array $params): void
    {
        $body = $this->request->body;
        $firstName   = $body['first_name'] ?? '';
        $lastName    = $body['last_name'] ?? '';
        $email       = $body['email'] ?? '';
        $password    = $body['password'] ?? '';
        $inviteToken = $body['invite_token'] ?? '';

        // Validare token de invitatie inainte de a crea contul
        $invite = null;
        if (!empty($inviteToken)) {
            $inviteModel = new InviteModel();
            $invite = $inviteModel->findByToken($inviteToken);
            if (!$invite) {
                Response::error('Invalid or expired invite link', 400);
            }
            if (strtotime($invite['expires_at']) < time()) {
                Response::error('Invite link has expired', 410);
            }
        }

        if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
            Response::error('All fields are required', 400);
        }

        if (strlen($password) < 6) {
            Response::error('Password must be at least 6 characters', 400);
        }

        $model = new UserModel();

        if ($model->emailExists($email)) {
            Response::error('Email already registered', 409);
        }

        $userId = $model->create([
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'email'      => $email,
            'password'   => $password,
            'role'       => 'viewer',
        ]);

        // Daca avem invitatie valida, adaugam userul in familia copilului
        if ($invite) {
            $familyModel = new FamilyModel();
            $familyModel->addMember((int) $invite['child_id'], $userId, $invite['permission']);
            $inviteModel = new InviteModel();
            $inviteModel->markUsed((int) $invite['id'], $userId);
        }

        Response::json(['id' => $userId, 'message' => 'Account created'], 201);
    }

    /**
     * Resetare parola intr-un singur endpoint, cu doua moduri determinate de body:
     *  - {email}                          -> genereaza un cod, il salveaza si il logheaza pe server
     *  - {email, code, new_password}      -> verifica codul valid si seteaza parola noua
     *
     * Raspunsurile sunt generice intentionat (nu dezvaluie daca emailul exista),
     * pentru a preveni enumerarea conturilor.
     */
    public function reset(array $params): void
    {
        $body = $this->request->body;
        $email = strtolower(trim((string) ($body['email'] ?? '')));
        $code = trim((string) ($body['code'] ?? ''));
        $newPassword = (string) ($body['new_password'] ?? '');

        if ($email === '') {
            Response::error('Email required', 400);
        }

        $userModel = new UserModel();
        $resetModel = new PasswordResetModel();
        $user = $userModel->findByEmail($email);

        // MOD 2: confirmare (avem cod + parola noua)
        if ($code !== '' && $newPassword !== '') {
            if (strlen($newPassword) < 6) {
                Response::error('Password must be at least 6 characters', 400);
            }

            $valid = $user ? $resetModel->findValid((int) $user['id'], $code) : null;
            if (!$valid) {
                Response::error('Invalid or expired code', 400);
            }

            $userModel->updatePassword((int) $user['id'], $newPassword);
            $resetModel->deleteForUser((int) $user['id']); // invalideaza toate codurile

            Response::json(['message' => 'Password has been reset']);
        }

        // MOD 1: cerere de cod
        if ($user) {
            $resetModel->deleteForUser((int) $user['id']); // pastram un singur cod activ
            $resetCode = (string) random_int(100000, 999999);
            $resetModel->create((int) $user['id'], $resetCode, Constants::RESET_EXPIRY_MINUTES);

            // Fara mailer: codul ajunge in log-ul serverului.
            error_log("[password-reset] user_id={$user['id']} email={$email} code={$resetCode} expira in " . Constants::RESET_EXPIRY_MINUTES . " min");
        }

        Response::json(['message' => 'If the email exists, a reset code has been generated']);
    }

    public function logout(array $params): void
    {
        SessionManager::destroy();
        Response::json(['message' => 'Logged out']);
    }

    public function me(array $params): void
    {
        $this->requireAuth();

        $userId = SessionManager::userId();
        $model = new UserModel();
        $user = $model->findById($userId);

        if (!$user) {
            Response::error('User not found', 404);
        }

        Response::json([
            'id' => $user['id'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'is_superadmin' => (bool) $user['is_superadmin'],
            'theme' => $user['theme'],
            'avatar_color' => $user['avatar_color'],
        ]);
    }
}