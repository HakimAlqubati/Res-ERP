<?php

namespace App\Modules\HR\PayrollReports\DTOs;

readonly class EmployeeFinancialSummaryDTO
{
    public function __construct(
        public int $employeeId,
        public string $employeeName,
        public string $branchName,
        
        public string $incentiveTypes,
        public string $allowanceTypes,
        public string $deductionTypes
    ) {}
}
