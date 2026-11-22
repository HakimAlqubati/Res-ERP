<?php

declare(strict_types=1);

namespace App\Modules\HR\EmployeeDocuments\Support;

use App\Models\EmployeeFileType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Request-scoped, tenant-isolated catalog of EmployeeFileType metadata & dynamic fields.
 * Eliminates N+1 queries inside Filament Repeaters, tables, and filters while staying Octane-safe.
 */
final class EmployeeFileTypeCatalog
{
    private const CACHE_TTL_SECONDS = 300;
    private const CACHE_SUFFIX = 'file_types_catalog_v1';

    /**
     * In-memory request memoization keyed by active database scope.
     *
     * @var array<string, Collection<int, array{id: int, name: string, active: bool, is_required: bool, has_expiry: bool, expiry_alert_days: int, dynamic_fields: array<int, array{id: int, field_name: string, field_type: string}>}>>
     */
    private array $memoized = [];

    /**
     * @return Collection<int, array{id: int, name: string, active: bool, is_required: bool, has_expiry: bool, expiry_alert_days: int, dynamic_fields: array<int, array{id: int, field_name: string, field_type: string}>}>
     */
    public function all(): Collection
    {
        $scope = TenantCacheKey::databaseScope();

        if (isset($this->memoized[$scope])) {
            return $this->memoized[$scope];
        }

        $cacheKey = TenantCacheKey::make(self::CACHE_SUFFIX);

        /** @var array<int, array{id: int, name: string, active: bool, is_required: bool, has_expiry: bool, expiry_alert_days: int, dynamic_fields: array<int, array{id: int, field_name: string, field_type: string}>}> $rows */
        $rows = Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, static function (): array {
            return EmployeeFileType::query()
                ->select(['id', 'name', 'active', 'is_required', 'has_expiry', 'expiry_alert_days'])
                ->with(['dynamicFields:id,file_type_id,field_name,field_type'])
                ->orderBy('name')
                ->get()
                ->mapWithKeys(static fn (EmployeeFileType $type): array => [
                    (int) $type->id => [
                        'id' => (int) $type->id,
                        'name' => (string) $type->name,
                        'active' => (bool) $type->active,
                        'is_required' => (bool) $type->is_required,
                        'has_expiry' => (bool) $type->has_expiry,
                        'expiry_alert_days' => (int) ($type->expiry_alert_days ?? DocumentStatusCalculator::DEFAULT_ALERT_DAYS),
                        'dynamic_fields' => $type->dynamicFields
                            ->map(static fn ($field): array => [
                                'id' => (int) $field->id,
                                'field_name' => (string) $field->field_name,
                                'field_type' => (string) $field->field_type,
                            ])
                            ->values()
                            ->all(),
                    ],
                ])
                ->all();
        });

        return $this->memoized[$scope] = collect($rows);
    }

    /**
     * @return array<int, string>
     */
    public function activeOptions(): array
    {
        return $this->all()
            ->filter(static fn (array $item): bool => $item['active'])
            ->mapWithKeys(static fn (array $item): array => [$item['id'] => $item['name']])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function allOptions(): array
    {
        return $this->all()
            ->mapWithKeys(static fn (array $item): array => [$item['id'] => $item['name']])
            ->all();
    }

    /**
     * @return array{id: int, name: string, active: bool, is_required: bool, has_expiry: bool, expiry_alert_days: int, dynamic_fields: array<int, array{id: int, field_name: string, field_type: string}>}|null
     */
    public function find(int|string|null $fileTypeId): ?array
    {
        if (! is_numeric($fileTypeId)) {
            return null;
        }

        return $this->all()->get((int) $fileTypeId);
    }

    public function hasExpiry(int|string|null $fileTypeId): bool
    {
        return (bool) ($this->find($fileTypeId)['has_expiry'] ?? false);
    }

    public function alertDays(int|string|null $fileTypeId): int
    {
        return (int) ($this->find($fileTypeId)['expiry_alert_days'] ?? DocumentStatusCalculator::DEFAULT_ALERT_DAYS);
    }

    /**
     * @return array<int, array{id: int, field_name: string, field_type: string}>
     */
    public function dynamicFields(int|string|null $fileTypeId): array
    {
        return $this->find($fileTypeId)['dynamic_fields'] ?? [];
    }

    public function flush(): void
    {
        $scope = TenantCacheKey::databaseScope();
        unset($this->memoized[$scope]);
        Cache::forget(TenantCacheKey::make(self::CACHE_SUFFIX));
    }

    public function flushMemory(): void
    {
        $this->memoized = [];
    }
}
