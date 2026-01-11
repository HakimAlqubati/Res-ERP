<?php

namespace App\Modules\HR\Payroll\Contracts;

use App\Models\Payroll;

/**
 * Interface for payroll repository.
 * 
 * Provides a contract for CRUD operations on Payroll model.
 */
interface PayrollRepositoryInterface
{
    /**
     * Create a new payroll record.
     *
     * @param array $data Payroll data
     * @return Payroll
     */
    public function create(array $data): Payroll;

    /**
     * Find a payroll by ID.
     *
     * @param int $id
     * @return Payroll|null
     */
    public function find($id): ?Payroll;

    /**
     * Get payrolls by employee.
     *
     * @param int $employeeId
     * @param int|null $year
     * @param int|null $month
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function byEmployee($employeeId, $year = null, $month = null);

    /**
     * Approve a payroll.
     *
     * @param int $id
     * @param int $userId
     * @return Payroll|null
     */
    public function approve($id, $userId);

    /**
     * Mark a payroll as paid.
     *
     * @param int $id
     * @param int $userId
     * @return Payroll|null
     */
    public function pay($id, $userId);
}
