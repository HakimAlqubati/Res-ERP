<?php

declare(strict_types=1);

namespace App\Modules\HR\EmployeeDocuments\Notifications;

use App\Modules\HR\EmployeeDocuments\Support\TenantCacheKey;
use App\Services\Warnings\WarningPayload;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Prevents duplicate daily digest notifications without requiring extra columns on hr_employee_files.
 * Uses a fast tenant-isolated cache check backed by the notifications table as persistent fallback.
 */
final class DocumentExpiryDeduplicator
{
    private const CACHE_TTL_HOURS = 36;

    public function buildScopeKey(string $targetScope, string $date): string
    {
        $dbScope = TenantCacheKey::databaseScope();

        return "doc-expiry-digest:{$dbScope}:{$targetScope}:{$date}";
    }

    public function wasSentToday(WarningPayload $payload, int $userId, string $date): bool
    {
        $deterministicId = $payload->deterministicId($userId);
        $cacheKey = $this->cacheKey($deterministicId, $date);

        if (Cache::has($cacheKey)) {
            return true;
        }

        $existsInDb = DB::table('notifications')
            ->where('id', $deterministicId)
            ->where('notifiable_id', $userId)
            ->exists();

        if ($existsInDb) {
            Cache::put($cacheKey, true, now()->addHours(self::CACHE_TTL_HOURS));

            return true;
        }

        return false;
    }

    public function markSent(WarningPayload $payload, int $userId, string $date): void
    {
        $deterministicId = $payload->deterministicId($userId);

        Cache::put(
            $this->cacheKey($deterministicId, $date),
            true,
            now()->addHours(self::CACHE_TTL_HOURS)
        );
    }

    private function cacheKey(string $deterministicId, string $date): string
    {
        return TenantCacheKey::make("notified:{$date}:{$deterministicId}");
    }
}
