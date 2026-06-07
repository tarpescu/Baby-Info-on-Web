<?php
/**
 * Feed RSS public cu momentele partajate (is_shared = 1) ale unui copil.
 * @author Tarpescu Sergiu
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Config\Constants;
use App\Models\ChildModel;
use App\Models\MomentModel;
use App\Services\RssService;

class RssController extends Controller
{
    public function feed(array $params): void
    {
        $childId = (int) ($params['child_id'] ?? 0);

        $childModel = new ChildModel();
        $child = $childModel->findById($childId);

        if (!$child) {
            Response::error('Child not found', 404);
        }

        $momentModel = new MomentModel();
        $moments = $momentModel->getShared($childId, Constants::RSS_ITEMS_LIMIT);

        $xml = (new RssService())->build($child, $moments);

        Response::xml($xml);
    }
}
