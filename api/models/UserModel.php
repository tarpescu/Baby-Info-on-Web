<?php
/**
 * @author Romila Raluca
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Core\Security;

class UserModel extends Model
{
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("
            SELECT id, first_name, last_name, email, password_hash, role, 
                   is_superadmin, banned_at, ban_reason, theme, avatar_color
            FROM users 
            WHERE email = :email 
            LIMIT 1
        ");
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT id, first_name, last_name, email, role, is_superadmin, 
                   banned_at, theme, avatar_color, created_at
            FROM users 
            WHERE id = :id 
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
            INSERT INTO users (first_name, last_name, email, password_hash, role, avatar_color)
            VALUES (:first_name, :last_name, :email, :password_hash, :role, :avatar_color)
            RETURNING id
        ");
        $stmt->execute([
            ':first_name' => Security::sanitizeInput($data['first_name']),
            ':last_name' => Security::sanitizeInput($data['last_name']),
            ':email' => strtolower(trim($data['email'])),
            ':password_hash' => Security::hashPassword($data['password']),
            ':role' => $data['role'] ?? 'viewer',
            ':avatar_color' => $color,
        ]);
        return (int) $stmt->fetchColumn();
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => strtolower(trim($email))]);
        return (bool) $stmt->fetch();
    }

    public function ban(int $id, string $reason): bool
    {
        $stmt = $this->db->prepare("
            UPDATE users SET banned_at = NOW(), ban_reason = :reason WHERE id = :id
        ");
        return $stmt->execute([':id' => $id, ':reason' => $reason]);
    }

    public function unban(int $id): bool
    {
        $stmt = $this->db->prepare("
            UPDATE users SET banned_at = NULL, ban_reason = NULL WHERE id = :id
        ");
        return $stmt->execute([':id' => $id]);
    }

    public function updateTheme(int $id, string $theme): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET theme = :theme WHERE id = :id");
        return $stmt->execute([':id' => $id, ':theme' => $theme]);
    }
}