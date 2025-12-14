<?php

namespace App\Services\Warnings;

use App\Mail\WarningMail;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Email Warning Sender - sends warnings via email.
 */
final class EmailWarningSender implements WarningSender
{
    /**
     * Send warning email to users.
     */
    public function send(Authenticatable|iterable $users, WarningPayload $payload): void
    {
        $this->dispatchEmails($users, $payload);
    }

    /**
     * Send warning email (same as send for email channel).
     */
    public function sendAlwaysNew(Authenticatable|iterable $users, WarningPayload $payload): void
    {
        $this->dispatchEmails($users, $payload);
    }

    /**
     * Dispatch emails to all users.
     */
    protected function dispatchEmails(Authenticatable|iterable $users, WarningPayload $payload): void
    {
        $users = is_iterable($users) ? $users : [$users];
        $userCount = is_array($users) ? count($users) : iterator_count($users);

        Log::info("[EmailWarningSender] dispatchEmails called", [
            'user_count' => $userCount,
            'payload_title' => $payload->title,
        ]);

        foreach ($users as $user) {
            try {
                // Skip users without email
                if (empty($user->email)) {
                    Log::info("[EmailWarningSender] Skipping user without email", ['user_id' => $user->id ?? null]);
                    continue;
                }

                Log::info("[EmailWarningSender] Sending email", [
                    'user_id' => $user->id ?? null,
                    'email' => $user->email,
                ]);

                // Send email immediately (use queue() for production with queue worker)
                Mail::to($user->email)->send(new WarningMail($payload, $user));

                Log::info("[EmailWarningSender] Email sent successfully", ['email' => $user->email]);
            } catch (\Throwable $e) {
                Log::error('Failed to send warning email', [
                    'user_id' => $user->id ?? null,
                    'email' => $user->email ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
