<?php
/**
 * @author Romila Raluca
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Core\Security;

class ChildModel extends Model
{
    public function getByUser(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT c.*, fm.permission 
            FROM children c
            JOIN family_members fm ON c.id = fm.child_id
            WHERE fm.user_id = :user_id
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Returneaza toti copiii din platforma, cu numele owner-ului
     * (folosit la exportul CSV din panoul de admin).
     *
     * @return array
     */
    public function getAll(): array
    {
        $stmt = $this->db->query("
            SELECT c.id, c.first_name, c.last_name, c.date_of_birth, c.gender,
                   c.blood_type, c.created_at,
                   u.first_name AS owner_first, u.last_name AS owner_last, u.email AS owner_email
            FROM children c
            JOIN users u ON c.created_by = u.id
            ORDER BY c.id
        ");
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT c.*, u.first_name AS parent_first, u.last_name AS parent_last
            FROM children c
            JOIN users u ON c.created_by = u.id
            WHERE c.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $colors = ['c1', 'c2', 'c3', 'c4', 'c5', 'c6'];
        $color = $data['avatar_color'] ?? $colors[array_rand($colors)];

        $stmt = $this->db->prepare("
            INSERT INTO children (first_name, last_name, date_of_birth, gender, 
                                  blood_type, avatar_color, notes, created_by)
            VALUES (:first_name, :last_name, :dob, :gender, :blood, :color, :notes, :created_by)
            RETURNING id
        ");
        $stmt->execute([
            ':first_name' => Security::sanitizeInput($data['first_name']),
            ':last_name' => Security::sanitizeInput($data['last_name'] ?? ''),
            ':dob' => $data['date_of_birth'],
            ':gender' => $data['gender'] ?? null,
            ':blood' => $data['blood_type'] ?? null,
            ':color' => $color,
            ':notes' => Security::sanitizeInput($data['notes'] ?? ''),
            ':created_by' => $data['created_by'],
        ]);
        return (int) $stmt->fetchColumn();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE children 
            SET first_name = :first_name, last_name = :last_name, 
                date_of_birth = :dob, gender = :gender, 
                blood_type = :blood, notes = :notes, updated_at = NOW()
            WHERE id = :id
        ");
        return $stmt->execute([
            ':id' => $id,
            ':first_name' => Security::sanitizeInput($data['first_name']),
            ':last_name' => Security::sanitizeInput($data['last_name'] ?? ''),
            ':dob' => $data['date_of_birth'],
            ':gender' => $data['gender'] ?? null,
            ':blood' => $data['blood_type'] ?? null,
            ':notes' => Security::sanitizeInput($data['notes'] ?? ''),
        ]);
    }

    /**
     * Actualizeaza URL-ul pozei de profil a copilului.
     * @param int $id
     * @param string $photoUrl - calea relativa, ex: /uploads/photos/123_abc.jpg
     * @return bool
     */
    public function updatePhoto(int $id, string $photoUrl): bool
    {
        $stmt = $this->db->prepare("
            UPDATE children SET photo_url = :photo_url, updated_at = NOW() WHERE id = :id
        ");
        return $stmt->execute([':photo_url' => $photoUrl, ':id' => $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM children WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function addFamilyMember(int $childId, int $userId, string $permission): void
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
}