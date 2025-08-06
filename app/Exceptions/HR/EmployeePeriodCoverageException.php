<?php

namespace App\Exceptions\HR;

use Exception;

class EmployeePeriodCoverageException extends Exception
{
    public function __construct(
        public readonly int $requiredDays,
        public readonly int $actualDays,
        public readonly int $missingDays,
        public readonly string $employeeName,
        public readonly string $employeeNo,
        string $message = ''
    ) {
        $finalMessage = $message ?: "Employee [$employeeName] (Employee No: $employeeNo) is assigned only [$actualDays] work days, but requires [$requiredDays]. Missing [$missingDays] day(s).";
        parent::__construct($finalMessage);
    }
}
