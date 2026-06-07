<?php
/**
 * Jurnal de interactiuni intre copil si o persoana din cercul social (tabela interactions).
 * @author Tarpescu Sergiu
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Core\Security;

class InteractionModel extends Model
{
    /** Toate interactiunile unui copil (jurnal complet), cu numele persoanei. */
    public function getByChild(int $childId, int $limit = 50): array
    {
        $stmt = $this->db->prepare("
            SELECT i.*,
                   r.name AS relationship_name, r.relationship AS relationship_type,
                   r.group_type, m.title AS moment_title
            FROM interactions i
            JOIN relationships r ON i.relationship_id = r.id
            LEFT JOIN moments m ON i.moment_id = m.id
            WHERE i.child_id = :child_id
            ORDER BY i.interacted_at DESC
            LIMIT :limit
        ");
        $stmt->execute([':child_id' => $childId, ':limit' => $limit]);
        return $stmt->fetchAll();
    }

    /** Interactiunile unei singure persoane. */
    public function getByRelationship(int $relationshipId): array
    {
        $stmt = $this->db->prepare("
            SELECT i.*, m.title AS moment_title
            FROM interactions i
            LEFT JOIN moments m ON i.moment_id = m.id
            WHERE i.relationship_id = :relationship_id
            ORDER BY i.interacted_at DESC
        ");
        $stmt->execute([':relationship_id' => $relationshipId]);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM interactions WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO interactions (child_id, relationship_id, moment_id, description, interacted_at)
            VALUES (:child_id, :relationship_id, :moment_id, :description, :interacted_at)
            RETURNING id
        ");
        $stmt->execute([
            ':child_id'        => $data['child_id'],
            ':relationship_id' => $data['relationship_id'],
            ':moment_id'       => $data['moment_id'] ?? null,
            ':description'     => isset($data['description']) ? Security::sanitizeInput($data['description']) : null,
            ':interacted_at'   => $data['interacted_at'] ?? date('Y-m-d H:i:s'),
        ]);
        return (int) $stmt->fetchColumn();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM interactions WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
