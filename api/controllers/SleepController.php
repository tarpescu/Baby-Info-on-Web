<?php
/**
 * @author Romila Raluca
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Core\SessionManager;
use App\Models\SleepModel;

class SleepController extends Controller
{
    public function store(array $params): void
    {
        $this->requireAuth();
        $childId = (int) ($params['id'] ?? 0);
        $this->requireWritePermission($childId);

        $body = $this->request->body;
        if (empty($body['started_at'])) {
            Response::error('Start time is required', 400);
        }

        $model = new SleepModel();
        $id = $model->create([
            'child_id' => $childId,
            'logged_by' => SessionManager::userId(),
            'type' => $body['type'] ?? 'night',
            'started_at' => $body['started_at'],
            'ended_at' => $body['ended_at'] ?? null,
            'quality' => $body['quality'] ?? null,
            'notes' => $body['notes'] ?? null,
        ]);

        Response::json(['id' => $id, 'message' => 'Sleep logged'], 201);
    }
}