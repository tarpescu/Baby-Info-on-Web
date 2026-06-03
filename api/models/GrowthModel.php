<?php
/**
 * @author Romila Raluca
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class GrowthModel extends Model
{
    public function getByChild(int $childId): array
    {
        $stmt = $this->db->prepare("
            SELECT g.*, u.first_name, u.last_name
            FROM growth g
            JOIN users u ON g.logged_by = u.id
            WHERE g.child_id = :child_id
            ORDER BY g.measured_at DESC
        ");
        $stmt->execute([':child_id' => $childId]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO growth (child_id, logged_by, weight_kg, height_cm, head_cm, measured_at)
            VALUES (:child_id, :logged_by, :weight_kg, :height_cm, :head_cm, :measured_at)
            RETURNING id
        ");
        $stmt->execute([
            ':child_id' => $data['child_id'],
            ':logged_by' => $data['logged_by'],
            ':weight_kg' => $data['weight_kg'] ?? null,
            ':height_cm' => $data['height_cm'] ?? null,
            ':head_cm' => $data['head_cm'] ?? null,
            ':measured_at' => $data['measured_at'] ?? date('Y-m-d H:i:s'),
        ]);
        return (int) $stmt->fetchColumn();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM growth WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}