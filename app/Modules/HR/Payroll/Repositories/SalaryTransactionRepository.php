<?php

namespace App\Modules\HR\Payroll\Repositories;

use App\Enums\HR\Payroll\SalaryTransactionType;
use App\Models\SalaryTransaction;
use App\Modules\HR\Payroll\Contracts\SalaryTransactionRepositoryInterface;

class SalaryTransactionRepository implements SalaryTransactionRepositoryInterface
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

        // إذا لم يُمرّر amount لكن لدينا qty & rate → احسبه
        $hasQtyRate = isset($data['qty'], $data['rate']) && $data['qty'] !== null && $data['rate'] !== null;
        if (!isset($data['amount']) && $hasQtyRate) {
            $multiplier = isset($data['multiplier']) && $data['multiplier'] !== null ? (float)$data['multiplier'] : 1.0;
            $data['amount'] = (float)$data['qty'] * (float)$data['rate'] * $multiplier;
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
        int $payrollRunId,
        int $employeeId,
        float $amount,
        string $date,
        string $description,
        $type,
        $subType,
        $reference = null,
        $payrollId = null,
        array $extra = [],
    ): SalaryTransaction {
        return $this->create(array_merge([
            'payroll_run_id' => $payrollRunId,
            'employee_id'    => $employeeId,
            'payroll_id'     => $payrollId,
            'date'           => $date,
            'amount'         => abs($amount),
            'type'           => $type,
            'sub_type'      => $subType,
            'operation'      =>  SalaryTransaction::OPERATION_SUB,
            'description'    => $description,
            'reference_id'   => is_object($reference) ? $reference->id : null,
            'reference_type' => $reference ? get_class($reference) : null,
            'status'         => SalaryTransaction::STATUS_APPROVED,
            'created_by'     => auth()->id() ?? null,
        ], $extra));
    }

    /**
     * إضافة بدل للموظف
     */
    public function addAllowance(
        int $payrollRunId,
        int $employeeId,
        float $amount,
        string $date,
        string $description,
        $reference = null,
        $payrollId = null,
        array $extra = []
    ): SalaryTransaction {
        return $this->create(array_merge(
            [
                'payroll_run_id' => $payrollRunId,
                'employee_id'    => $employeeId,
                'payroll_id'     => $payrollId,
                'date'           => $date,
                'amount'         => abs($amount), // دائماً موجب
                'type'           => SalaryTransactionType::TYPE_ALLOWANCE,
                'operation'      => SalaryTransaction::OPERATION_ADD,
                'description'    => $description,
                'reference_id'   => is_object($reference) ? $reference->id : null,
                'reference_type' => $reference ? get_class($reference) : null,
                'status'         => SalaryTransaction::STATUS_APPROVED,
                'created_by'     => auth()->id() ?? null,
            ],
            $extra
        ));
    }

    /**
     * إضافة حركة مالية عامة لأي نوع (مثلاً مكافأة أو سلفة ...)
     */
    public function addTransaction(
        int $payrollRunId,
        int $employeeId,
        float $amount,
        string $date,
        SalaryTransactionType $type,
        ?string $operation,
        string $description,
        $reference = null,
        $payrollId = null,
        string $status = null,
        array $extra = [],
    ): SalaryTransaction {
        return $this->create(array_merge([
            'payroll_run_id' => $payrollRunId,
            'employee_id'    => $employeeId,
            'payroll_id'     => $payrollId,
            'date'           => $date,
            'amount'         => abs($amount), // دائماً موجب
            'type'           => $type->value,
            'operation'      => $operation,
            'description'    => $description,
            'reference_id'   => is_object($reference) ? $reference->id : null,
            'reference_type' => $reference ? get_class($reference) : null,
            'status'         => $status ?? SalaryTransaction::STATUS_APPROVED,
            'created_by'     => auth()->id() ?? null,
        ], $extra));
    }
}
