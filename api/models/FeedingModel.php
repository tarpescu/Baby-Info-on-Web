<?php
/**
 * @author Romila Raluca
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class FeedingModel extends Model
{
    public function getByChild(int $childId, int $limit = 50): array
    {
        $stmt = $this->db->prepare("
            SELECT f.*, u.first_name, u.last_name
            FROM feedings f
            JOIN users u ON f.logged_by = u.id
            WHERE f.child_id = :child_id
            ORDER BY f.fed_at DESC
            LIMIT :limit
        ");
        $stmt->execute([':child_id' => $childId, ':limit' => $limit]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO feedings (child_id, logged_by, type, side, duration_min, amount_ml, food_desc, notes, fed_at)
            VALUES (:child_id, :logged_by, :type, :side, :duration_min, :amount_ml, :food_desc, :notes, :fed_at)
            RETURNING id
        ");
        $stmt->execute([
            ':child_id' => $data['child_id'],
            ':logged_by' => $data['logged_by'],
            ':type' => $data['type'],
            ':side' => $data['side'] ?? null,
            ':duration_min' => $data['duration_min'] ?? null,
            ':amount_ml' => $data['amount_ml'] ?? null,
            ':food_desc' => $data['food_desc'] ?? null,
            ':notes' => $data['notes'] ?? null,
            ':fed_at' => $data['fed_at'] ?? date('Y-m-d H:i:s'),
        ]);
        return (int) $stmt->fetchColumn();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM feedings WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}