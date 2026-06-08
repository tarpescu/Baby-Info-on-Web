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
use App\Models\FamilyModel;
use App\Models\InviteModel;
use App\Models\UserModel;

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

        // Cele 3 raspunsuri de securitate (folosite la resetarea parolei)
        $answers = [
            $body['security_answer_1'] ?? '',
            $body['security_answer_2'] ?? '',
            $body['security_answer_3'] ?? '',
        ];
        foreach ($answers as $a) {
            if (trim((string) $a) === '') {
                Response::error('All 3 security answers are required', 400);
            }
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
            'security_answers' => $answers,
        ]);

        // Daca avem invitatie valida, adaugam userul la TOTI copiii familiei invitatorului,
        // nu doar la copilul specificat in invitatie.
        if ($invite) {
            $familyModel  = new FamilyModel();
            $invitedBy    = (int) $invite['invited_by'];
            $permission   = $invite['permission'];

            // Gasim toti copiii la care invitatorul are acces
            $familyChildIds = $familyModel->getChildIdsByUser($invitedBy);

            // Adaugam noul user la fiecare copil din familie
            foreach ($familyChildIds as $childId) {
                $familyModel->addMember((int) $childId, $userId, $permission);
            }

            // Daca cumva invitatorul nu are niciun copil (caz exceptional), folosim child_id din invitatie
            if (empty($familyChildIds)) {
                $familyModel->addMember((int) $invite['child_id'], $userId, $permission);
            }

            $inviteModel = new InviteModel();
            $inviteModel->markUsed((int) $invite['id'], $userId);
        }

        Response::json(['id' => $userId, 'message' => 'Account created'], 201);
    }

    /**
     * Resetare parola pe baza de intrebari de securitate (un singur pas):
     *   {email, security_answer_1, security_answer_2, security_answer_3, new_password}
     *
     * Verifica emailul + cele 3 raspunsuri, apoi seteaza parola noua. Mesajul de
     * eroare e generic (nu dezvaluie daca a gresit emailul sau raspunsurile).
     */
    public function reset(array $params): void
    {
        $body = $this->request->body;
        $email = strtolower(trim((string) ($body['email'] ?? '')));
        $newPassword = (string) ($body['new_password'] ?? '');
        $answers = [
            $body['security_answer_1'] ?? '',
            $body['security_answer_2'] ?? '',
            $body['security_answer_3'] ?? '',
        ];

        if ($email === '') {
            Response::error('Email required', 400);
        }
        foreach ($answers as $a) {
            if (trim((string) $a) === '') {
                Response::error('All 3 security answers are required', 400);
            }
        }
        if (strlen($newPassword) < 6) {
            Response::error('Password must be at least 6 characters', 400);
        }

        $userModel = new UserModel();
        $user = $userModel->findByEmail($email);

        // Mesaj generic atat pentru email gresit cat si pentru raspunsuri gresite.
        if (!$user || !$userModel->verifySecurityAnswers((int) $user['id'], $answers)) {
            Response::error('Email or security answers are incorrect', 400);
        }

        $userModel->updatePassword((int) $user['id'], $newPassword);
        Response::json(['message' => 'Password has been reset']);
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