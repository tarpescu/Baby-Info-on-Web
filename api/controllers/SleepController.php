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
    /**
     * Returneaza istoricul de somn al unui copil.
     * Suporta parametrul query ?limit=N (default 20).
     */
    public function index(array $params): void
    {
        $this->requireAuth();
        $childId = (int) ($params['id'] ?? 0);
        $this->requireFamilyAccess($childId);

        $limit = (int) ($this->request->query['limit'] ?? 20);
        $model = new SleepModel();
        Response::json($model->getByChild($childId, $limit));
    }

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