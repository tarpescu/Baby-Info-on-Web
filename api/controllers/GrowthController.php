<?php
/**
 * @author Romila Raluca
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Core\SessionManager;
use App\Models\GrowthModel;

class GrowthController extends Controller
{
    /**
     * Returneaza istoricul de crestere al unui copil.
     */
    public function index(array $params): void
    {
        $this->requireAuth();
        $childId = (int) ($params['id'] ?? 0);
        $this->requireFamilyAccess($childId);

        $model = new GrowthModel();
        Response::json($model->getByChild($childId));
    }

    public function store(array $params): void
    {
        $this->requireAuth();
        $this->requireCsrf();
        $childId = (int) ($params['id'] ?? 0);
        $this->requireWritePermission($childId);

        $body = $this->request->body;
        if (!empty($body['measured_at']) && strtotime($body['measured_at']) > time()) {
            Response::error('Data nu poate fi în viitor.', 400);
        }

        $model = new GrowthModel();
        $id = $model->create([
            'child_id' => $childId,
            'logged_by' => SessionManager::userId(),
            'weight_kg' => $body['weight_kg'] ?? null,
            'height_cm' => $body['height_cm'] ?? null,
            'head_cm' => $body['head_cm'] ?? null,
            'measured_at' => $body['measured_at'] ?? null,
        ]);

        Response::json(['id' => $id, 'message' => 'Growth logged'], 201);
    }
}