<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Employee;
use App\Enums\HR\Payroll\SalaryAllocationRule;
use Illuminate\Database\Eloquent\Model;

class EmployeeBranchLog extends Model
{
    use HasFactory;

    // - [x] إنشاء الـ Enum الاحترافي `SalaryAllocationRule` (PROPORTIONAL, FIRST_BRANCH, LAST_BRANCH)
    // - [x] إضافة محرك توزيع الرواتب `getSalarySegments` في موديل `EmployeeBranchLog`
    // - [x] تحديث `PayrollSimulationService` لاستخدام المنطق الموحد ودعم الفترات المخصصة
    // - [x] تحديث `PayrollRunService` لدعم الموظفين المنتقلين وتعدد الفترات
    // - [x] التحقق واصلاح أخطاء الـ Type Hinting

    protected $table = 'hr_employee_branch_logs';
    protected $fillable = ['employee_id', 'branch_id', 'start_at', 'end_at', 'created_by'];

    // protected $casts = [
    //     'start_at' => 'datetime',
    //     'end_at'   => 'datetime',
    // ];

    // Define the relationship with the Employee model
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    // Define the relationship with the Branch model (assuming you have a Branch model)
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers for Payroll Branch Transfer
    // ─────────────────────────────────────────────────────────────

    /**
     * جلب سجلات الفرع الفعّالة للموظف التي تتقاطع مع فترة الراتب.
     */
    public static function getForPeriod(int $employeeId, Carbon $periodStart, Carbon $periodEnd): EloquentCollection
    {
        return static::where('employee_id', $employeeId)
            ->where('start_at', '<=', $periodEnd)
            ->where(function ($q) use ($periodStart) {
                $q->whereNull('end_at')
                  ->orWhere('end_at', '>=', $periodStart);
            })
            ->orderBy('start_at')
            ->get();
    }

    /**
     * حساب عدد الأيام المتقاطعة بين هذا السجل وفترة الراتب.
     */
    public function daysOverlapWith(Carbon $periodStart, Carbon $periodEnd): int
    {
        $from = Carbon::parse($this->start_at)->max($periodStart);
        $to   = Carbon::parse($this->end_at ?? $periodEnd)->min($periodEnd);

        return max(0, (int) $from->diffInDays($to) + 1);
    }

    /**
     * جلب قائمة معرّفات الموظفين (employee_ids) الذين كانوا ينتمون لهذا الفرع في هذه الفترة.
     */
    public static function getEmployeesForBranchInRange(int $branchId, Carbon $startDate, Carbon $endDate): array
    {
        return static::where('branch_id', $branchId)
            ->where('start_at', '<=', $endDate->toDateString())
            ->where(function ($q) use ($startDate) {
                $q->whereNull('end_at')
                  ->orWhere('end_at', '>=', $startDate->toDateString());
            })
            ->distinct()
            ->pluck('employee_id')
            ->toArray();
    }

    /**
     * جلب فترة البداية والنهاية الفعلية للموظف داخل فرع معين خلال فترة زمنية محددة.
     */
    public static function getAssignmentRange(int $employeeId, int $branchId, Carbon $periodStart, Carbon $periodEnd): ?array
    {
        $log = static::where('employee_id', $employeeId)
            ->where('branch_id', $branchId)
            ->where('start_at', '<=', $periodEnd->toDateString())
            ->where(function ($q) use ($periodStart) {
                $q->whereNull('end_at')
                  ->orWhere('end_at', '>=', $periodStart->toDateString());
            })
            ->first();

        if (!$log) {
            return null;
        }

        return [
            'start' => Carbon::parse($log->start_at)->max($periodStart),
            'end'   => Carbon::parse($log->end_at ?? $periodEnd)->min($periodEnd),
        ];
    }

    /**
     * محرك توزيع الرواتب (Salary Allocation Engine):
     * يحدد فترات الاستحقاق للفرع بناءً على القاعدة المختارة.
     */
    public static function getSalarySegments(
        Employee $employee,
        Carbon $periodStart,
        Carbon $periodEnd,
        ?int $targetBranchId = null,
        ?SalaryAllocationRule $rule = null
    ): Collection {
        $rule = $rule ?? SalaryAllocationRule::PROPORTIONAL;

        // 1. جلب كل السجلات التي تتقاطع مع فترة الراتب
        $logs = static::where('employee_id', $employee->id)
            ->where('start_at', '<=', $periodEnd->toDateTimeString())
            ->where(function ($q) use ($periodStart) {
                $q->whereNull('end_at')
                    ->orWhere('end_at', '>=', $periodStart->toDateTimeString());
            })
            ->orderBy('start_at')
            ->get();

        if ($logs->isEmpty()) {
            return collect();
        }

        $segments = collect();

        switch ($rule) {
            case SalaryAllocationRule::PROPORTIONAL:
                foreach ($logs as $log) {
                    if ($targetBranchId && $log->branch_id != $targetBranchId) {
                        continue;
                    }

                    $segments->push([
                        'branch_id' => (int) $log->branch_id,
                        'start'     => Carbon::parse($log->start_at)->max($periodStart),
                        'end'       => Carbon::parse($log->end_at ?? $periodEnd)->min($periodEnd),
                    ]);
                }
                break;

            case SalaryAllocationRule::FIRST_BRANCH:
                $firstLog = $logs->first();
                if (is_null($targetBranchId) || $firstLog->branch_id == $targetBranchId) {
                    $segments->push([
                        'branch_id' => (int) $firstLog->branch_id,
                        'start'     => $periodStart->copy(),
                        'end'       => $periodEnd->copy(),
                    ]);
                }
                break;

            case SalaryAllocationRule::LAST_BRANCH:
                $lastLog = $logs->last();
                if (is_null($targetBranchId) || $lastLog->branch_id == $targetBranchId) {
                    $segments->push([
                        'branch_id' => (int) $lastLog->branch_id,
                        'start'     => $periodStart->copy(),
                        'end'       => $periodEnd->copy(),
                    ]);
                }
                break;
        }

        return $segments;
    }
}
