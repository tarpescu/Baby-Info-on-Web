<?php
/**
 * @author Romila Raluca
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class MedicalModel extends Model
{
    public function getByChild(int $childId, int $limit = 50): array
    {
        $stmt = $this->db->prepare("
            SELECT m.*, u.first_name, u.last_name
            FROM medical_records m
            JOIN users u ON m.logged_by = u.id
            WHERE m.child_id = :child_id
            ORDER BY m.date_at DESC
            LIMIT :limit
        ");
        $stmt->execute([':child_id' => $childId, ':limit' => $limit]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO medical_records (child_id, logged_by, type, title, description, doctor_name, clinic_name, date_at, next_date, document_url)
            VALUES (:child_id, :logged_by, :type, :title, :description, :doctor_name, :clinic_name, :date_at, :next_date, :document_url)
            RETURNING id
        ");
        $stmt->execute([
            ':child_id' => $data['child_id'],
            ':logged_by' => $data['logged_by'],
            ':type' => $data['type'],
            ':title' => $data['title'],
            ':description' => $data['description'] ?? null,
            ':doctor_name' => $data['doctor_name'] ?? null,
            ':clinic_name' => $data['clinic_name'] ?? null,
            ':date_at' => $data['date_at'],
            ':next_date' => $data['next_date'] ?? null,
            ':document_url' => $data['document_url'] ?? null,
        ]);
        return (int) $stmt->fetchColumn();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM medical_records WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}