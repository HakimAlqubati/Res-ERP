<?php

namespace App\Modules\HR\Payroll\Contracts;

use App\Models\PayrollRun;

/**
 * Interface for payroll simulation services.
 * 
 * Provides a contract for simulating payroll calculations without database writes.
 */
interface PayrollSimulatorInterface
{
    /**
     * Simulate payroll for a set of employees.
     *
     * @param array $employeeIds List of employee IDs to simulate
     * @param int $year Year of the period
     * @param int $month Month of the period
     * @return array Simulation results for each employee
     */
    public function simulateForEmployees(array $employeeIds, int $year, int $month): array;

    /**
     * Simulate payroll for employees within a specific PayrollRun context.
     *
     * @param PayrollRun $run The payroll run context
     * @param array $employeeIds List of employee IDs to simulate
     * @return array Simulation results for each employee
     */
    public function simulateForRunEmployees(PayrollRun $run, array $employeeIds): array;
}
