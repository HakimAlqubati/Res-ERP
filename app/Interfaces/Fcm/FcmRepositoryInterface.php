<?php

namespace App\Interfaces\Fcm;

interface FcmRepositoryInterface
{
    public function updateDeviceToken(int $userId, string $fcmToken): bool;
}