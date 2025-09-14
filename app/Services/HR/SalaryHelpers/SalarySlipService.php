<?php

declare(strict_types=1);

namespace App\Services\HR\SalaryHelpers;

use App\Models\Employee;
use App\Models\SalaryTransaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SalarySlipService
{
    /**
     * يبني بيانات عرض قسيمة الراتب للموظف والسنة/الشهر المحددين.
     *
     * @return array Payload جاهز للتمرير إلى الـ view
     *
     * structure:
     *  [
     *      'employee'              => Employee,
     *      'branch'                => Branch|null,
     *      'monthName'             => string,
     *      'data'                  => object { details: [ { overtime_pay, total_incentives, net_salary } ] },
     *      'employeeAllowances'    => array<array{allowance_name:string, amount:float}>,
     *      'employeeDeductions'    => array<array{deduction_name:string, deduction_amount:float}>,
     *      'totalAllowanceAmount'  => float,
     *      'totalDeductionAmount'  => float,
     *  ]
     */
    public function build(int $employeeId, int $year, int $month): array
    {
        // الموظف + الفرع
        /** @var Employee $employee */
        $employee = Employee::with('branch')->find($employeeId);
        if (! $employee) {
            throw new ModelNotFoundException("Employee {$employeeId} not found.");
        }
        $branch = $employee->branch;

        // جلب الحركات المعتمدة لنفس السنة/الشهر
        /** @var Collection<int, SalaryTransaction> $tx */
        $tx = SalaryTransaction::query()
            ->where('employee_id', $employeeId)
            ->where('year', $year)
            ->where('month', $month)
            ->where('status', SalaryTransaction::STATUS_APPROVED)
            ->get();

        // تقسيم الحركات (موجب = إيراد، سالب = خصم)
        // تقسيم الحركات بناءً على operation
        $earnings   = $tx->filter(fn(SalaryTransaction $t) => $t->operation === SalaryTransaction::OPERATION_ADD);
        $deductions = $tx->filter(fn(SalaryTransaction $t) => $t->operation === SalaryTransaction::OPERATION_SUB);


        // البنود الأساسية بحسب النوع
        $basicSalary  = (float) $tx->where('type', 'salary')->sum('amount');
        $overtimePay  = (float) $tx->where('type', 'overtime')->sum('amount');
        $bonusesTotal = (float) $tx->where('type', 'bonus')->sum('amount');

        // تفاصيل البدلات (لعمود الـ Earnings)
        $employeeAllowances = $tx->where('type', 'allowance')
            ->map(function (SalaryTransaction $t) {
                $name = $t->description ?: (is_string($t->sub_type) ? ucfirst(str_replace('_', ' ', $t->sub_type)) : 'Allowance');
                return [
                    'allowance_name' => $name,
                    'amount'         => round((float) $t->amount, 2),
                ];
            })
            ->values()
            ->all();

        // تفاصيل الخصومات (لعمود الـ Deductions)
        $employeeDeductions = $tx->filter(fn($t) => in_array($t->type, ['deduction', 'penalty', 'installment', 'advance'], true))
            ->map(function (SalaryTransaction $t) {
                $name = $t->description ?: (is_string($t->sub_type) ? ucfirst(str_replace('_', ' ', $t->sub_type)) : 'Deduction');
                return [
                    'deduction_name'   => $name,
                    'deduction_amount' => round(abs((float) $t->amount), 2), // موجب للعرض
                ];
            })
            ->values()
            ->all();

        // المجاميع
        $totalEarnings   = round((float) $earnings->sum('amount'), 2);
        $totalDeductions = round((float) $deductions->sum('amount'), 2);
        $netSalary       = round($totalEarnings - $totalDeductions, 2);
        // الحفاظ على أسماء/هيكلة المتغيرات المستخدمة في القالب الحالي:
        $data = (object) [
            'details' => [[
                'overtime_pay'     => round($overtimePay, 2),
                'total_incentives' => round($bonusesTotal, 2),
                'net_salary'       => $netSalary,
            ]],
        ];
 
        $totalAllowanceAmount = $totalEarnings;   // إجمالي العمود الأيسر (Earnings)
        $totalDeductionAmount = $totalDeductions; // إجمالي العمود الأيمن (Deductions)
        $monthName            = Carbon::create($year, $month, 1)->format('F Y');

        return [
            'employee'             => $employee,
            'branch'               => $branch,
            'monthName'            => $monthName,
            'data'                 => $data,
            'employeeAllowances'   => $employeeAllowances,
            'employeeDeductions'   => $employeeDeductions,
            'totalAllowanceAmount' => $totalAllowanceAmount,
            'totalDeductionAmount' => $totalDeductionAmount,
        ];
    }
}
