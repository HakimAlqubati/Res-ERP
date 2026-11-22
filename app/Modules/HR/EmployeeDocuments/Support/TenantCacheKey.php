<?php

declare(strict_types=1);

namespace App\Modules\HR\EmployeeDocuments\Support;

use Illuminate\Support\Facades\DB;
use Spatie\Multitenancy\Models\Tenant;

/**
 * Generates tenant- and database-isolated cache keys.
 * Required because PrefixCacheTask is disabled in config/multitenancy.php.
 */
final class TenantCacheKey
{
    public static function databaseScope(): string
    {
        $tenantId = class_exists(Tenant::class) && Tenant::current()
            ? (string) Tenant::current()->getKey()
            : 'central';

        $dbName = (string) (DB::connection()->getDatabaseName() ?: 'default');

        return "{$tenantId}:{$dbName}";
    }

    public static function make(string $suffix): string
    {
        return 'hr:employee_documents:' . self::databaseScope() . ':' . ltrim($suffix, ':');
    }
}
