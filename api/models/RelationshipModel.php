<?php
/**
 * Cercul social al copilului (tabela relationships).
 * @author Tarpescu Sergiu
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Core\Security;

class RelationshipModel extends Model
{
    public function getByChild(int $childId): array
    {
        $stmt = $this->db->prepare("
            SELECT r.*, u.first_name AS added_by_first, u.last_name AS added_by_last
            FROM relationships r
            JOIN users u ON r.added_by = u.id
            WHERE r.child_id = :child_id
            ORDER BY r.group_type, r.name
        ");
        $stmt->execute([':child_id' => $childId]);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM relationships WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO relationships (child_id, name, relationship, group_type, age_years, notes, avatar_color, added_by)
            VALUES (:child_id, :name, :relationship, :group_type, :age_years, :notes, :avatar_color, :added_by)
            RETURNING id
        ");
        $stmt->execute([
            ':child_id'     => $data['child_id'],
            ':name'         => Security::sanitizeInput($data['name']),
            ':relationship' => Security::sanitizeInput($data['relationship']),
            ':group_type'   => $data['group_type'],
            ':age_years'    => $data['age_years'] ?? null,
            ':notes'        => isset($data['notes']) ? Security::sanitizeInput($data['notes']) : null,
            ':avatar_color' => $data['avatar_color'] ?? 'c1',
            ':added_by'     => $data['added_by'],
        ]);
        return (int) $stmt->fetchColumn();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE relationships SET
                name = :name, relationship = :relationship, group_type = :group_type,
                age_years = :age_years, notes = :notes, avatar_color = :avatar_color
            WHERE id = :id
        ");
        return $stmt->execute([
            ':id'           => $id,
            ':name'         => Security::sanitizeInput($data['name']),
            ':relationship' => Security::sanitizeInput($data['relationship']),
            ':group_type'   => $data['group_type'],
            ':age_years'    => $data['age_years'] ?? null,
            ':notes'        => isset($data['notes']) ? Security::sanitizeInput($data['notes']) : null,
            ':avatar_color' => $data['avatar_color'] ?? 'c1',
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM relationships WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
