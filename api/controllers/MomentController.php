<?php
/**
 * @author Romila Raluca
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Core\SessionManager;
use App\Models\MomentModel;

class MomentController extends Controller
{
    public function store(array $params): void
    {
        $this->requireAuth();
        $childId = (int) ($params['id'] ?? 0);
        $this->requireWritePermission($childId);

        $body = $this->request->body;
        if (empty($body['title']) || empty($body['type'])) {
            Response::error('Title and type are required', 400);
        }

        $model = new MomentModel();
        $momentId = $model->create([
            'child_id' => $childId,
            'logged_by' => SessionManager::userId(),
            'type' => $body['type'],
            'title' => $body['title'],
            'body' => $body['body'] ?? '',
            'is_pinned' => $body['is_pinned'] ?? false,
            'is_shared' => $body['is_shared'] ?? false,
            'happened_at' => $body['happened_at'] ?? null,
        ]);

        Response::json(['id' => $momentId, 'message' => 'Moment logged'], 201);
    }

    public function destroy(array $params): void
    {
        $this->requireAuth();
        $momentId = (int) ($params['id'] ?? 0);

        $model = new MomentModel();
        $moment = $model->findById($momentId);

        if (!$moment) {
            Response::error('Moment not found', 404);
        }

        $this->requireWritePermission((int) $moment['child_id']);

        $model->delete($momentId);
        Response::noContent();
    }
}