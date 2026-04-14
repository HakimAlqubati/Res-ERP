<?php

namespace App\Modules\HR\PayrollReports\DTOs;

use Illuminate\Http\Request;

class PayrollReportFilterDTO
{
    public function __construct(
        public readonly ?int $branchId = null,
        public readonly ?int $year = null,
        public readonly ?int $month = null,
        public readonly ?int $employeeId = null,
        public readonly ?int $payrollRunId = null,
        public readonly ?string $status = null,
        public readonly ?string $dateFrom = null,
        public readonly ?string $dateTo = null,
    ) {
    }

    /**
     * Create DTO from a standard array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            branchId: $data['branch_id'] ?? null,
            year: $data['year'] ?? null,
            month: $data['month'] ?? null,
            employeeId: $data['employee_id'] ?? null,
            payrollRunId: $data['payroll_run_id'] ?? null,
            status: $data['status'] ?? null,
            dateFrom: $data['date_from'] ?? null,
            dateTo: $data['date_to'] ?? null,
        );
    }

    /**
     * Create DTO from HTTP request.
     */
    public static function fromRequest(Request $request): self
    {
        return self::fromArray($request->all());
    }
}
