<?php
/**
 * @author Romila Raluca
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Core\Security;

class MediaModel extends Model
{
    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO media (child_id, moment_id, uploaded_by, type, filename, original_name, size_bytes, mime_type, caption, taken_at)
            VALUES (:child_id, :moment_id, :uploaded_by, :type, :filename, :original_name, :size_bytes, :mime_type, :caption, :taken_at)
            RETURNING id
        ");
        $stmt->execute([
            ':child_id' => $data['child_id'],
            ':moment_id' => $data['moment_id'] ?? null,
            ':uploaded_by' => $data['uploaded_by'],
            ':type' => $data['type'],
            ':filename' => $data['filename'],
            ':original_name' => $data['original_name'],
            ':size_bytes' => $data['size_bytes'] ?? null,
            ':mime_type' => $data['mime_type'] ?? null,
            ':caption' => isset($data['caption']) ? Security::sanitizeInput($data['caption']) : null,
            ':taken_at' => $data['taken_at'] ?? null,
        ]);
        return (int) $stmt->fetchColumn();
    }

    public function getByMoment(int $momentId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM media
            WHERE moment_id = :moment_id
            ORDER BY id ASC
        ");
        $stmt->execute([':moment_id' => $momentId]);
        return $stmt->fetchAll();
    }
}
