<?php
/**
 * @author Romila Raluca
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Core\SessionManager;
use App\Models\CommentModel;
use App\Models\MomentModel;

class CommentController extends Controller
{
    public function index(array $params): void
    {
        $this->requireAuth();
        $momentId = (int) ($params['id'] ?? 0);

        $momentModel = new MomentModel();
        $moment = $momentModel->findById($momentId);

        if (!$moment) {
            Response::error('Moment not found', 404);
        }

        $this->requireFamilyAccess((int) $moment['child_id']);

        $model = new CommentModel();
        $comments = $model->getByMoment($momentId);

        Response::json($comments);
    }

    public function store(array $params): void
    {
        $this->requireAuth();
        $momentId = (int) ($params['id'] ?? 0);

        $momentModel = new MomentModel();
        $moment = $momentModel->findById($momentId);

        if (!$moment) {
            Response::error('Moment not found', 404);
        }

        $this->requireFamilyAccess((int) $moment['child_id']);

        $body = $this->request->body;
        if (empty($body['body'])) {
            Response::error('Comment body is required', 400);
        }

        $model = new CommentModel();
        $commentId = $model->create($momentId, SessionManager::userId(), $body['body']);

        Response::json([
            'id' => $commentId,
            'message' => 'Comment added',
        ], 201);
    }
}