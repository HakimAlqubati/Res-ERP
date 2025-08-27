<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Models\User;

class NotificationService
{
    /**
     * Send notification to a user or a list of users using FCM token.
     *
     * @param string|array $recipients   Single FCM token or array of tokens
     * @param string $title              Notification title
     * @param string $body               Notification body
     * @param array $data                Optional data payload
     * @return void
     */
    public function sendFCMNotification(string|array $recipients, string $title, string $body, array $data = []): void
    {
        $tokens = is_array($recipients) ? $recipients : [$recipients];

        foreach ($tokens as $token) {
            try {
                sendNotification($token, $title, $body, $data);
            } catch (Exception $e) {
                Log::error("FCM Notification failed: " . $e->getMessage(), [
                    'token' => $token,
                    'title' => $title,
                    'body' => $body,
                    'data' => $data,
                ]);
            }
        }
    }

    /**
     * Send notification to all users with a specific role.
     *
     * @param int $roleId
     * @param string $title
     * @param string $body
     * @param array $data
     * @return void
     */
    public function notifyUsersByRole(int $roleId, string $title, string $body, array $data = []): void
    {
        $tokens = User::whereHas('roles', function ($query) use ($roleId) {
            $query->where('id', $roleId);
        })->pluck('fcm_token')->filter()->toArray();

        $this->sendFCMNotification($tokens, $title, $body, $data);
    }

    /**
     * Send notification to a user model.
     *
     * @param User $user
     * @param string $title
     * @param string $body
     * @param array $data
     * @return void
     */
    public function notifyUser(User $user, string $title, string $body, array $data = []): void
    {
        if ($user->fcm_token) {
            $this->sendFCMNotification($user->fcm_token, $title, $body, $data);
        }
    }
}
