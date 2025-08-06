<?php

namespace App\Repositories\HR\Salary;

use App\Models\SalaryTransaction;

class SalaryTransactionRepository
{
    /**
     * إنشاء حركة مالية جديدة (دالة عامة)
     */
    public function create(array $data): SalaryTransaction
    {
        if (empty($data['currency'])) {
            $data['currency'] = SalaryTransaction::defaultCurrency();
        }
        // ✅ إضافة السنة والشهر من التاريخ إن لم يتم تمريرهم
        if (!isset($data['year'])) {
            $data['year'] = \Carbon\Carbon::parse($data['date'])->year;
        }

        if (!isset($data['month'])) {
            $data['month'] = \Carbon\Carbon::parse($data['date'])->month;
        }

        // تأكد أن المبلغ دائماً موجب!
        $data['amount'] = abs($data['amount']);

        return SalaryTransaction::create($data);
    }

    /**
     * جلب حركة برقمها
     */
    public function find(int $id): ?SalaryTransaction
    {
        return SalaryTransaction::find($id);
    }

    /**
     * جلب جميع الحركات لموظف
     */
    public function getByEmployee(int $employeeId)
    {
        return SalaryTransaction::where('employee_id', $employeeId)->get();
    }

    /**
     * إضافة خصم للموظف
     */
    public function addDeduction(
        int $employeeId,
        float $amount,
        string $date,
        string $description,
        $reference = null,
        $payrollId = null,
        array $extra = [],
    ): SalaryTransaction {
        return $this->create(array_merge([
            'employee_id'    => $employeeId,
            'payroll_id'     => $payrollId,
            'date'           => $date,
            'amount'         => abs($amount),
            'type'           => SalaryTransaction::TYPE_DEDUCTION,
            'operation'      => SalaryTransaction::OPERATION_SUB,
            'description'    => $description,
            'reference_id'   => $reference?->id ?? null,
            'reference_type' => $reference ? get_class($reference) : null,
            'status'         => SalaryTransaction::STATUS_APPROVED,
            'created_by'     => auth()->id() ?? null,
        ], $extra));
    }

    /**
     * إضافة بدل للموظف
     */
    public function addAllowance(
        int $employeeId,
        float $amount,
        string $date,
        string $description,
        $reference = null,
        $payrollId = null
    ): SalaryTransaction {
        return $this->create([
            'employee_id'    => $employeeId,
            'payroll_id'     => $payrollId,
            'date'           => $date,
            'amount'         => abs($amount), // دائماً موجب
            'type'           => SalaryTransaction::TYPE_ALLOWANCE,
            'operation'      => SalaryTransaction::OPERATION_ADD,
            'description'    => $description,
            'reference_id'   => $reference?->id ?? null,
            'reference_type' => $reference ? get_class($reference) : null,
            'status'         => SalaryTransaction::STATUS_APPROVED,
            'created_by'     => auth()->id() ?? null,
        ]);
    }

    /**
     * إضافة حركة مالية عامة لأي نوع (مثلاً مكافأة أو سلفة ...)
     */
    public function addTransaction(
        int $employeeId,
        float $amount,
        string $date,
        string $type,
        string $operation,
        string $description,
        $reference = null,
        $payrollId = null,
        string $status = null
    ): SalaryTransaction {
        return $this->create([
            'employee_id'    => $employeeId,
            'payroll_id'     => $payrollId,
            'date'           => $date,
            'amount'         => abs($amount), // دائماً موجب
            'type'           => $type,
            'operation'      => $operation,
            'description'    => $description,
            'reference_id'   => $reference?->id ?? null,
            'reference_type' => $reference ? get_class($reference) : null,
            'status'         => $status ?? SalaryTransaction::STATUS_APPROVED,
            'created_by'     => auth()->id() ?? null,
        ]);
    }
}
