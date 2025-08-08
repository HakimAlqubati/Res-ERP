<?php

namespace App\Services\HR\Payroll;

use App\Models\Employee;
use App\Models\Payroll;
use App\Repositories\HR\Salary\PayrollRepository;
use App\Repositories\HR\Salary\SalaryTransactionRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollCalculationService
{
    public function __construct(
        protected PayrollRepository $payrollRepo,
        protected SalaryTransactionRepository $transactionRepo,
        protected PayrollSimulationService $payrollSimulator // ✅ استخدام المحاكي
    ) {}

    /**
     * حساب راتب موظف واحد وحفظه
     */
    public function calculateForEmployee(Employee $employee, int $year, int $month): array
    {
        try {
            return DB::transaction(function () use ($employee, $year, $month) {
                $periodStart = Carbon::create($year, $month, 1)->startOfMonth();
                $periodEnd   = Carbon::create($year, $month, 1)->endOfMonth();

                // تحقق من وجود سجل راتب سابق
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

                // ✅ استدعاء المحاكي
                $simulationResults = $this->payrollSimulator->simulateForEmployees([$employee->id], $year, $month);
                $simulation = $simulationResults[0] ?? null;

                if (!$simulation || !$simulation['success']) {
                    return [
                        'success' => false,
                        'message' => $simulation['message'] ?? 'Payroll simulation failed.',
                    ];
                }
                $result     = $simulation['data'];
                // dd($result);
                $netSalary  = $result['net_salary'];
                $debtAmount = $result['debt_amount'] ?? 0;

                // ✅ حفظ سجل الراتب
                $payroll = $this->payrollRepo->create([
                    'employee_id'             => $employee->id,
                    'branch_id'               => $employee->branch_id,
                    'year'                    => $year,
                    'month'                   => $month,
                    'period_start_date'       => $result['period_start'],
                    'period_end_date'         => $result['period_end'],
                    'base_salary'             => $result['base_salary'],
                    'total_allowances'        => 0,
                    'overtime_amount'         => $result['overtime_amount'],
                    'total_deductions'        => $result['absence_deduction'],
                    'gross_salary'            => $result['gross_salary'],
                    'net_salary'              => $netSalary,
                    'debt_amount'             => $debtAmount,
                    'currency'                => getDefaultCurrency(),
                    'status'                  => Payroll::STATUS_PENDING,
                    'created_by'              => auth()->id() ?? null,
                    'notes'                   => "Automatic payroll generation via simulator",
                ]);

                // ✅ إنشاء المعاملات المالية
                $this->generateSalaryTransactions($employee, $result, $periodEnd, $payroll);

                // ✅ تسجيل المديونية في حالة الخصم أكبر من الراتب
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

                return [
                    'success' => true,
                    'message' => "Payroll calculated and saved successfully.",
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

    /**
     * إنشاء المعاملات المالية الخاصة بالراتب (بدلات، خصومات)
     */
    protected function generateSalaryTransactions(Employee $employee, array $result, Carbon $periodEnd, Payroll $payroll): void
    {
        $transactions = $result['transactions'] ?? [];

        foreach ($transactions as $txn) {
            $amount = $txn['amount'] ?? 0;
            if ($amount <= 0) {
                continue;
            }

            // نوع المعاملة: خصم أم إضافة
            if ($txn['operation'] === '-') {
                $this->transactionRepo->addDeduction(
                    employeeId: $employee->id,
                    amount: $amount,
                    date: $periodEnd->toDateString(),
                    description: $txn['description'] ?? 'Deduction',
                    type: $txn['type'],
                    subType: $txn['sub_type'] ?? null,
                    payrollId: $payroll->id,
                    extra: $txn['extra'] ?? []
                );
            } else {
                $this->transactionRepo->addTransaction(
                    employeeId: $employee->id,
                    amount: $amount,
                    date: $periodEnd->toDateString(),
                    type: $txn['type'] ?? 'other',
                    operation: $txn['operation'],
                    description: $txn['description'] ?? 'Addition',
                    payrollId: $payroll->id
                );
            }
        }
    }


    /**
     * حساب وحفظ رواتب مجموعة موظفين
     */
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
