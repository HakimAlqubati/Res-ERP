<?php

declare(strict_types=1);

namespace App\Modules\HR\Payroll\Reports;

use App\Models\Branch;
use App\Models\Employee;
use App\Modules\HR\Payroll\DTOs\PrePayrollDeductionFilterDTO;
use App\Modules\HR\Payroll\Services\PayrollSimulationService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * تقرير الاستقطاعات المتوقعة قبل إنشاء البايرول.
 *
 * يعيد استخدام PayrollSimulationService لجلب الخصومات المحسوبة
 * من المصادر الأصلية (الحضور، الجزاءات، السلف، الخصومات العامة)
 * بدون أي كتابة في قاعدة البيانات.
 *
 * الناتج يطابق هيكل DeductionReport::getSummary() لإعادة استخدام نفس الـ View.
 */
class PrePayrollDeductionReport
{
    public function __construct(
        protected PayrollSimulationService $simulator,
    ) {}

    /**
     * تشغيل المحاكاة وإرجاع ملخص الاستقطاعات بنفس هيكل DeductionReport.
     */
    public function getSummary(PrePayrollDeductionFilterDTO $filters): array
    {
        $employeeIds = $this->resolveEmployeeIds($filters);

        if (empty($employeeIds)) {
            return $this->emptyResponse($filters);
        }

        $simulationResults = $this->simulator->simulateForEmployees(
            employeeIds: $employeeIds,
            year:        $filters->year,
            month:       $filters->month,
            branchId:    count($filters->branchIds ?? []) === 1 ? $filters->branchIds[0] : null,
        );

        $monthKey  = sprintf('%04d-%02d', $filters->year, $filters->month);
        $monthName = Carbon::createFromFormat('Y-m', $monthKey)->translatedFormat('F Y');

        $employeesMap = Employee::with('branch')->whereIn('id', $employeeIds)->get()->keyBy('id');

        $employeesDeductions = $this->buildEmployeesDeductions(
            $simulationResults,
            $monthKey,
            $monthName,
            $employeesMap
        );

        if (empty($employeesDeductions)) {
            return $this->emptyResponse($filters);
        }

        $grandTotal = round(array_sum(array_column($employeesDeductions, 'total_deductions')), 2);

        return [
            'report_title'        => $this->resolveReportTitle($filters),
            'from_date'           => Carbon::create($filters->year, $filters->month, 1)->format('Y-m-d'),
            'to_date'             => Carbon::create($filters->year, $filters->month, 1)->endOfMonth()->format('Y-m-d'),
            'employee_id'         => $filters->employeeId,
            'branch_ids'          => $filters->branchIds,
            'monthly_deductions'  => $this->buildMonthlyAggregate($employeesDeductions, $monthKey, $monthName),
            'employees_deductions' => $employeesDeductions,
            'grand_total'         => $grandTotal,
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * تحديد معرفات الموظفين بناءً على الفلاتر.
     *
     * @return int[]
     */
    private function resolveEmployeeIds(PrePayrollDeductionFilterDTO $filters): array
    {
        $query = Employee::query()->where('active', 1)->select('id');

        if ($filters->employeeId) {
            return [$filters->employeeId];
        }

        if (!empty($filters->branchIds)) {
            $query->whereIn('branch_id', $filters->branchIds);
        }

        return $query->pluck('id')->all();
    }

    /**
     * بناء بيانات الاستقطاعات لكل موظف من نتائج المحاكاة.
     *
     * نصفّي من transactions[] كل ما هو operation='-' (خصم).
     */
    private function buildEmployeesDeductions(
        array  $simulationResults,
        string $monthKey,
        string $monthName,
        Collection $employeesMap
    ): array {
        $result = [];

        foreach ($simulationResults as $sim) {
            if (! ($sim['success'] ?? false)) {
                continue;
            }

            $deductions = $this->extractDeductionsFromTransactions(
                $sim['transactions'] ?? [],
            );

            if (empty($deductions)) {
                continue;
            }

            $total = round(array_sum(array_column($deductions, 'deduction_amount')), 2);

            $emp = $employeesMap[$sim['employee_id']] ?? null;

            $result[] = [
                'employee_id'       => $sim['employee_id'],
                'employee_name'     => $sim['name'],
                'branch_name'       => $emp?->branch?->name ?? __('Unknown Branch'),
                'monthly_deductions' => [
                    [
                        'month'           => $monthKey,
                        'month_name'      => $monthName,
                        'deductions_list' => $deductions,
                        'month_total'     => $total,
                    ],
                ],
                'total_deductions'  => $total,
            ];
        }

        return $result;
    }

    /**
     * استخراج الخصومات من مصفوفة الحركات المالية.
     *
     * @param  array<int, array<string, mixed>> $transactions
     * @return array<int, array{deduction_name: string, deduction_amount: float}>
     */
    private function extractDeductionsFromTransactions(array $transactions): array
    {
        $grouped = [];

        foreach ($transactions as $tx) {
            if (($tx['operation'] ?? '') !== '-') {
                continue;
            }

            $name   = $tx['description'] ?: ucfirst(str_replace('_', ' ', $tx['sub_type'] ?? $tx['type'] ?? ''));
            $amount = round(abs((float) ($tx['amount'] ?? 0)), 2);

            if ($amount <= 0) {
                continue;
            }

            // دمج الخصومات المتكررة بنفس الاسم
            if (isset($grouped[$name])) {
                $grouped[$name]['deduction_amount'] = round($grouped[$name]['deduction_amount'] + $amount, 2);
            } else {
                $grouped[$name] = [
                    'deduction_name'   => $name,
                    'deduction_amount' => $amount,
                ];
            }
        }

        return array_values($grouped);
    }

    /**
     * بناء ملخص الاستقطاعات الشهرية على مستوى التقرير كله.
     */
    private function buildMonthlyAggregate(
        array  $employeesDeductions,
        string $monthKey,
        string $monthName,
    ): array {
        $merged = [];

        foreach ($employeesDeductions as $emp) {
            foreach ($emp['monthly_deductions'][0]['deductions_list'] ?? [] as $item) {
                $name = $item['deduction_name'];

                if (isset($merged[$name])) {
                    $merged[$name]['deduction_amount'] = round(
                        $merged[$name]['deduction_amount'] + $item['deduction_amount'],
                        2,
                    );
                } else {
                    $merged[$name] = $item;
                }
            }
        }

        $monthTotal = round(array_sum(array_column($merged, 'deduction_amount')), 2);

        return [
            [
                'month'           => $monthKey,
                'month_name'      => $monthName,
                'deductions_list' => array_values($merged),
                'month_total'     => $monthTotal,
            ],
        ];
    }

    /**
     * هيكل فارغ يطابق ناتج getSummary() لضمان ثبات التعاقد مع الـ View.
     */
    private function emptyResponse(PrePayrollDeductionFilterDTO $filters): array
    {
        return [
            'report_title'         => $this->resolveReportTitle($filters),
            'from_date'            => Carbon::create($filters->year, $filters->month, 1)->format('Y-m-d'),
            'to_date'              => Carbon::create($filters->year, $filters->month, 1)->endOfMonth()->format('Y-m-d'),
            'employee_id'          => $filters->employeeId,
            'branch_ids'           => $filters->branchIds,
            'monthly_deductions'   => [],
            'employees_deductions' => [],
            'grand_total'          => 0.0,
        ];
    }

    /**
     * عنوان التقرير بناءً على الفلاتر المحددة.
     */
    private function resolveReportTitle(PrePayrollDeductionFilterDTO $filters): string
    {
        if ($filters->employeeId) {
            return Employee::find($filters->employeeId)?->name ?? 'Unknown Employee';
        }

        if (!empty($filters->branchIds)) {
            $branches = Branch::whereIn('id', $filters->branchIds)->pluck('name');
            if ($branches->count() > 2) {
                return __('Multiple Branches');
            }
            return $branches->isNotEmpty() ? $branches->join(' & ') . ' - ' . __('Branch') : 'Unknown Branch';
        }

        return __('All Employees');
    }
}
