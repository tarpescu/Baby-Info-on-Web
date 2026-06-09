<?php
/**
 * Model pentru token-urile Bearer folosite de REST API v1.
 * Token-ul raw (64 bytes hex) este returnat o singura data la creare;
 * in baza de date se stocheaza doar hash-ul SHA-256.
 * @author Romila Raluca
 */

declare(strict_types=1);

namespace App\Models;

use App\Config\Database;

class ApiTokenModel
{
    /**
     * Cauta un token activ dupa hash-ul SHA-256.
     * Returneaza randul JOIN-at cu users (include is_superadmin, banned_at).
     *
     * @param  string     $hash SHA-256 al token-ului raw
     * @return array|null Randul din api_tokens + users, sau null daca nu exista
     */
    public function findByHash(string $hash): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT t.id, t.user_id, t.token_hash, t.expires_at, t.revoked,
                   u.is_superadmin, u.banned_at
              FROM api_tokens t
              JOIN users u ON u.id = t.user_id
             WHERE t.token_hash = :hash
             LIMIT 1
        ");
        $stmt->execute([':hash' => $hash]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Creeaza un token nou pentru un user.
     * Genereaza 32 bytes random, calculeaza SHA-256, salveaza hash-ul.
     *
     * @param  int         $userId       ID-ul userului proprietar
     * @param  string      $name         Eticheta descriptiva (ex. "Postman dev")
     * @param  int|null    $expiresInDays Zile pana la expirare; null = fara expirare
     * @return string      Token-ul raw (de afisat o singura data clientului)
     */
    public function create(int $userId, string $name, ?int $expiresInDays = 30): string
    {
        $raw  = bin2hex(random_bytes(32));   // 64 chars hex
        $hash = hash('sha256', $raw);        // 64 chars hex

        $expiresAt = ($expiresInDays !== null && $expiresInDays > 0)
            ? date('Y-m-d H:i:s', strtotime("+{$expiresInDays} days"))
            : null;

        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO api_tokens (user_id, token_hash, name, expires_at)
            VALUES (:user_id, :hash, :name, :expires_at)
        ");
        $stmt->execute([
            ':user_id'    => $userId,
            ':hash'       => $hash,
            ':name'       => $name,
            ':expires_at' => $expiresAt,
        ]);

        return $raw;
    }

    /**
     * Actualizeaza timestamp-ul last_used_at pentru un token.
     *
     * @param int $id ID-ul randului din api_tokens
     */
    public function touch(int $id): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE api_tokens SET last_used_at = NOW() WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    /**
     * Revoca toate token-urile unui user (setare revoked = TRUE).
     *
     * @param int $userId
     */
    public function revokeAll(int $userId): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE api_tokens SET revoked = TRUE WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
    }

    /**
     * Listeaza toate token-urile unui user (fara token_hash — nu se expune niciodata).
     *
     * @param  int   $userId
     * @return array Lista de token-uri cu id, name, created_at, expires_at, last_used_at, revoked
     */
    public function listForUser(int $userId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT id, name, created_at, expires_at, last_used_at, revoked
              FROM api_tokens
             WHERE user_id = :user_id
          ORDER BY created_at DESC
        ");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }
}
