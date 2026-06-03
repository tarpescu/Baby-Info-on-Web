<?php
/**
 * @author Romila Raluca
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class InviteModel extends Model
{
    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO invitations (child_id, invited_by, token, email, permission, expires_at)
            VALUES (:child_id, :invited_by, :token, :email, :permission, :expires_at)
            RETURNING id
        ");
        $stmt->execute([
            ':child_id' => $data['child_id'],
            ':invited_by' => $data['invited_by'],
            ':token' => $data['token'],
            ':email' => $data['email'] ?? null,
            ':permission' => $data['permission'],
            ':expires_at' => $data['expires_at'],
        ]);
        return (int) $stmt->fetchColumn();
    }

    public function findByToken(string $token): ?array
    {
        $stmt = $this->db->prepare("
            SELECT i.*, c.first_name AS child_first, c.last_name AS child_last
            FROM invitations i
            JOIN children c ON i.child_id = c.id
            WHERE i.token = :token AND i.used_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([':token' => $token]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function markUsed(int $inviteId, int $userId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE invitations SET used_at = NOW(), used_by = :user_id
            WHERE id = :id
        ");
        return $stmt->execute([':id' => $inviteId, ':user_id' => $userId]);
    }

    public function getByChild(int $childId): array
    {
        $stmt = $this->db->prepare("
            SELECT i.*, u.first_name AS inviter_first
            FROM invitations i
            JOIN users u ON i.invited_by = u.id
            WHERE i.child_id = :child_id
            ORDER BY i.created_at DESC
        ");
        $stmt->execute([':child_id' => $childId]);
        return $stmt->fetchAll();
    }
}