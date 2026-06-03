<?php
/**
 * @author Romila Raluca
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\Security;
use App\Models\InviteModel;
use App\Models\FamilyModel;

class InviteTokenService
{
    public function acceptInvite(string $token, int $userId): array
    {
        $inviteModel = new InviteModel();
        $invite = $inviteModel->findByToken($token);

        if (!$invite) {
            return ['success' => false, 'error' => 'Invalid token'];
        }

        if (strtotime($invite['expires_at']) < time()) {
            return ['success' => false, 'error' => 'Token expired'];
        }

        $familyModel = new FamilyModel();
        $familyModel->addMember($invite['child_id'], $userId, $invite['permission']);
        $inviteModel->markUsed((int) $invite['id'], $userId);

        return ['success' => true, 'child_id' => $invite['child_id']];
    }
}