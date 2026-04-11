<?php

declare(strict_types=1);

namespace App\Modules\HR\Payroll\Services;

use App\Models\Employee;
use App\Models\EmployeeBranchLog;
use App\Models\Payroll;
use App\Models\PayrollBranchSplit;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * BranchTransferPayrollResolver
 *
 * مسؤولية هذه الكلاس الوحيدة: اكتشاف ما إذا كان الموظف قد انتقل
 * بين الفروع خلال فترة الراتب، وإنشاء سجلات التوزيع المحاسبي
 * في جدول hr_payroll_branch_splits.
 *
 * ─────────────────────────────────────────────────────────────
 * هذه الكلاس لا تؤثر على حساب الراتب إطلاقاً.
 * تعمل فقط بعد اكتمال الحساب والحفظ.
 * ─────────────────────────────────────────────────────────────
 *
 * أوضاع التوزيع (تُقرأ من الإعدادات):
 *   - previous_branch : الفرع السابق يتحمل الراتب كاملاً
 *   - pro_rated       : توزيع نسبي بعدد الأيام (الافتراضي)
 *   - new_branch      : الفرع الجديد يتحمل الراتب كاملاً
 */
class BranchTransferPayrollResolver
{
    /**
     * هل انتقل الموظف بين الفروع خلال هذه الفترة؟
     * (يُعيد true إذا وجد أكثر من سجل في branch_logs)
     */
    public function hasTransfer(Employee $employee, Carbon $periodStart, Carbon $periodEnd): bool
    {
        return EmployeeBranchLog::getForPeriod($employee->id, $periodStart, $periodEnd)->count() > 1;
    }

    /**
     * بناء سجلات توزيع التكلفة وحفظها في hr_payroll_branch_splits.
     *
     * يُستدعى مباشرةً بعد حفظ الـ Payroll.
     * إذا لم يكن هناك انتقال → لا يفعل شيئاً.
     *
     * @param  Payroll  $payroll
     * @param  Employee $employee
     * @param  Carbon   $periodStart
     * @param  Carbon   $periodEnd
     * @param  float    $netSalary    صافي الراتب المحسوب
     * @param  string   $mode         الوضع من الإعداد
     */
    public function buildSplits(
        Payroll  $payroll,
        Employee $employee,
        Carbon   $periodStart,
        Carbon   $periodEnd,
        float    $netSalary,
        string   $mode = 'pro_rated'
    ): void {
        $logs = EmployeeBranchLog::getForPeriod($employee->id, $periodStart, $periodEnd);

        // لا انتقال → لا شيء
        if ($logs->count() <= 1) {
            return;
        }

        $totalDays = (int) $periodStart->diffInDays($periodEnd) + 1;

        $segments = match ($mode) {
            'previous_branch' => $this->buildAllOnFirst($logs, $totalDays, $netSalary, $periodStart, $periodEnd),
            'new_branch'      => $this->buildAllOnLast($logs, $totalDays, $netSalary, $periodStart, $periodEnd),
            default           => $this->buildProRated($logs, $totalDays, $netSalary, $periodStart, $periodEnd),
        };

        foreach ($segments as $segment) {
            PayrollBranchSplit::create([
                'payroll_id'       => $payroll->id,
                'employee_id'      => $employee->id,
                'branch_id'        => $segment['branch_id'],
                'from_date'        => $segment['from_date'],
                'to_date'          => $segment['to_date'],
                'days_count'       => $segment['days'],
                'total_days'       => $totalDays,
                'ratio'            => $segment['ratio'],
                'allocated_amount' => $segment['amount'],
                'liability_mode'   => $mode,
            ]);
        }

        Log::info("PayrollBranchSplit: Employee [{$employee->id}] has {$logs->count()} branch logs in period. Mode={$mode}. Splits created: " . count($segments));
    }

    // ─────────────────────────────────────────────────────────────
    // Private Builders
    // ─────────────────────────────────────────────────────────────

    /**
     * توزيع نسبي: كل فرع يدفع بحسب عدد أيامه.
     */
    private function buildProRated($logs, int $totalDays, float $salary, Carbon $start, Carbon $end): array
    {
        $segments  = [];
        $remaining = $salary;
        $count     = $logs->count();

        foreach ($logs as $i => $log) {
            $days  = $log->daysOverlapWith($start, $end);
            $ratio = $totalDays > 0 ? round($days / $totalDays, 4) : 0;

            // آخر سجل يأخذ الباقي لتفادي أخطاء التقريب
            $isLast = ($i === $count - 1);
            $amount = $isLast ? round($remaining, 2) : round($salary * $ratio, 2);
            $remaining -= $amount;

            $segments[] = [
                'branch_id' => $log->branch_id,
                'from_date' => Carbon::parse($log->start_at)->max($start)->toDateString(),
                'to_date'   => Carbon::parse($log->end_at ?? $end)->min($end)->toDateString(),
                'days'      => $days,
                'ratio'     => $ratio,
                'amount'    => $amount,
            ];
        }

        return $segments;
    }

    /**
     * الفرع الأول (السابق) يتحمل الراتب كاملاً.
     */
    private function buildAllOnFirst($logs, int $totalDays, float $salary, Carbon $start, Carbon $end): array
    {
        $first = $logs->first();
        return [[
            'branch_id' => $first->branch_id,
            'from_date' => $start->toDateString(),
            'to_date'   => $end->toDateString(),
            'days'      => $totalDays,
            'ratio'     => 1.0,
            'amount'    => $salary,
        ]];
    }

    /**
     * الفرع الأخير (الجديد) يتحمل الراتب كاملاً.
     */
    private function buildAllOnLast($logs, int $totalDays, float $salary, Carbon $start, Carbon $end): array
    {
        $last = $logs->last();
        return [[
            'branch_id' => $last->branch_id,
            'from_date' => $start->toDateString(),
            'to_date'   => $end->toDateString(),
            'days'      => $totalDays,
            'ratio'     => 1.0,
            'amount'    => $salary,
        ]];
    }
}
