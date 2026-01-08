<?php

namespace App\Modules\HR\Payroll\Contracts;

use App\Enums\HR\Payroll\SalaryTransactionType;
use App\Models\SalaryTransaction;

/**
 * Interface for salary transaction repository.
 * 
 * Provides a contract for managing salary transactions (deductions, allowances, etc.).
 */
interface SalaryTransactionRepositoryInterface
{
    /**
     * Create a new salary transaction.
     *
     * @param array $data Transaction data
     * @return SalaryTransaction
     */
    public function create(array $data): SalaryTransaction;

    /**
     * Find a transaction by ID.
     *
     * @param int $id
     * @return SalaryTransaction|null
     */
    public function find(int $id): ?SalaryTransaction;

    /**
     * Get all transactions for an employee.
     *
     * @param int $employeeId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByEmployee(int $employeeId);

    /**
     * Add a deduction transaction.
     *
     * @param int $payrollRunId
     * @param int $employeeId
     * @param float $amount
     * @param string $date
     * @param string $description
     * @param mixed $type
     * @param mixed $subType
     * @param object|null $reference
     * @param int|null $payrollId
     * @param array $extra
     * @return SalaryTransaction
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
    ): SalaryTransaction;

    /**
     * Add an allowance transaction.
     *
     * @param int $payrollRunId
     * @param int $employeeId
     * @param float $amount
     * @param string $date
     * @param string $description
     * @param object|null $reference
     * @param int|null $payrollId
     * @param array $extra
     * @return SalaryTransaction
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
    ): SalaryTransaction;

    /**
     * Add a general transaction.
     *
     * @param int $payrollRunId
     * @param int $employeeId
     * @param float $amount
     * @param string $date
     * @param SalaryTransactionType $type
     * @param string|null $operation
     * @param string $description
     * @param object|null $reference
     * @param int|null $payrollId
     * @param string|null $status
     * @param array $extra
     * @return SalaryTransaction
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
    ): SalaryTransaction;
}
