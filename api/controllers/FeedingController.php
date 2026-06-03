<?php
/**
 * @author Romila Raluca
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Core\SessionManager;
use App\Models\FeedingModel;

class FeedingController extends Controller
{
    public function store(array $params): void
    {
        $this->requireAuth();
        $childId = (int) ($params['id'] ?? 0);
        $this->requireWritePermission($childId);

        $body = $this->request->body;
        if (empty($body['type'])) {
            Response::error('Feeding type is required', 400);
        }

        $model = new FeedingModel();
        $id = $model->create([
            'child_id' => $childId,
            'logged_by' => SessionManager::userId(),
            'type' => $body['type'],
            'side' => $body['side'] ?? null,
            'duration_min' => $body['duration_min'] ?? null,
            'amount_ml' => $body['amount_ml'] ?? null,
            'food_desc' => $body['food_desc'] ?? null,
            'notes' => $body['notes'] ?? null,
            'fed_at' => $body['fed_at'] ?? null,
        ]);

        Response::json(['id' => $id, 'message' => 'Feeding logged'], 201);
    }
}