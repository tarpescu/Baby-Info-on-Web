<?php
/**
 * @author Romila Raluca
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Core\Security;

class CommentModel extends Model
{
    public function getByMoment(int $momentId): array
    {
        $stmt = $this->db->prepare("
            SELECT c.*, u.first_name, u.last_name, u.avatar_color
            FROM comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.moment_id = :moment_id
            ORDER BY c.created_at ASC
        ");
        $stmt->execute([':moment_id' => $momentId]);
        return $stmt->fetchAll();
    }

    public function create(int $momentId, int $userId, string $body): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO comments (moment_id, user_id, body)
            VALUES (:moment_id, :user_id, :body)
            RETURNING id
        ");
        $stmt->execute([
            ':moment_id' => $momentId,
            ':user_id' => $userId,
            ':body' => Security::sanitizeInput($body),
        ]);
        return (int) $stmt->fetchColumn();
    }

    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare("
            DELETE FROM comments WHERE id = :id AND user_id = :user_id
        ");
        return $stmt->execute([':id' => $id, ':user_id' => $userId]);
    }

    public function countByMoment(int $momentId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM comments WHERE moment_id = :moment_id
        ");
        $stmt->execute([':moment_id' => $momentId]);
        return (int) $stmt->fetchColumn();
    }
}