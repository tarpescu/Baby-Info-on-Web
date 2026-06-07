<?php
/**
 * Jurnal de interactiuni cu persoanele din cercul social al copilului.
 * Listare sub copil (jurnal complet) sau sub relatie; creare sub relatie.
 * @author Tarpescu Sergiu
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Models\InteractionModel;
use App\Models\RelationshipModel;

class InteractionController extends Controller
{
    /** GET /api/children/{id}/interactions — jurnalul complet al copilului. */
    public function index(array $params): void
    {
        $this->requireAuth();
        $childId = (int) ($params['id'] ?? 0);
        $this->requireFamilyAccess($childId);

        $limit = (int) ($this->request->query['limit'] ?? 50);
        $model = new InteractionModel();
        Response::json($model->getByChild($childId, $limit));
    }

    /** GET /api/relationships/{id}/interactions — interactiunile unei persoane. */
    public function byRelationship(array $params): void
    {
        $this->requireAuth();
        $relationshipId = (int) ($params['id'] ?? 0);

        $relationship = (new RelationshipModel())->findById($relationshipId);
        if (!$relationship) {
            Response::error('Relationship not found', 404);
        }
        $this->requireFamilyAccess((int) $relationship['child_id']);

        $model = new InteractionModel();
        Response::json($model->getByRelationship($relationshipId));
    }

    /** POST /api/relationships/{id}/interactions — child_id dedus din relatie. */
    public function store(array $params): void
    {
        $this->requireAuth();
        $relationshipId = (int) ($params['id'] ?? 0);

        $relationship = (new RelationshipModel())->findById($relationshipId);
        if (!$relationship) {
            Response::error('Relationship not found', 404);
        }
        $childId = (int) $relationship['child_id'];
        $this->requireWritePermission($childId);

        $body = $this->request->body;
        $model = new InteractionModel();
        $id = $model->create([
            'child_id'        => $childId,
            'relationship_id' => $relationshipId,
            'moment_id'       => $this->intOrNull($body['moment_id'] ?? null),
            'description'     => $body['description'] ?? null,
            'interacted_at'   => $body['interacted_at'] ?? null,
        ]);

        Response::json(['id' => $id, 'message' => 'Interaction logged'], 201);
    }

    /** DELETE /api/interactions/{id} */
    public function destroy(array $params): void
    {
        $this->requireAuth();
        $id = (int) ($params['id'] ?? 0);

        $model = new InteractionModel();
        $interaction = $model->findById($id);
        if (!$interaction) {
            Response::error('Interaction not found', 404);
        }
        $this->requireWritePermission((int) $interaction['child_id']);

        $model->delete($id);
        Response::json(['message' => 'Interaction deleted']);
    }

    private function intOrNull(mixed $value): ?int
    {
        return ($value === null || $value === '') ? null : (int) $value;
    }
}
