<?php
/**
 * @author Romila Raluca
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Core\SessionManager;
use App\Models\ReactionModel;
use App\Models\MomentModel;

class ReactionController extends Controller
{
    private array $validEmojis = ['like', 'heart', 'smile', 'star', 'laugh'];

    public function store(array $params): void
    {
        $this->requireAuth();
        $this->requireCsrf();
        $momentId = (int) ($params['id'] ?? 0);

        $momentModel = new MomentModel();
        $moment = $momentModel->findById($momentId);

        if (!$moment) {
            Response::error('Moment not found', 404);
        }

        $this->requireFamilyAccess((int) $moment['child_id']);

        $body = $this->request->body;
        $emojiType = $body['emoji_type'] ?? '';

        if (!in_array($emojiType, $this->validEmojis, true)) {
            Response::error('Invalid emoji type', 400);
        }

        $model = new ReactionModel();
        $model->create($momentId, SessionManager::userId(), $emojiType);
        $model->updateMomentCount($momentId);

        Response::json(['message' => 'Reaction added'], 201);
    }

    public function destroy(array $params): void
    {
        $this->requireAuth();
        $this->requireCsrf();
        $momentId = (int) ($params['id'] ?? 0);

        $body = $this->request->body;
        $emojiType = $body['emoji_type'] ?? '';

        if (!in_array($emojiType, $this->validEmojis, true)) {
            Response::error('Invalid emoji type', 400);
        }

        $model = new ReactionModel();
        $model->delete($momentId, SessionManager::userId(), $emojiType);
        $model->updateMomentCount($momentId);

        Response::noContent();
    }
}