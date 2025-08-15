<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\SalaryTransaction;
use Carbon\Carbon;

class SalarySlipController extends Controller
{
    public function show(int $employee, int $year, int $month)
    {
        // الموظف + الفرع
        $employeeModel = Employee::with('branch')->findOrFail($employee);
        $branch        = $employeeModel->branch;

        // جلب الحركات المعتمدة لنفس السنة/الشهر
        $tx = SalaryTransaction::query()
            ->where('employee_id', $employee)
            ->where('year', $year)
            ->where('month', $month)
            ->where('status', SalaryTransaction::STATUS_APPROVED)
            ->get();

        // تقسيم الحركات (موجب = إيراد، سالب = خصم)
        $earnings   = $tx->filter(fn ($t) => $t->amount > 0);
        $deductions = $tx->filter(fn ($t) => $t->amount < 0);

        // البنود الأساسية بحسب النوع (اختياري: عدِّل حسب تسمياتك)
        $basicSalary  = (float) $tx->where('type', 'salary')->sum('amount');
        $overtimePay  = (float) $tx->where('type', 'overtime')->sum('amount');
        $bonusesTotal = (float) $tx->where('type', 'bonus')->sum('amount');

        // تفاصيل البدلات (لعمود الـ Earnings)
        $employeeAllowances = $tx->where('type', 'allowance')
            ->map(fn($t) => [
                'allowance_name' => $t->description ?: (is_string($t->sub_type) ? ucfirst(str_replace('_', ' ', $t->sub_type)) : 'Allowance'),
                'amount'         => round((float) $t->amount, 2),
            ])->values()->all();

        // تفاصيل الخصومات (لعمود الـ Deductions)
        $employeeDeductions = $tx->filter(fn($t) => in_array($t->type, ['deduction', 'penalty', 'installment', 'advance']))
            ->map(fn($t) => [
                'deduction_name'   => $t->description ?: (is_string($t->sub_type) ? ucfirst(str_replace('_', ' ', $t->sub_type)) : 'Deduction'),
                'deduction_amount' => round(abs((float) $t->amount), 2), // موجب للعرض
            ])->values()->all();

        // المجاميع
        $totalEarnings       = round((float) $earnings->sum('amount'), 2);
        $totalDeductions     = round(abs((float) $deductions->sum('amount')), 2);
        $netSalary           = round($totalEarnings - $totalDeductions, 2);

        // إذا أردت الحفاظ على أسماء المتغيرات القديمة في القالب الحالي:
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

        // تمرير كل شيء إلى نفس القالب القديم (سنستبدل مصدر البيانات فقط)
        return view('export.reports.hr.salaries.salary-slip', [
            'employee'              => $employeeModel,
            'branch'                => $branch,
            'monthName'             => $monthName,
            'data'                  => $data,
            'employeeAllowances'    => $employeeAllowances,
            'employeeDeductions'    => $employeeDeductions,
            'totalAllowanceAmount'  => $totalAllowanceAmount,
            'totalDeductionAmount'  => $totalDeductionAmount,
        ]);
    }
}
