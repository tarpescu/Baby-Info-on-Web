<?php
/**
 * @author Romila Raluca
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class ReactionModel extends Model
{
    public function getByMoment(int $momentId): array
    {
        $stmt = $this->db->prepare("
            SELECT emoji_type, COUNT(*) as count
            FROM reactions
            WHERE moment_id = :moment_id
            GROUP BY emoji_type
        ");
        $stmt->execute([':moment_id' => $momentId]);
        return $stmt->fetchAll();
    }

    public function getUserReaction(int $momentId, int $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT emoji_type FROM reactions
            WHERE moment_id = :moment_id AND user_id = :user_id
            LIMIT 1
        ");
        $stmt->execute([':moment_id' => $momentId, ':user_id' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(int $momentId, int $userId, string $emojiType): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO reactions (moment_id, user_id, emoji_type)
            VALUES (:moment_id, :user_id, :emoji_type)
            ON CONFLICT (moment_id, user_id, emoji_type) DO NOTHING
        ");
        return $stmt->execute([
            ':moment_id' => $momentId,
            ':user_id' => $userId,
            ':emoji_type' => $emojiType,
        ]);
    }

    public function delete(int $momentId, int $userId, string $emojiType): bool
    {
        $stmt = $this->db->prepare("
            DELETE FROM reactions
            WHERE moment_id = :moment_id AND user_id = :user_id AND emoji_type = :emoji_type
        ");
        return $stmt->execute([
            ':moment_id' => $momentId,
            ':user_id' => $userId,
            ':emoji_type' => $emojiType,
        ]);
    }

    public function updateMomentCount(int $momentId): void
    {
        $stmt = $this->db->prepare("
            UPDATE moments SET reactions = (
                SELECT COUNT(*) FROM reactions WHERE moment_id = :moment_id
            ) WHERE id = :moment_id
        ");
        $stmt->execute([':moment_id' => $momentId]);
    }
}