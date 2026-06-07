<?php
/**
 * Coduri temporare de resetare a parolei (tabela password_resets).
 * @author Tarpescu Sergiu
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class PasswordResetModel extends Model
{
    /**
     * Creeaza un cod de resetare valabil $expiryMinutes minute.
     * Expirarea este calculata in DB (NOW() + interval) ca sa fie consistenta cu
     * verificarea din findValid, indiferent de fusul orar al PHP-ului.
     */
    public function create(int $userId, string $code, int $expiryMinutes): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO password_resets (user_id, code, expires_at)
            VALUES (:user_id, :code, NOW() + (:minutes::int * INTERVAL '1 minute'))
            RETURNING id
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':code'    => $code,
            ':minutes' => $expiryMinutes,
        ]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Gaseste un cod valid (neexpirat) pentru user. Intoarce null daca nu exista.
     */
    public function findValid(int $userId, string $code): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM password_resets
            WHERE user_id = :user_id AND code = :code AND expires_at > NOW()
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute([':user_id' => $userId, ':code' => $code]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Sterge toate codurile unui user (dupa folosire sau la cerere noua).
     */
    public function deleteForUser(int $userId): void
    {
        $stmt = $this->db->prepare("DELETE FROM password_resets WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
    }
}
