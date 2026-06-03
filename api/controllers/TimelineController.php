<?php
/**
 * @author Romila Raluca
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Models\MomentModel;
use App\Models\CommentModel;
use App\Models\ReactionModel;

class TimelineController extends Controller
{
    public function index(array $params): void
    {
        $this->requireAuth();
        $childId = (int) ($params['id'] ?? 0);
        $this->requireFamilyAccess($childId);

        $query = $this->request->query;
        $type = $query['type'] ?? null;
        $limit = (int) ($query['limit'] ?? 50);
        $offset = (int) ($query['offset'] ?? 0);

        $model = new MomentModel();
        $moments = $model->getByChild($childId, $type, $limit, $offset);

        $commentModel = new CommentModel();
        $reactionModel = new ReactionModel();

        foreach ($moments as &$moment) {
            $moment['comments'] = $commentModel->countByMoment((int) $moment['id']);
            $moment['reactions'] = $reactionModel->getByMoment((int) $moment['id']);
        }

        Response::json($moments);
    }

    public function feed(array $params): void
    {
        $this->requireAuth();
        $childId = (int) ($params['id'] ?? 0);
        $this->requireFamilyAccess($childId);

        $model = new MomentModel();
        $moments = $model->getByChild($childId, null, 20, 0);

        $grouped = [];
        foreach ($moments as $moment) {
            $month = date('F Y', strtotime($moment['happened_at']));
            if (!isset($grouped[$month])) {
                $grouped[$month] = [];
            }
            $grouped[$month][] = $moment;
        }

        Response::json($grouped);
    }
}