<?php

namespace App\Services\HR\Payroll;

use App\DTOs\HR\Payroll\RunPayrollData;
use App\Enums\HR\Payroll\SalaryTransactionType;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollRun;            // ← تأكد من الاستيراد
use App\Models\SalaryTransaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PayrollRunService
{
    public function __construct(
        protected PayrollCalculationService $calculator,
    ) {}

    public function simulate(RunPayrollData $input): array
    {
        [$periodStart, $periodEnd] = $this->computePeriod($input->year, $input->month);
        $employees = $this->eligibleEmployees($input->branchId);

        $items = [];
        $totals = [
            'base_salary'       => 0.0,
            'overtime_amount'   => 0.0,
            'total_allowances'  => 0.0,
            'total_deductions'  => 0.0,
            'gross_salary'      => 0.0,
            'net_salary'        => 0.0,
            'count'             => 0,
        ];

        foreach ($employees as $employee) {
            $calc = $this->calculator->calculateForEmployee($employee, $input->year, $input->month);

            $row = [
                'employee_id'       => $employee->id,
                'employee_name'     => $employee->name,
                'base_salary'       => $calc['base_salary'] ?? 0,
                'overtime_amount'   => $calc['overtime_amount'] ?? 0,
                'total_allowances'  => $calc['total_allowances'] ?? 0,
                'total_deductions'  => $calc['total_deductions'] ?? 0,
                'gross_salary'      => $calc['gross_salary'] ?? 0,
                'net_salary'        => $calc['net_salary'] ?? 0,
                'transactions'      => $calc['transactions'] ?? [],
                'penalties'         => $calc['penalties'] ?? [],
                'penalty_total'     => $calc['penalty_total'] ?? 0,
                'daily_rate_method' => $calc['daily_rate_method'] ?? null,
            ];

            $items[] = $row;
            $totals['base_salary']      += $row['base_salary'];
            $totals['overtime_amount']  += $row['overtime_amount'];
            $totals['total_allowances'] += $row['total_allowances'];
            $totals['total_deductions'] += $row['total_deductions'];
            $totals['gross_salary']     += $row['gross_salary'];
            $totals['net_salary']       += $row['net_salary'];
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
            'totals'  => $totals,
            'items'   => $items,
        ];
    }

    /**
     * Creates/updates PayrollRun, links Payroll + SalaryTransactions to it,
     * and updates run aggregates.
     */
    public function runAndPersist(RunPayrollData $input): array
    {
        [$periodStart, $periodEnd] = $this->computePeriod($input->year, $input->month);
        $branch = Branch::findOrFail($input->branchId);

        $employees = $this->eligibleEmployees($input->branchId);

        // 1) احضر/أنشئ سجل التشغيل للفترة
        $run = PayrollRun::query()
            ->where('branch_id', $input->branchId)
            ->where('year', $input->year)
            ->where('month', $input->month)
            ->first();

        if (! $run) {
            $run = new PayrollRun();
            $run->branch_id         = $input->branchId;
            $run->year              = $input->year;
            $run->month             = $input->month;
            $run->period_start_date = $periodStart->toDateString();
            $run->period_end_date   = $periodEnd->toDateString();
            $monthName = Carbon::create($input->year, $input->month, 1)->format('F Y');
            $run->name = "Payroll {$monthName} - $branch->name";
            $run->status            = 'pending';
            // اترك currency/fx_rate كما هي إن لم تكن موجودة في الـ DTO
            $run->total_gross       = 0;
            $run->total_net         = 0;
            $run->total_allowances  = 0;
            $run->total_deductions  = 0;
            $run->save();
        } else {
            $run->period_start_date = $periodStart->toDateString();
            $run->period_end_date   = $periodEnd->toDateString();
            $run->status            = 'pending';
            $run->save();
        }

        $created = 0;
        $updated = 0;
        $rows    = [];

        // مجاميع التشغيل
        $aggGross      = 0.0;
        $aggNet        = 0.0;
        $aggAllowances = 0.0;
        $aggDeductions = 0.0;

        DB::transaction(function () use ($employees, $input, $periodStart, $periodEnd, $run, &$created, &$updated, &$rows, &$aggGross, &$aggNet, &$aggAllowances, &$aggDeductions) {

            foreach ($employees as $employee) {
                $calc = $this->calculator->calculateForEmployee($employee, $input->year, $input->month);

                // 2) Payroll لكل موظف لنفس الفترة
                $payroll = Payroll::query()
                    ->where('employee_id', $employee->id)
                    ->where('year', $input->year)
                    ->where('month', $input->month)
                    ->first();

                if ($payroll && !$input->overwriteExisting) {
                    $rows[] = [
                        'employee_id' => $employee->id,
                        'status'      => 'skipped_existing',
                        'payroll_id'  => $payroll->id,
                    ];
                    continue;
                }

                if (! $payroll) {
                    $payroll = new Payroll();
                    $payroll->employee_id = $employee->id;
                    $payroll->branch_id   = $input->branchId;
                    $payroll->year        = $input->year;
                    $payroll->month       = $input->month;
                }

                // اربط تشغيل الرواتب
                $payroll->payroll_run_id    = $run->id; // ← المفتاح كما في موديلك
                $payroll->period_start_date = $periodStart->toDateString();
                $payroll->period_end_date   = $periodEnd->toDateString();
                $payroll->base_salary       = $calc['base_salary'] ?? 0;
                $payroll->total_allowances  = $calc['total_allowances'] ?? 0;
                $payroll->total_bonus       = $calc['total_bonus'] ?? 0;
                $payroll->overtime_amount   = $calc['overtime_amount'] ?? 0;
                $payroll->total_deductions  = $calc['total_deductions'] ?? 0;
                $payroll->gross_salary      = $calc['gross_salary'] ?? 0;
                $payroll->net_salary        = $calc['net_salary'] ?? 0;
                $payroll->status            = $payroll->status ?? Payroll::STATUS_PENDING;

                $wasExisting = $payroll->exists;
                $payroll->save();

                // 3) امسح حركات الموظف لنفس التشغيل فقط عند overwrite
                if ($input->overwriteExisting) {
                    SalaryTransaction::query()
                        ->where('employee_id', $employee->id)
                        ->where('year', $input->year)
                        ->where('month', $input->month)
                        ->where('payroll_run_id', $run->id) // مهم: لا تمسح تشغيلات أخرى لنفس الفترة
                        ->delete();
                }

                // 4) أنشئ الحركات المرتبطة بهذا التشغيل
                $this->calculator->generateSalaryTransactions(
                    $run,
                    $employee,
                    $calc,
                    $periodEnd,
                    $payroll
                );

                // 5) حدّث المجاميع للتشغيل
                $aggGross      += ($calc['gross_salary']      ?? 0);
                $aggNet        += ($calc['net_salary']        ?? 0);
                $aggAllowances += ($calc['total_allowances']  ?? 0);
                $aggDeductions += ($calc['total_deductions']  ?? 0);

                $rows[] = [
                    'employee_id' => $employee->id,
                    'status'      => $wasExisting ? 'updated' : 'created',
                    'payroll_id'  => $payroll->id,
                    'net'         => $payroll->net_salary,
                ];

                $wasExisting ? $updated++ : $created++;
            }

            // 6) حفظ مجاميع التشغيل وتحديث الحالة
            $run->total_gross      = round($aggGross, 2);
            $run->total_net        = round($aggNet, 2);
            $run->total_allowances = round($aggAllowances, 2);
            $run->total_deductions = round($aggDeductions, 2);
            $run->status           = 'completed';
            $run->save();
        });
        if (
            !empty($rows) && count(array_unique(array_column($rows, 'status'))) === 1
            && $rows[0]['status'] === 'skipped_existing'
        ) {
            return [
                'success' => false,
                'message' => 'No payrolls processed: all employees already have payrolls for this period.',
                'meta'    => [
                    'payroll_run_id' => $run->id,
                    'branch_id'      => $input->branchId,
                    'year'           => $input->year,
                    'month'          => $input->month,
                ]
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
            'rows'     => $rows,
        ];
    }

    /** Helpers **/

    protected function computePeriod(int $year, int $month): array
    {
        if ($month < 1 || $month > 12) {
            throw new InvalidArgumentException('Invalid month value.');
        }
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end   = Carbon::create($year, $month, 1)->endOfMonth();
        return [$start, $end];
    }

    protected function eligibleEmployees(int $branchId): Collection
    {
        return Employee::query()
            ->where('branch_id', $branchId)
            ->active()
            ->get();
    }
}
