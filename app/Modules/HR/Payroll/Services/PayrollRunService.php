<?php

namespace App\Modules\HR\Payroll\Services;

use App\Enums\HR\Payroll\SalaryAllocationRule;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeBranchLog;
use App\Models\Payroll;
use App\Models\PayrollRun;
use App\Models\SalaryTransaction;
use App\Models\Setting;
use App\Modules\HR\Payroll\Contracts\PayrollRunnerInterface;
use App\Modules\HR\Payroll\DTOs\RunPayrollData;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PayrollRunService implements PayrollRunnerInterface
{
    public function __construct(
        protected PayrollSimulationService $simulator,
        protected PayrollCalculationService $calculator, // للحركات المالية فقط
    ) {}

    // ─────────────────────────────────────────────────────────────
    // Public API
    // ─────────────────────────────────────────────────────────────

    /**
     * معاينة الرواتب لفرع معين بدون حفظ في قاعدة البيانات.
     */
    public function simulate(RunPayrollData $input): array
    {
        [$periodStart, $periodEnd] = $this->computePeriod($input->year, $input->month);

        $employees = $this->eligibleEmployees($input->branchId, $input->year, $input->month, $periodStart, $periodEnd, $input->employeeIds);

        if ($employees->isEmpty()) {
            return [
                'success' => true,
                'message' => 'No eligible employees found for this branch and payroll period.',
                'meta'    => [
                    'branch_id'    => $input->branchId,
                    'year'         => $input->year,
                    'month'        => $input->month,
                    'period_start' => $periodStart->toDateString(),
                    'period_end'   => $periodEnd->toDateString(),
                ],
                'totals' => ['base_salary' => 0.0, 'overtime_amount' => 0.0, 'total_allowances' => 0.0, 'total_deductions' => 0.0, 'gross_salary' => 0.0, 'net_salary' => 0.0, 'count' => 0],
                'items'  => [],
            ];
        }

        // استدعاء المحاكي للموظفين على كامل الفترة الشهرية ليتم احتساب وتوزيع الإجازات التلقائية بشكل موحد
        $simulations = $this->simulator->simulateForEmployees(
            $employees->pluck('id')->all(),
            $input->year,
            $input->month,
            $input->branchId,
            $periodStart,
            $periodEnd,
        );

        $items  = [];
        $totals = ['base_salary' => 0.0, 'overtime_amount' => 0.0, 'total_allowances' => 0.0, 'total_deductions' => 0.0, 'gross_salary' => 0.0, 'net_salary' => 0.0, 'count' => 0];

        foreach ($simulations as $calc) {
            if (!($calc['success'] ?? false)) {
                continue;
            }

            $row = [
                'employee_id'       => $calc['employee_id'],
                'employee_name'     => $calc['name'],
                'branch_id'         => $calc['branch_id'] ?? $calc['data']['branch_id'] ?? $input->branchId,
                'period_start'      => $calc['data']['period_start']     ?? $periodStart->toDateString(),
                'period_end'        => $calc['data']['period_end']       ?? $periodEnd->toDateString(),
                'base_salary'       => $calc['data']['base_salary']      ?? 0,
                'overtime_amount'   => $calc['data']['overtime_amount']  ?? 0,
                'total_allowances'  => $calc['data']['allowance_total']  ?? 0,
                'total_deductions'  => $calc['total_deduction']          ?? 0,
                'gross_salary'      => $calc['data']['gross_salary']     ?? 0,
                'net_salary'        => $calc['data']['net_salary']       ?? 0,
                'transactions'      => $calc['transactions']             ?? [],
                'penalties'         => $calc['penalties']                ?? [],
                'penalty_total'     => $calc['penalty_total']            ?? 0,
                'daily_rate_method' => $calc['daily_rate_method']        ?? null,
            ];

            $items[] = $row;
            foreach (['base_salary', 'overtime_amount', 'total_allowances', 'total_deductions', 'gross_salary', 'net_salary'] as $key) {
                $totals[$key] += $row[$key];
            }
            $totals['count']++;
        }

        return [
            'success' => true,
            'message' => 'Salary preview simulation completed (no DB writes).',
            'meta'    => [
                'branch_id'    => $input->branchId,
                'year'         => $input->year,
                'month'        => $input->month,
                'period_start' => $periodStart->toDateString(),
                'period_end'   => $periodEnd->toDateString(),
            ],
            'totals' => $totals,
            'items'  => $items,
        ];
    }

    /**
     * تشغيل الرواتب وحفظها في قاعدة البيانات.
     */
    public function runAndPersist(RunPayrollData $input): array
    {
        [$periodStart, $periodEnd] = $this->computePeriod($input->year, $input->month);

        $employees = $this->eligibleEmployees($input->branchId, $input->year, $input->month, $periodStart, $periodEnd, $input->employeeIds);

        if ($employees->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No eligible employees found for this branch and payroll period.',
                'meta'    => ['branch_id' => $input->branchId, 'year' => $input->year, 'month' => $input->month],
                'rows'    => [],
            ];
        }

        $run = $this->resolvePayrollRun($input, $periodStart, $periodEnd);

        $created = 0;
        $updated = 0;
        $rows    = [];

        // استدعاء المحاكي للموظفين على كامل الفترة الشهرية لاحتساب وتوزيع الإجازات التلقائية بشكل موحد
        $simulations = $this->simulator->simulateForEmployees(
            $employees->pluck('id')->all(),
            $input->year,
            $input->month,
            $input->branchId,
            $periodStart,
            $periodEnd,
        );

        $employeeMap = $employees->keyBy('id');

        DB::transaction(function () use ($simulations, $employeeMap, $input, $run, &$created, &$updated, &$rows) {
            foreach ($simulations as $simulation) {
                if (!$simulation || !($simulation['success'] ?? false)) {
                    $rows[] = [
                        'employee_id' => $simulation['employee_id'] ?? null,
                        'status'      => 'failed',
                        'message'     => $simulation['message'] ?? 'Unknown error',
                    ];
                    continue;
                }

                $employeeId = $simulation['employee_id'];
                $employee   = $employeeMap->get($employeeId) ?? Employee::find($employeeId);

                $log = (object) [
                    'branch_id' => (int) ($simulation['branch_id'] ?? $simulation['data']['branch_id'] ?? $input->branchId),
                    'start'     => Carbon::parse($simulation['data']['period_start']),
                    'end'       => Carbon::parse($simulation['data']['period_end']),
                ];

                // تحويل شكل نتيجة المحاكي إلى الشكل المتوقع من fillPayroll/generateSalaryTransactions
                $calc = [
                    'base_salary'      => $simulation['data']['base_salary']     ?? 0,
                    'overtime_amount'  => $simulation['data']['overtime_amount']  ?? 0,
                    'total_allowances' => $simulation['data']['allowance_total']  ?? 0,
                    'total_bonus'      => 0,
                    'total_deductions' => $simulation['total_deduction']          ?? 0,
                    'gross_salary'     => $simulation['data']['gross_salary']     ?? 0,
                    'net_salary'       => $simulation['data']['net_salary']       ?? 0,
                    'transactions'     => $simulation['transactions']             ?? [],
                    'penalties'        => $simulation['penalties']                ?? [],
                    'penalty_total'    => $simulation['penalty_total']            ?? 0,
                ];

                $payroll = $this->resolvePayroll($employee, $log, $input, $run);

                if ($payroll === null) {
                    $rows[] = ['employee_id' => $employee->id, 'status' => 'skipped_existing'];
                    continue;
                }

                $wasExisting = $payroll->exists;
                $this->fillPayroll($payroll, $calc, $run, $log);
                $payroll->save();

                if ($input->overwriteExisting) {
                    $this->clearTransactions($employee, $log, $input, $run, $payroll);
                }

                $this->calculator->generateSalaryTransactions($run, $employee, $calc, $log->end, $payroll);

                $rows[] = [
                    'employee_id' => $employee->id,
                    'status'      => $wasExisting ? 'updated' : 'created',
                    'payroll_id'  => $payroll->id,
                    'net'         => $payroll->net_salary,
                ];

                $wasExisting ? $updated++ : $created++;
            }

            $this->updateRunTotals($run);
        });

        $allSkipped = !empty($rows) && collect($rows)->every(fn($r) => $r['status'] === 'skipped_existing');

        if ($allSkipped) {
            return [
                'success' => false,
                'message' => 'No payrolls processed: all employees already have payrolls for this period.',
                'meta'    => ['payroll_run_id' => $run->id, 'branch_id' => $input->branchId, 'year' => $input->year, 'month' => $input->month],
            ];
        }

        return [
            'success' => true,
            'message' => 'Payrolls persisted successfully.',
            'meta'    => [
                'payroll_run_id' => $run->id,
                'branch_id'      => $input->branchId,
                'year'           => $input->year,
                'month'          => $input->month,
                'created'        => $created,
                'updated'        => $updated,
            ],
            'rows' => $rows,
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // Private Helpers
    // ─────────────────────────────────────────────────────────────

    /**
     * تحويل الموظفين إلى فترات عمل (Segments) بناءً على قاعدة التوزيع.
     */
    private function buildSegments(int $branchId, int $year, int $month, Carbon $periodStart, Carbon $periodEnd, ?array $employeeIds = null): Collection
    {
        $employees = $this->eligibleEmployees($branchId, $year, $month, $periodStart, $periodEnd, $employeeIds);

        return $employees->flatMap(
            fn(Employee $employee) => EmployeeBranchLog::getSalarySegments(
                $employee,
                $periodStart,
                $periodEnd,
                $this->segmentTargetBranchId($employee, $branchId),
            )
                ->map(fn($seg) => ['employee' => $employee, 'log' => (object) $seg])
        );
    }

    /**
     * For proportional allocation, one payroll run may contain payroll rows for all
     * branch periods of an employee who touched the selected branch in the month.
     */
    private function segmentTargetBranchId(Employee $employee, int $branchId): ?int
    {
        return $employee->getEffectiveSalaryAllocationRule() === SalaryAllocationRule::PROPORTIONAL
            ? null
            : $branchId;
    }

    /**
     * جلب الموظفين المؤهلين للرواتب بناءً على سجلات الفروع التاريخية.
     */
    private function eligibleEmployees(int $branchId, int $year, int $month, Carbon $periodStart, Carbon $periodEnd, ?array $employeeIds = null): Collection
    {
        $idsInLog = EmployeeBranchLog::getEmployeesForBranchInRange($branchId, $periodStart, $periodEnd);

        return Employee::query()
            ->eligibleForPayroll($year, $month)
            ->whereIn('id', $idsInLog)
            ->when($employeeIds, fn($q) => $q->whereIn('id', $employeeIds))
            ->get()
            ->filter(fn(Employee $employee) => $this->isEmployeeOwnedByBranchForPayroll($employee, $branchId, $periodStart, $periodEnd))
            ->values();
    }

    /**
     * Proportional salaries are generated only from the employee's last/current
     * branch in the payroll period; that run then creates the branch split rows.
     */
    private function isEmployeeOwnedByBranchForPayroll(Employee $employee, int $branchId, Carbon $periodStart, Carbon $periodEnd): bool
    {
        if ($employee->getEffectiveSalaryAllocationRule() !== SalaryAllocationRule::PROPORTIONAL) {
            return true;
        }

        $ownerSegment = EmployeeBranchLog::getSalarySegments(
            $employee,
            $periodStart,
            $periodEnd,
            null,
            SalaryAllocationRule::LAST_BRANCH,
        )->first();

        return (int) ($ownerSegment['branch_id'] ?? 0) === $branchId;
    }

    /**
     * إنشاء أو جلب PayrollRun للفترة الحالية.
     */
    private function resolvePayrollRun(RunPayrollData $input, Carbon $periodStart, Carbon $periodEnd): PayrollRun
    {
        $run = PayrollRun::query()
            ->where('branch_id', $input->branchId)
            ->where('year',      $input->year)
            ->where('month',     $input->month)
            ->where('status',    '!=', PayrollRun::STATUS_APPROVED)
            ->first();

        if ($run) {
            return $run;
        }

        $branch    = Branch::findOrFail($input->branchId);
        $monthName = Carbon::create($input->year, $input->month, 1)->format('M Y');

        $run = new PayrollRun([
            'status'            => PayrollRun::STATUS_PENDING,
            'branch_id'         => $input->branchId,
            'year'              => $input->year,
            'month'             => $input->month,
            'period_start_date' => $periodStart->toDateString(),
            'period_end_date'   => $periodEnd->toDateString(),
            'name'              => "{$monthName} - {$branch->name}",
            'pay_date'          => $input->payDate ? Carbon::parse($input->payDate)->toDateString() : now()->toDateString(),
            'total_gross'       => 0,
            'total_net'         => 0,
            'total_allowances'  => 0,
            'total_deductions'  => 0,
        ]);

        // Scope the Observer's pending-check to only the targeted employees (if any).
        $run->pendingCheckEmployeeIds = $input->employeeIds;

        $run->save();

        return $run;
    }

    /**
     * جلب سجل الراتب الموجود أو إنشاء واحد جديد.
     * يُعيد null في حالة وجود سجل ولم يُطلب الكتابة فوقه.
     */
    private function resolvePayroll(Employee $employee, object $log, RunPayrollData $input, PayrollRun $run): ?Payroll
    {
        $existing = Payroll::query()
            ->where('employee_id',      $employee->id)
            ->where('branch_id',        $log->branch_id)
            ->where('year',             $input->year)
            ->where('month',            $input->month)
            ->where('period_start_date', $log->start->toDateString())
            ->first();

        if ($existing && !$input->overwriteExisting) {
            return null; // تخطي
        }

        return $existing ?? new Payroll([
            'employee_id'       => $employee->id,
            'branch_id'         => $log->branch_id,
            'year'              => $input->year,
            'month'             => $input->month,
            'period_start_date' => $log->start->toDateString(),
        ]);
    }

    /**
     * تعبئة بيانات الراتب من نتيجة الاحتساب.
     */
    private function fillPayroll(Payroll $payroll, array $calc, PayrollRun $run, object $log): void
    {
        $payroll->payroll_run_id  = $run->id;
        $payroll->period_end_date = $log->end->toDateString();
        $payroll->base_salary     = $calc['base_salary']     ?? 0;
        $payroll->total_allowances = $calc['total_allowances'] ?? 0;
        $payroll->total_bonus     = $calc['total_bonus']     ?? 0;
        $payroll->overtime_amount = $calc['overtime_amount'] ?? 0;
        $payroll->total_deductions = $calc['total_deductions'] ?? 0;
        $payroll->gross_salary    = $calc['gross_salary']    ?? 0;
        $payroll->net_salary      = $calc['net_salary']      ?? 0;
        $payroll->status          = $payroll->status ?? Payroll::STATUS_PENDING;
    }

    /**
     * حذف حركات الراتب القديمة عند إعادة الاحتساب (overwrite).
     */
    private function clearTransactions(Employee $employee, object $log, RunPayrollData $input, PayrollRun $run, Payroll $payroll): void
    {
        SalaryTransaction::query()
            ->where('employee_id',   $employee->id)
            // ->where('branch_id',     $log->branch_id)
            ->where('year',          $input->year)
            ->where('month',         $input->month)
            ->where('payroll_run_id', $run->id)
            ->where('payroll_id',    $payroll->id)
            ->delete();
    }

    /**
     * تحديث مجاميع الـ PayrollRun من قاعدة البيانات.
     */
    private function updateRunTotals(PayrollRun $run): void
    {
        $run->total_gross      = round($run->payrolls()->sum('gross_salary'), 2);
        $run->total_net        = round($run->payrolls()->sum('net_salary'), 2);
        $run->total_allowances = round($run->payrolls()->sum('total_allowances'), 2);
        $run->total_deductions = round($run->payrolls()->sum('total_deductions'), 2);
        $run->save();
    }

    /**
     * حساب فترة الراتب (بداية ونهاية الشهر).
     */
    private function computePeriod(int $year, int $month): array
    {
        if ($month < 1 || $month > 12) {
            throw new InvalidArgumentException('Invalid month value.');
        }

        $periodStart = Carbon::create($year, $month, 1)->startOfMonth();
        $periodEnd   = Carbon::create($year, $month, 1)->endOfMonth();

        // تقليص نهاية الفترة إذا كان الشهر الحالي
        if ($year == now()->year && $month == now()->month) {
            $periodEnd = now()->endOfDay();
        }

        return [$periodStart, $periodEnd];
    }
}
