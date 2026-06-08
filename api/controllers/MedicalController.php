<?php
/**
 * @author Romila Raluca
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Core\SessionManager;
use App\Models\MedicalModel;

class MedicalController extends Controller
{
    public function index(array $params): void
    {
        $this->requireAuth();
        $childId = (int) ($params['id'] ?? 0);
        $this->requireFamilyAccess($childId);

        $limit = (int) ($this->request->query['limit'] ?? 50);

        $model = new MedicalModel();
        Response::json($model->getByChild($childId, $limit));
    }

    public function store(array $params): void
    {
        $this->requireAuth();
        $childId = (int) ($params['id'] ?? 0);
        $this->requireWritePermission($childId);

        $body = $this->request->body;
        if (empty($body['type']) || empty($body['title']) || empty($body['date_at'])) {
            Response::error('Type, title and date are required', 400);
        }

        $model = new MedicalModel();
        $id = $model->create([
            'child_id' => $childId,
            'logged_by' => SessionManager::userId(),
            'type' => $body['type'],
            'title' => $body['title'],
            'description' => $body['description'] ?? null,
            'doctor_name' => $body['doctor_name'] ?? null,
            'clinic_name' => $body['clinic_name'] ?? null,
            'date_at' => $body['date_at'],
            'next_date' => $body['next_date'] ?? null,
        ]);

        Response::json(['id' => $id, 'message' => 'Medical record logged'], 201);
    }
}