<?php

namespace App\Services\HR\AttendanceHelpers\Reports;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * MissingCheckoutService
 *
 * Finds employees who have an accepted check-in for a given date
 * but have not recorded an accepted check-out — i.e. forgot to check out.
 */
class MissingCheckoutService
{
    /**
     * Retrieve employees missing a checkout on the given date.
     *
     * @param  Carbon|string|null  $date     Target date (defaults to today)
     * @param  array               $filters  Optional: branch_id, department_id
     * @return Collection
     */
    public function getMissingCheckouts(Carbon|string|null $date = null, array $filters = []): Collection
    {
        $date = $this->resolveDate($date);

        // ── 1. Collect (employee_id, period_id) pairs that already have a checkout ──
        $checkedOutPairs = Attendance::query()
            ->where('check_type', Attendance::CHECKTYPE_CHECKOUT)
            ->where('accepted', 1)
            ->where('check_date', $date)
            ->get(['employee_id', 'period_id'])
            ->map(fn($r) => $r->employee_id . '_' . $r->period_id)
            ->unique();

        // ── 2. Fetch accepted check-ins with no matching checkout pair ─────────────
        $query = Attendance::query()
            ->with(['employee:id,name,branch_id', 'period:id,name,start_at,end_at'])
            ->where('check_type', Attendance::CHECKTYPE_CHECKIN)
            ->where('accepted', 1)
            ->where('check_date', $date)
            ->whereNotExists(function ($sub) use ($date) {
                $sub->from('hr_attendances as co')
                    ->whereColumn('co.employee_id', 'hr_attendances.employee_id')
                    ->whereColumn('co.period_id',   'hr_attendances.period_id')
                    ->where('co.check_date',  $date)
                    ->where('co.check_type',  Attendance::CHECKTYPE_CHECKOUT)
                    ->where('co.accepted',    1)
                    ->whereNull('co.deleted_at');
            });

        // ── 3. Apply optional filters ───────────────────────────────────────────────
        if (!empty($filters['branch_id'])) {
            $query->whereHas(
                'employee',
                fn($q) =>
                $q->where('branch_id', (int) $filters['branch_id'])
            );
        }

        if (!empty($filters['department_id'])) {
            $query->whereHas(
                'employee',
                fn($q) =>
                $q->where('department_id', (int) $filters['department_id'])
            );
        }

        // ── 4. Keep earliest check-in per (employee, period) to avoid duplicates ───
        return $query
            ->orderBy('check_time')
            ->get()
            ->unique(fn($r) => $r->employee_id . '_' . $r->period_id)
            ->values()
            ->map(fn($r) => [
                'employee_id'     => $r->employee_id,
                'employee_name'   => $r->employee?->name,
                'branch_id'       => $r->employee?->branch_id,
                'checkin_time'    => $r->check_time,
                'checkin_date'    => $r->check_date,
                'attendance_id'   => $r->id,
                'period_id'       => $r->period_id,
                'period_name'     => $r->period?->name,
                'period_start_at' => $r->period?->start_at,
                'period_end_at'   => $r->period?->end_at,
                'status'          => $r->status,
            ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    protected function resolveDate(Carbon|string|null $date): string
    {
        if ($date instanceof Carbon) {
            return $date->toDateString();
        }

        return $date ? Carbon::parse($date)->toDateString() : Carbon::today()->toDateString();
    }
}
