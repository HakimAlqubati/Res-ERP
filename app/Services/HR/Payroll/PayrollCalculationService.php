<?php

namespace App\Services\HR\Payroll;

use App\Models\Employee;
use App\Models\Payroll;
use App\Repositories\HR\Salary\PayrollRepository;
use App\Repositories\HR\Salary\SalaryTransactionRepository;
use App\Services\HR\AttendanceHelpers\Reports\AttendanceFetcher;
use App\Services\HR\SalaryHelpers\SalaryCalculatorService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollCalculationService
{
    public function __construct(
        protected PayrollRepository $payrollRepo,
        protected SalaryTransactionRepository $transactionRepo,
        protected AttendanceFetcher $attendanceFetcher,
        protected SalaryCalculatorService $salaryCalculatorService
    ) {}

    /**
     * حساب راتب موظف واحد
     */
    public function calculateForEmployee(Employee $employee, int $year, int $month)
    {
        try {
            return DB::transaction(function () use ($employee, $year, $month) {
                $periodStart = Carbon::create($year, $month, 1)->startOfMonth();
                $periodEnd   = Carbon::create($year, $month, 1)->endOfMonth();

                // الراتب الأساسي للموظف
                $monthlySalary = $employee->salary;

                if (is_null($monthlySalary) || $monthlySalary == 0) {
                    return [
                        'success' => false,
                        'message' => "Cannot calculate payroll: monthly salary is not set or is zero for employee [{$employee->name}] (Employee No: {$employee->employee_no}) in branch [{$employee->branch?->name}] for {$month}/{$year}."
                    ];
                }

                $workDays      = 26; // عدد أيام العمل في الشهر (تستخرج من إعدادات النظام أو الجدول الفعلي)
                $dailyHours    = 6;  // عدد ساعات العمل في اليوم (قابلة للتعديل حسب المؤسسة)

                // ✅ جلب بيانات الحضور للفترة
                $attendanceData = $this->attendanceFetcher->fetchEmployeeAttendances($employee, $periodStart, $periodEnd);

                // ✅ استخدام كلاس الحسبة SalaryCalculator
                $result = $this->salaryCalculatorService->calculate(
                    employeeData: $attendanceData,
                    salary: $monthlySalary,
                    workDays: $workDays,
                    dailyHours: $dailyHours
                );

                $netSalary = $result['net_salary'];

                $debtAmount = 0;
                if ($netSalary < 0) {
                    $debtAmount = abs($netSalary);
                    $netSalary = 0;
                }

                // ✅ تحقق من وجود سجل راتب سابق
                $exists = Payroll::withTrashed()
                    ->where('employee_id', $employee->id)
                    ->where('branch_id', $employee->branch_id)
                    ->where('year', $year)
                    ->where('month', $month)
                    ->exists();

                if ($exists) {
                    return [
                        'success' => false,
                        'message' => "Payroll has already been calculated for employee [{$employee->name}] (Employee No: {$employee->employee_no}) in branch [{$employee->branch?->name}] for {$month}/{$year}.",
                    ];
                }
                // ✅ إنشاء سجل Payroll
                $payroll = $this->payrollRepo->create([
                    'employee_id'             => $employee->id,
                    'branch_id'               => $employee->branch_id,
                    'year'                    => $year,
                    'month'                   => $month,
                    'period_start_date'       => $periodStart,
                    'period_end_date'         => $periodEnd,
                    'base_salary'             => $result['base_salary'],
                    'total_allowances'        => 0, // يمكن حسابها لاحقًا إن وجدت
                    'overtime_amount'         => $result['overtime_amount'],
                    'total_deductions'        => $result['absence_deduction'] + $result['partial_deduction'],
                    'gross_salary'            => $result['gross_salary'] ?? ($result['base_salary'] + $result['overtime_amount']),
                    'net_salary'              => $netSalary,
                    'currency'                => getDefaultCurrency(),
                    'status'                  => Payroll::STATUS_PENDING,
                    'created_by'              => auth()->id() ?? null,
                    'notes'                   => "Automatic payroll generation",
                ]);

                if ($debtAmount > 0) {
                    $this->transactionRepo->addDeduction(
                        employeeId: $employee->id,
                        amount: $debtAmount,
                        date: $periodEnd->toDateString(),
                        description: 'Deferred deduction due to deductions exceeding net salary',
                        payrollId: $payroll->id,
                        extra: [
                            'status' => 'deferred',
                            'notes'  => 'سيتم خصم هذا المبلغ من الراتب في الشهر التالي تلقائيًا'
                        ]
                    );
                }


                $this->generateSalaryTransactions($employee, $result, $periodEnd, $payroll);

                return [
                    'success' => true,
                    'message' => "Payroll calculated successfully.",
                    'data'    => $payroll,
                ];
            });
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => "An unexpected error occurred during payroll calculation: {$e->getMessage()}",
            ];
        }
    }


    protected function generateSalaryTransactions(Employee $employee, array $result, Carbon $periodEnd, Payroll $payroll): void
    {
        if ($result['absence_deduction'] > 0) {
            $this->transactionRepo->addDeduction(
                employeeId: $employee->id,
                amount: $result['absence_deduction'],
                date: $periodEnd->toDateString(),
                description: 'Deduction for absence days',
                payrollId: $payroll->id
            );
        }

        if ($result['partial_deduction'] > 0) {
            $this->transactionRepo->addDeduction(
                employeeId: $employee->id,
                amount: $result['partial_deduction'],
                date: $periodEnd->toDateString(),
                description: 'Partial attendance deduction',
                payrollId: $payroll->id
            );
        }

        if ($result['overtime_amount'] > 0) {
            $this->transactionRepo->addTransaction(
                employeeId: $employee->id,
                amount: $result['overtime_amount'],
                date: $periodEnd->toDateString(),
                type: 'overtime',
                operation: '+',
                description: 'Approved overtime allowance',
                payrollId: $payroll->id
            );
        }
    }

    public function calculateForEmployees(array $employeeIds, int $year, int $month): array
    {
        $results = [];

        $employees = Employee::whereIn('id', $employeeIds)->get();

        foreach ($employees as $employee) {
            $result = $this->calculateForEmployee($employee, $year, $month);
            $results[] = [
                'success'     => $result['success'],
                'message'     => $result['message'],
                'employee_id' => $employee->id,
                'employee_no' => $employee->employee_no,
                'name'        => $employee->name,
            ];
        }

        return $results;
    }
}
