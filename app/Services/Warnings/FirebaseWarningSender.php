<?php

namespace App\Services\Warnings;

use App\Models\AppLog;
use Illuminate\Contracts\Auth\Authenticatable;

class FirebaseWarningSender implements WarningSender
{
    /**
     * Send warning via Firebase Cloud Messaging.
     * Uses the sendNotification() helper from app/helpers.php.
     */
    public function send(Authenticatable|iterable $users, WarningPayload $payload): void
    {
        $users = is_iterable($users) ? $users : [$users];

        foreach ($users as $user) {
            $token = $user->fcm_token ?? null;

            if (empty($token)) {
                AppLog::write(
                    "FCM skipped for User {$user->id}: No fcm_token found.",
                    AppLog::LEVEL_INFO,
                    'FirebaseWarningSender'
                );
                continue;
            }

            try {
                // Use the helper function provided by the user
                $resultJson = sendNotification(
                    $token,
                    $payload->title,
                    is_array($payload->detail) ? json_encode($payload->detail) : $payload->detail,
                    $payload->context ?? []
                );

                $result = json_decode($resultJson, true);
                if (($result['status'] ?? '') === 'error') {
                     AppLog::write(
                        "FCM Failed for User {$user->id}: " . ($result['message'] ?? 'Unknown Error'),
                        AppLog::LEVEL_ERROR,
                        'FirebaseWarningSender',
                        $result
                    );
                } else {
                    AppLog::write(
                        "FCM Sent successfully to User {$user->id}.",
                        AppLog::LEVEL_INFO,
                        'FirebaseWarningSender',
                        $result
                    );
                }

            } catch (\Throwable $e) {
                AppLog::write(
                    "FCM Exception for User {$user->id}: " . $e->getMessage(),
                    AppLog::LEVEL_ERROR,
                    'FirebaseWarningSender'
                );
            }
        }
    }

    /**
     * Always send a new notification (Firebase doesn't have native upsert in this context).
     */
    public function sendAlwaysNew(Authenticatable|iterable $users, WarningPayload $payload): void
    {
        $this->send($users, $payload);
    }
}
