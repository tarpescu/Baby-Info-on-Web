<?php
/**
 * @author Romila Raluca
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class FamilyModel extends Model
{
    public function getMembers(int $childId): array
    {
        $stmt = $this->db->prepare("
            SELECT fm.id, fm.permission, fm.joined_at,
                   u.id AS user_id, u.first_name, u.last_name, u.email, u.avatar_color
            FROM family_members fm
            JOIN users u ON fm.user_id = u.id
            WHERE fm.child_id = :child_id
            ORDER BY fm.joined_at ASC
        ");
        $stmt->execute([':child_id' => $childId]);
        return $stmt->fetchAll();
    }

    public function getPermission(int $childId, int $userId): ?string
    {
        $stmt = $this->db->prepare("
            SELECT permission FROM family_members
            WHERE child_id = :child_id AND user_id = :user_id
            LIMIT 1
        ");
        $stmt->execute([':child_id' => $childId, ':user_id' => $userId]);
        $row = $stmt->fetch();
        return $row['permission'] ?? null;
    }

    public function addMember(int $childId, int $userId, string $permission): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO family_members (child_id, user_id, permission)
            VALUES (:child_id, :user_id, :permission)
            ON CONFLICT (child_id, user_id) DO NOTHING
        ");
        $stmt->execute([
            ':child_id' => $childId,
            ':user_id' => $userId,
            ':permission' => $permission,
        ]);
    }

    public function removeMember(int $childId, int $userId): bool
    {
        $stmt = $this->db->prepare("
            DELETE FROM family_members
            WHERE child_id = :child_id AND user_id = :user_id
        ");
        return $stmt->execute([':child_id' => $childId, ':user_id' => $userId]);
    }

    /**
     * Returneaza toate child_id-urile la care un user are acces (orice rol).
     * Folosit la acceptarea invitatiei pentru a adauga noul user la toti copiii familiei.
     */
    public function getChildIdsByUser(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT child_id FROM family_members
            WHERE user_id = :user_id
        ");
        $stmt->execute([':user_id' => $userId]);
        return array_column($stmt->fetchAll(), 'child_id');
    }

    public function updatePermission(int $childId, int $userId, string $permission): bool
    {
        $stmt = $this->db->prepare("
            UPDATE family_members SET permission = :permission
            WHERE child_id = :child_id AND user_id = :user_id
        ");
        return $stmt->execute([
            ':child_id' => $childId,
            ':user_id' => $userId,
            ':permission' => $permission,
        ]);
    }
}