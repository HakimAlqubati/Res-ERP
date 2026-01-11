<?php

namespace App\Modules\HR\Payroll\Contracts;

use App\Modules\HR\Payroll\DTOs\RunPayrollData;

/**
 * Interface for payroll run services.
 * 
 * Provides a contract for simulating and executing payroll runs.
 */
interface PayrollRunnerInterface
{
    /**
     * Simulate payroll without persisting to database.
     *
     * @param RunPayrollData $input The payroll run parameters
     * @return array Simulation results with employee breakdowns and totals
     */
    public function simulate(RunPayrollData $input): array;

    /**
     * Run and persist payroll to database.
     *
     * @param RunPayrollData $input The payroll run parameters
     * @return array Result with created/updated payrolls and aggregates
     */
    public function runAndPersist(RunPayrollData $input): array;
}
