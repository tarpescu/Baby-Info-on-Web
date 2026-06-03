<?php
/**
 * @author Romila Raluca
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Config\Constants;
use App\Core\Controller;
use App\Core\Response;
use App\Core\Security;
use App\Core\SessionManager;
use App\Models\InviteModel;
use App\Models\FamilyModel;

class InviteController extends Controller
{
    public function store(array $params): void
    {
        $this->requireAuth();
        $childId = (int) ($params['id'] ?? 0);
        $this->requireWritePermission($childId);

        $body = $this->request->body;
        $email = $body['email'] ?? null;
        $permission = $body['permission'] ?? 'viewer';

        $valid = ['owner', 'coparent', 'caregiver', 'viewer'];
        if (!in_array($permission, $valid, true)) {
            Response::error('Invalid permission', 400);
        }

        $token = Security::generateInviteToken();
        $expires = date('Y-m-d H:i:s', strtotime('+' . Constants::INVITE_EXPIRY_HOURS . ' hours'));

        $model = new InviteModel();
        $model->create([
            'child_id' => $childId,
            'invited_by' => SessionManager::userId(),
            'token' => $token,
            'email' => $email,
            'permission' => $permission,
            'expires_at' => $expires,
        ]);

        Response::json([
            'token' => $token,
            'link' => '/invite?token=' . $token,
            'expires_at' => $expires,
        ], 201);
    }

    public function validate(array $params): void
    {
        $token = $this->request->query['token'] ?? '';
        if (empty($token)) {
            Response::error('Token required', 400);
        }

        $model = new InviteModel();
        $invite = $model->findByToken($token);

        if (!$invite) {
            Response::error('Invalid or expired token', 404);
        }

        if (strtotime($invite['expires_at']) < time()) {
            Response::error('Token expired', 410);
        }

        Response::json([
            'child_id' => $invite['child_id'],
            'child_name' => $invite['child_first'] . ' ' . $invite['child_last'],
            'permission' => $invite['permission'],
            'expires_at' => $invite['expires_at'],
        ]);
    }
}