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

        $answers = $data['security_answers'] ?? [];

        $stmt = $this->db->prepare("
            INSERT INTO users (first_name, last_name, email, password_hash, role, avatar_color,
                               security_answer_1, security_answer_2, security_answer_3)
            VALUES (:first_name, :last_name, :email, :password_hash, :role, :avatar_color,
                    :security_answer_1, :security_answer_2, :security_answer_3)
            RETURNING id
        ");
        $stmt->execute([
            ':first_name' => Security::sanitizeInput($data['first_name']),
            ':last_name' => Security::sanitizeInput($data['last_name']),
            ':email' => strtolower(trim($data['email'])),
            ':password_hash' => Security::hashPassword($data['password']),
            ':role' => $data['role'] ?? 'viewer',
            ':avatar_color' => $color,
            ':security_answer_1' => self::hashAnswer($answers[0] ?? null),
            ':security_answer_2' => self::hashAnswer($answers[1] ?? null),
            ':security_answer_3' => self::hashAnswer($answers[2] ?? null),
        ]);
        return (int) $stmt->fetchColumn();
    }

    /** Normalizeaza (lowercase + trim) si hashuieste un raspuns de securitate. */
    private static function hashAnswer(?string $answer): ?string
    {
        $answer = trim((string) $answer);
        if ($answer === '') {
            return null;
        }
        return Security::hashPassword(mb_strtolower($answer));
    }

    /** Seteaza/actualizeaza cele 3 raspunsuri de securitate (hash-uite). */
    public function updateSecurityAnswers(int $id, array $answers): bool
    {
        $stmt = $this->db->prepare("
            UPDATE users SET security_answer_1 = :a1, security_answer_2 = :a2, security_answer_3 = :a3
            WHERE id = :id
        ");
        return $stmt->execute([
            ':id' => $id,
            ':a1' => self::hashAnswer($answers[0] ?? null),
            ':a2' => self::hashAnswer($answers[1] ?? null),
            ':a3' => self::hashAnswer($answers[2] ?? null),
        ]);
    }

    /** Verifica daca toate cele 3 raspunsuri date corespund (case-insensitive). */
    public function verifySecurityAnswers(int $userId, array $answers): bool
    {
        $stmt = $this->db->prepare("
            SELECT security_answer_1, security_answer_2, security_answer_3
            FROM users WHERE id = :id LIMIT 1
        ");
        $stmt->execute([':id' => $userId]);
        $row = $stmt->fetch();
        if (!$row) {
            return false;
        }

        $hashes = [$row['security_answer_1'], $row['security_answer_2'], $row['security_answer_3']];
        for ($i = 0; $i < 3; $i++) {
            $given = mb_strtolower(trim((string) ($answers[$i] ?? '')));
            if ($given === '' || empty($hashes[$i]) || !Security::verifyPassword($given, $hashes[$i])) {
                return false;
            }
        }
        return true;
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

    /**
     * Seteaza o parola noua (primeste parola in clar, o hashuieste intern).
     */
    public function updatePassword(int $id, string $plainPassword): bool
    {
        $stmt = $this->db->prepare("
            UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE id = :id
        ");
        return $stmt->execute([
            ':id'   => $id,
            ':hash' => Security::hashPassword($plainPassword),
        ]);
    }

    /**
     * Lista tuturor userilor (fara password_hash), cei mai noi primii.
     */
    public function getAll(): array
    {
        $stmt = $this->db->query("
            SELECT id, first_name, last_name, email, role, is_superadmin,
                   banned_at, ban_reason, theme, avatar_color, created_at
            FROM users
            ORDER BY created_at DESC
        ");
        return $stmt->fetchAll();
    }
}