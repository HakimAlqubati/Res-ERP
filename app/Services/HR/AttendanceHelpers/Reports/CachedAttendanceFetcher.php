<?php

namespace App\Services\HR\AttendanceHelpers\Reports;

use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * كلاس يغلف AttendanceFetcher ويخزن النتائج في Redis Cache
 * لتسريع جلب بيانات الحضور وتقليل الاستعلامات على قاعدة البيانات.
 */
class CachedAttendanceFetcher
{
    /**
     * بادئة مفاتيح الكاش
     */
    protected const CACHE_PREFIX = 'attendance';

    /**
     * مدة الكاش الافتراضية بالثواني (ساعة واحدة)
     */
    protected const DEFAULT_TTL = 3600;

    protected AttendanceFetcher $attendanceFetcher;
    protected int $ttl;

    public function __construct(AttendanceFetcher $attendanceFetcher, int $ttl = self::DEFAULT_TTL)
    {
        $this->attendanceFetcher = $attendanceFetcher;
        $this->ttl = $ttl;
    }

    /**
     * جلب حضور موظف لفترة معينة - من الكاش أو من قاعدة البيانات
     *
     * @param Employee $employee
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param bool $forceRefresh إجبار إعادة الجلب من قاعدة البيانات
     * @return Collection
     */
    public function fetchEmployeeAttendances(
        Employee $employee,
        Carbon $startDate,
        Carbon $endDate,
        bool $forceRefresh = false
    ): Collection {
        $cacheKey = $this->buildCacheKey('fetch', $employee->id, $startDate->format('Y-m-d'), $endDate->format('Y-m-d'));

        // إذا طلب المستخدم تحديث البيانات
        if ($forceRefresh) {
            $this->forgetCache($cacheKey);
        }

        $cached = Cache::store('redis')->get($cacheKey);

        if ($cached !== null) {
            return $this->hydrateCollection($cached);
        }

        // جلب البيانات من AttendanceFetcher الأصلي
        $result = $this->attendanceFetcher->fetchEmployeeAttendances($employee, $startDate, $endDate);

        // تخزين في Redis
        Cache::store('redis')->put($cacheKey, $this->dehydrateCollection($result), $this->ttl);

        return $result;
    }

    /**
     * جلب تفاصيل حضور موظف لفترة معينة في يوم محدد - من الكاش أو من قاعدة البيانات
     *
     * @param int $employeeId
     * @param int $periodId
     * @param string $date
     * @param bool $forceRefresh
     * @return mixed
     */
    public function getEmployeePeriodAttendnaceDetails(
        int $employeeId,
        int $periodId,
        string $date,
        bool $forceRefresh = false
    ) {
        $cacheKey = $this->buildCacheKey('period_details', $employeeId, $periodId, $date);

        if ($forceRefresh) {
            $this->forgetCache($cacheKey);
        }

        return Cache::store('redis')->remember($cacheKey, $this->ttl, function () use ($employeeId, $periodId, $date) {
            return $this->attendanceFetcher->getEmployeePeriodAttendnaceDetails($employeeId, $periodId, $date);
        });
    }

    /**
     * حذف كاش حضور موظف معين لفترة معينة
     *
     * @param int $employeeId
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @return void
     */
    public function forgetEmployeeCache(int $employeeId, ?Carbon $startDate = null, ?Carbon $endDate = null): void
    {
        if ($startDate && $endDate) {
            $cacheKey = $this->buildCacheKey('fetch', $employeeId, $startDate->format('Y-m-d'), $endDate->format('Y-m-d'));
            $this->forgetCache($cacheKey);
        }

        // حذف جميع مفاتيح هذا الموظف عبر pattern
        $this->forgetByPattern(self::CACHE_PREFIX . ":{$employeeId}:*");
    }

    /**
     * حذف جميع كاش الحضور
     *
     * @return void
     */
    public function forgetAllCache(): void
    {
        $this->forgetByPattern(self::CACHE_PREFIX . ':*');
    }

    /**
     * الحصول على الـ AttendanceFetcher الأصلي
     *
     * @return AttendanceFetcher
     */
    public function getOriginalFetcher(): AttendanceFetcher
    {
        return $this->attendanceFetcher;
    }

    /**
     * تغيير مدة الكاش
     *
     * @param int $seconds
     * @return static
     */
    public function setTtl(int $seconds): static
    {
        $this->ttl = $seconds;
        return $this;
    }

    // =========================================================================
    // Private / Helper Methods
    // =========================================================================

    /**
     * بناء مفتاح الكاش
     */
    protected function buildCacheKey(string $type, ...$parts): string
    {
        return self::CACHE_PREFIX . ':' . $type . ':' . implode(':', $parts);
    }

    /**
     * حذف مفتاح معين من الكاش
     */
    protected function forgetCache(string $key): void
    {
        Cache::store('redis')->forget($key);
    }

    /**
     * حذف مفاتيح بناءً على pattern
     * يستخدم Redis SCAN + DEL لحذف المفاتيح المطابقة
     */
    protected function forgetByPattern(string $pattern): void
    {
        $redis = Cache::store('redis')->getStore()->connection();
        $prefix = config('cache.stores.redis.prefix', config('cache.prefix', ''));

        $cursor = null;
        do {
            $result = $redis->scan($cursor, [
                'match' => $prefix . $pattern,
                'count' => 100,
            ]);

            if ($result === false) {
                break;
            }

            [$cursor, $keys] = $result;

            if (!empty($keys)) {
                $redis->del(...$keys);
            }
        } while ($cursor != 0);
    }

    /**
     * تحويل Collection إلى array قابل للتخزين في الكاش
     * يحوّل الـ Collection الفرعية (مثل periods) إلى arrays
     */
    protected function dehydrateCollection(Collection $collection): array
    {
        return $collection->map(function ($item) {
            if ($item instanceof Collection) {
                return ['__type' => 'collection', '__data' => $this->dehydrateCollection($item)];
            }

            if (is_array($item) && isset($item['periods']) && $item['periods'] instanceof Collection) {
                $item['periods'] = ['__type' => 'collection', '__data' => $item['periods']->toArray()];
            }

            return $item;
        })->toArray();
    }

    /**
     * تحويل array من الكاش إلى Collection مع استعادة الـ Collections الفرعية
     */
    protected function hydrateCollection(array $data): Collection
    {
        $collection = collect($data)->map(function ($item) {
            // استعادة Collection مغلفة
            if (is_array($item) && isset($item['__type']) && $item['__type'] === 'collection') {
                return collect($item['__data']);
            }

            // استعادة periods كـ Collection داخل يوم
            if (is_array($item) && isset($item['periods'])) {
                if (is_array($item['periods']) && isset($item['periods']['__type']) && $item['periods']['__type'] === 'collection') {
                    $item['periods'] = collect($item['periods']['__data']);
                } elseif (is_array($item['periods'])) {
                    $item['periods'] = collect($item['periods']);
                }
            }

            return $item;
        });

        return $collection;
    }
}
