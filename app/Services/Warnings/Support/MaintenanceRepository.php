<?php

namespace App\Services\Warnings\Support;

use App\Models\Equipment;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;

final class MaintenanceRepository
{
    /**
     * معدات تأخرت صيانتها (next_service_date < اليوم)
     */
    public function overdue(array $filters = [])
    {
        return $this->baseQuery($filters)
            ->whereNotNull('next_service_date')
            ->whereDate('next_service_date', '<', Carbon::today())
            ->orderBy('next_service_date')
            ->cursor();
    }

    /**
     * معدات موعد صيانتها خلال N يوم (بما في اليوم)
     */
    public function dueWithin(int $days = 7, array $filters = [])
    {
        $today = Carbon::today();
        $limit = Carbon::today()->addDays($days);

        return $this->baseQuery($filters)
            ->whereNotNull('next_service_date')
            ->whereBetween('next_service_date', [$today, $limit])
            ->orderBy('next_service_date')
            ->cursor();
    }

    /**
     * معدات صيانتها قادمة بعد N يوم (للاطلاع/التخطيط)
     */
    public function upcoming(int $days = 30, array $filters = [])
    {
        $start = Carbon::today()->addDay();
        $end   = Carbon::today()->addDays($days);

        return $this->baseQuery($filters)
            ->whereNotNull('next_service_date')
            ->whereBetween('next_service_date', [$start, $end])
            ->orderBy('next_service_date')
            ->cursor();
    }

    /** ===== Internals ===== */
    private function baseQuery(array $filters): Builder
    {
        $q = Equipment::query()->whereNull('deleted_at');

        // افتراضياً: نستثني المتقاعدة
        $q->where('status', '!=', Equipment::STATUS_RETIRED);

        if (!empty($filters['branch_id'])) {
            $q->where('branch_id', (int)$filters['branch_id']);
        }
        if (!empty($filters['branch_area_id'])) {
            $q->where('branch_area_id', (int)$filters['branch_area_id']);
        }
        if (!empty($filters['status'])) {
            $q->where('status', $filters['status']);
        }

        return $q->with(['branch', 'type']);
    }
}
