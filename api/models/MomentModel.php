<?php
/**
 * @author Tarpescu Sergiu
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Core\Security;

class MomentModel extends Model
{
    public function getByChild(int $childId, ?string $type = null, int $limit = 50, int $offset = 0): array
    {
        $sql = "
            SELECT m.*, u.first_name, u.last_name, u.avatar_color,
                   (SELECT '/uploads/photos/' || md.filename
                      FROM media md
                     WHERE md.moment_id = m.id
                     ORDER BY md.id ASC
                     LIMIT 1) AS media_url
            FROM moments m
            JOIN users u ON m.logged_by = u.id
            WHERE m.child_id = :child_id
        ";
        $params = [':child_id' => $childId];

        if ($type) {
            $sql .= " AND m.type = :type";
            $params[':type'] = $type;
        }

        $sql .= " ORDER BY m.happened_at DESC LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT m.*, u.first_name, u.last_name
            FROM moments m
            JOIN users u ON m.logged_by = u.id
            WHERE m.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $isShared = $data['is_shared'] ? 1 : 0;
        // Token public generat doar daca momentul e partajat (pentru /share/{token}).
        $shareToken = $isShared ? Security::generateShareToken() : null;

        $stmt = $this->db->prepare("
            INSERT INTO moments (child_id, logged_by, type, title, body, is_pinned, is_shared, share_token, tags, happened_at)
            VALUES (:child_id, :logged_by, :type, :title, :body, :is_pinned, :is_shared, :share_token, :tags, :happened_at)
            RETURNING id
        ");
        $stmt->execute([
            ':child_id' => $data['child_id'],
            ':logged_by' => $data['logged_by'],
            ':type' => $data['type'],
            ':title' => Security::sanitizeInput($data['title']),
            ':body' => Security::sanitizeInput($data['body'] ?? ''),
            ':is_pinned' => $data['is_pinned'] ? 1 : 0,
            ':is_shared' => $isShared,
            ':share_token' => $shareToken,
            ':tags' => $data['tags'] ?? '',
            ':happened_at' => $data['happened_at'] ?? date('Y-m-d H:i:s'),
        ]);
        return (int) $stmt->fetchColumn();
    }

    public function update(int $id, array $data): bool
    {
        $isShared = $data['is_shared'] ? 1 : 0;
        // Daca devine partajat si nu are inca token, primeste unul; token-ul existent se pastreaza.
        $maybeToken = $isShared ? Security::generateShareToken() : null;

        $stmt = $this->db->prepare("
            UPDATE moments SET
                title = :title, body = :body, is_pinned = :is_pinned,
                is_shared = :is_shared,
                share_token = COALESCE(share_token, :maybe_token),
                happened_at = :happened_at
            WHERE id = :id
        ");
        return $stmt->execute([
            ':id' => $id,
            ':title' => Security::sanitizeInput($data['title']),
            ':body' => Security::sanitizeInput($data['body'] ?? ''),
            ':is_pinned' => $data['is_pinned'] ? 1 : 0,
            ':is_shared' => $isShared,
            ':maybe_token' => $maybeToken,
            ':happened_at' => $data['happened_at'],
        ]);
    }

    /** Gaseste un moment partajat dupa token-ul public (pentru pagina /share/{token}). */
    public function findByShareToken(string $token): ?array
    {
        $stmt = $this->db->prepare("
            SELECT m.*, u.first_name, u.last_name,
                   c.first_name AS child_first, c.last_name AS child_last,
                   (SELECT '/uploads/photos/' || md.filename
                      FROM media md
                     WHERE md.moment_id = m.id
                     ORDER BY md.id ASC
                     LIMIT 1) AS media_url
            FROM moments m
            JOIN users u ON m.logged_by = u.id
            JOIN children c ON m.child_id = c.id
            WHERE m.share_token = :token AND m.is_shared = 1
            LIMIT 1
        ");
        $stmt->execute([':token' => $token]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM moments WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function getPinned(int $childId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM moments
            WHERE child_id = :child_id AND is_pinned = 1
            ORDER BY happened_at DESC
        ");
        $stmt->execute([':child_id' => $childId]);
        return $stmt->fetchAll();
    }

    public function getShared(int $childId, int $limit = 50): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM moments
            WHERE child_id = :child_id AND is_shared = 1
            ORDER BY happened_at DESC
            LIMIT :limit
        ");
        $stmt->execute([':child_id' => $childId, ':limit' => $limit]);
        return $stmt->fetchAll();
    }
}