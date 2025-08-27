<?php

namespace App\Repositories\Fcm;

use Exception;
use App\Interfaces\Fcm\FcmRepositoryInterface;
use App\Models\User;

class FcmRepository implements FcmRepositoryInterface
{
    public function updateDeviceToken(int $userId, string $fcmToken): bool
    {
        try {
            $user = User::findOrFail($userId);
            return $user->update(['fcm_token' => $fcmToken]);
        } catch (Exception $e) {
            return false;
        }
    }
}