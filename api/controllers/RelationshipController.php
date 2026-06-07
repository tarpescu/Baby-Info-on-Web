<?php
/**
 * CRUD pentru cercul social al copilului (relationships).
 * @author Tarpescu Sergiu
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Core\SessionManager;
use App\Models\RelationshipModel;

class RelationshipController extends Controller
{
    private const GROUP_TYPES = ['family', 'daycare', 'friends', 'other'];

    public function index(array $params): void
    {
        $this->requireAuth();
        $childId = (int) ($params['id'] ?? 0);
        $this->requireFamilyAccess($childId);

        $model = new RelationshipModel();
        Response::json($model->getByChild($childId));
    }

    public function store(array $params): void
    {
        $this->requireAuth();
        $childId = (int) ($params['id'] ?? 0);
        $this->requireWritePermission($childId);

        $body = $this->request->body;
        if (empty($body['name']) || empty($body['relationship'])) {
            Response::error('Name and relationship are required', 400);
        }
        $groupType = $this->validGroupType($body['group_type'] ?? 'friends');

        $model = new RelationshipModel();
        $id = $model->create([
            'child_id'     => $childId,
            'name'         => $body['name'],
            'relationship' => $body['relationship'],
            'group_type'   => $groupType,
            'age_years'    => $this->intOrNull($body['age_years'] ?? null),
            'notes'        => $body['notes'] ?? null,
            'avatar_color' => $body['avatar_color'] ?? 'c1',
            'added_by'     => SessionManager::userId(),
        ]);

        Response::json(['id' => $id, 'message' => 'Relationship added'], 201);
    }

    public function update(array $params): void
    {
        $this->requireAuth();
        $id = (int) ($params['id'] ?? 0);

        $model = new RelationshipModel();
        $relationship = $model->findById($id);
        if (!$relationship) {
            Response::error('Relationship not found', 404);
        }
        $this->requireWritePermission((int) $relationship['child_id']);

        $body = $this->request->body;
        if (empty($body['name']) || empty($body['relationship'])) {
            Response::error('Name and relationship are required', 400);
        }

        $model->update($id, [
            'name'         => $body['name'],
            'relationship' => $body['relationship'],
            'group_type'   => $this->validGroupType($body['group_type'] ?? $relationship['group_type']),
            'age_years'    => $this->intOrNull($body['age_years'] ?? null),
            'notes'        => $body['notes'] ?? null,
            'avatar_color' => $body['avatar_color'] ?? ($relationship['avatar_color'] ?? 'c1'),
        ]);

        Response::json(['message' => 'Relationship updated']);
    }

    public function destroy(array $params): void
    {
        $this->requireAuth();
        $id = (int) ($params['id'] ?? 0);

        $model = new RelationshipModel();
        $relationship = $model->findById($id);
        if (!$relationship) {
            Response::error('Relationship not found', 404);
        }
        $this->requireWritePermission((int) $relationship['child_id']);

        $model->delete($id);
        Response::json(['message' => 'Relationship deleted']);
    }

    private function validGroupType(string $value): string
    {
        if (!in_array($value, self::GROUP_TYPES, true)) {
            Response::error('Invalid group_type (family, daycare, friends, other)', 400);
        }
        return $value;
    }

    private function intOrNull(mixed $value): ?int
    {
        return ($value === null || $value === '') ? null : (int) $value;
    }
}
