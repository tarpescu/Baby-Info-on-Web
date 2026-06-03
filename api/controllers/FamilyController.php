<?php
/**
 * @author Romila Raluca
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Core\SessionManager;
use App\Models\FamilyModel;

class FamilyController extends Controller
{
    public function index(array $params): void
    {
        $this->requireAuth();
        $childId = (int) ($params['id'] ?? 0);
        $this->requireFamilyAccess($childId);

        $model = new FamilyModel();
        $members = $model->getMembers($childId);

        Response::json($members);
    }

    public function updatePermission(array $params): void
    {
        $this->requireAuth();
        $childId = (int) ($params['id'] ?? 0);
        $this->requireWritePermission($childId);

        $body = $this->request->body;
        $userId = (int) ($body['user_id'] ?? 0);
        $permission = $body['permission'] ?? '';

        if (!$userId || empty($permission)) {
            Response::error('user_id and permission required', 400);
        }

        $model = new FamilyModel();
        $success = $model->updatePermission($childId, $userId, $permission);

        if (!$success) {
            Response::error('Update failed', 500);
        }

        Response::json(['message' => 'Permission updated']);
    }

    public function removeMember(array $params): void
    {
        $this->requireAuth();
        $childId = (int) ($params['id'] ?? 0);
        $this->requireWritePermission($childId);

        $body = $this->request->body;
        $userId = (int) ($body['user_id'] ?? 0);

        if (!$userId) {
            Response::error('user_id required', 400);
        }

        $currentUserId = SessionManager::userId();
        if ($userId === $currentUserId) {
            Response::error('Cannot remove yourself', 400);
        }

        $model = new FamilyModel();
        $model->removeMember($childId, $userId);

        Response::noContent();
    }
}