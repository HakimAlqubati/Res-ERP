<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeBranchLog extends Model
{
    use HasFactory;

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
     *
     * السجل "فعّال" إذا كان:
     *   - بدأ قبل نهاية الفترة، و
     *   - لم ينتهِ بعد (end_at = null) أو انتهى بعد بداية الفترة
     */
    public static function getForPeriod(int $employeeId, Carbon $periodStart, Carbon $periodEnd): Collection
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
     *
     * مثال: سجل من 5 يناير إلى 20 يناير، فترة الراتب 1–31 يناير → 16 يوم
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
}
