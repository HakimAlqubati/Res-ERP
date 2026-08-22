<?php

namespace App\Modules\HR\PayrollReports\Contracts;

use App\Modules\HR\PayrollReports\DTOs\EmployeeFinancialSummaryDTO;
use Illuminate\Support\Collection;

interface EmployeeFinancialReportServiceInterface
{
    /**
     * Generate financial summary for a specific employee.
     * 
     * @param int $employeeId
     * @return EmployeeFinancialSummaryDTO
     */
    public function generateForEmployee(int $employeeId): EmployeeFinancialSummaryDTO;

    /**
     * Generate financial summary for multiple employees.
     * 
     * @param array $employeeIds
     * @return Collection<int, EmployeeFinancialSummaryDTO>
     */
    public function generateForEmployees(array $employeeIds): Collection;
}
