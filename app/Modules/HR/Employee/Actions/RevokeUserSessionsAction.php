<?php

declare(strict_types=1);

namespace App\Modules\HR\Employee\Actions;

use App\Models\AppLog;
use App\Models\User;
use App\Modules\HR\Employee\Contracts\UserSessionRevokerInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Single-responsibility action to terminate all active API tokens (Passport)
 * and web sessions for a user immediately.
 */
final class RevokeUserSessionsAction implements UserSessionRevokerInterface
{
    public function revokeForUser(int $userId, string $reason = ''): void
    {
        if ($userId <= 0) {
            return;
        }

        $revokedTokens = $this->revokePassportTokens($userId);
        $deletedSessions = $this->deleteDatabaseSessions($userId);
        $this->rotateRememberToken($userId);

        AppLog::write(
            message: "Revoked active sessions/tokens for User #{$userId}" . ($reason ? " ({$reason})" : ''),
            level: AppLog::LEVEL_INFO,
            context: 'HR_SESSION_REVOKED',
            extra: [
                'user_id' => $userId,
                'revoked_tokens_count' => $revokedTokens,
                'deleted_db_sessions_count' => $deletedSessions,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Bulk-revoke Passport access and refresh tokens without N+1 queries.
     */
    private function revokePassportTokens(int $userId): int
    {
        if (! Schema::hasTable('oauth_access_tokens')) {
            return 0;
        }

        $activeTokenIds = DB::table('oauth_access_tokens')
            ->where('user_id', $userId)
            ->where('revoked', false)
            ->pluck('id');

        if ($activeTokenIds->isEmpty()) {
            return 0;
        }

        if (Schema::hasTable('oauth_refresh_tokens')) {
            DB::table('oauth_refresh_tokens')
                ->whereIn('access_token_id', $activeTokenIds->all())
                ->where('revoked', false)
                ->update(['revoked' => true]);
        }

        return DB::table('oauth_access_tokens')
            ->whereIn('id', $activeTokenIds->all())
            ->update([
                'revoked' => true,
                'updated_at' => now(),
            ]);
    }

    /**
     * Delete active session rows when the database session driver table exists.
     */
    private function deleteDatabaseSessions(int $userId): int
    {
        $sessionTable = (string) config('session.table', 'sessions');

        if (! Schema::hasTable($sessionTable)) {
            return 0;
        }

        return DB::table($sessionTable)
            ->where('user_id', $userId)
            ->delete();
    }

    /**
     * Invalidate remember-me cookies without triggering UserObserver loops.
     */
    private function rotateRememberToken(int $userId): void
    {
        User::withTrashed()
            ->where('id', $userId)
            ->update(['remember_token' => Str::random(60)]);
    }
}
