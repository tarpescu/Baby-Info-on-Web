<?php
/**
 * @author Romila Raluca
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class SleepModel extends Model
{
    public function getByChild(int $childId, int $limit = 50): array
    {
        $stmt = $this->db->prepare("
            SELECT s.*, u.first_name, u.last_name
            FROM sleep_logs s
            JOIN users u ON s.logged_by = u.id
            WHERE s.child_id = :child_id
            ORDER BY s.started_at DESC
            LIMIT :limit
        ");
        $stmt->execute([':child_id' => $childId, ':limit' => $limit]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO sleep_logs (child_id, logged_by, type, started_at, ended_at, quality, notes)
            VALUES (:child_id, :logged_by, :type, :started_at, :ended_at, :quality, :notes)
            RETURNING id
        ");
        $stmt->execute([
            ':child_id' => $data['child_id'],
            ':logged_by' => $data['logged_by'],
            ':type' => $data['type'],
            ':started_at' => $data['started_at'],
            ':ended_at' => $data['ended_at'] ?? null,
            ':quality' => $data['quality'] ?? null,
            ':notes' => $data['notes'] ?? null,
        ]);
        return (int) $stmt->fetchColumn();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM sleep_logs WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}