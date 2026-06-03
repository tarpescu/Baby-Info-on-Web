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
    public function store(array $params): void
    {
        $this->requireAuth();
        $childId = (int) ($params['id'] ?? 0);
        $this->requireWritePermission($childId);

        $body = $this->request->body;

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