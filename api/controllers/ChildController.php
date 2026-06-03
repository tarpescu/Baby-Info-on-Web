<?php
/**
 * @author Romila Raluca
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Core\SessionManager;
use App\Models\ChildModel;

class ChildController extends Controller
{
    public function index(array $params): void
    {
        $this->requireAuth();
        $userId = SessionManager::userId();

        $model = new ChildModel();
        $children = $model->getByUser($userId);

        Response::json($children);
    }

    public function show(array $params): void
    {
        $this->requireAuth();
        $childId = (int) ($params['id'] ?? 0);
        $this->requireFamilyAccess($childId);

        $model = new ChildModel();
        $child = $model->findById($childId);

        if (!$child) {
            Response::error('Child not found', 404);
        }

        Response::json($child);
    }

    public function store(array $params): void
    {
        $this->requireAuth();
        $body = $this->request->body;

        $required = ['first_name', 'date_of_birth'];
        foreach ($required as $field) {
            if (empty($body[$field])) {
                Response::error("Field {$field} is required", 400);
            }
        }

        $userId = SessionManager::userId();
        $model = new ChildModel();

        $childId = $model->create([
            'first_name' => $body['first_name'],
            'last_name' => $body['last_name'] ?? '',
            'date_of_birth' => $body['date_of_birth'],
            'gender' => $body['gender'] ?? null,
            'blood_type' => $body['blood_type'] ?? null,
            'notes' => $body['notes'] ?? '',
            'created_by' => $userId,
        ]);

        $model->addFamilyMember($childId, $userId, 'owner');

        Response::json(['id' => $childId, 'message' => 'Child profile created'], 201);
    }

    public function update(array $params): void
    {
        $this->requireAuth();
        $childId = (int) ($params['id'] ?? 0);
        $this->requireWritePermission($childId);

        $body = $this->request->body;
        $model = new ChildModel();

        $success = $model->update($childId, $body);

        if (!$success) {
            Response::error('Update failed', 500);
        }

        Response::json(['message' => 'Child profile updated']);
    }

    public function destroy(array $params): void
    {
        $this->requireAuth();
        $childId = (int) ($params['id'] ?? 0);
        $this->requireWritePermission($childId);

        $model = new ChildModel();
        $model->delete($childId);

        Response::noContent();
    }
}