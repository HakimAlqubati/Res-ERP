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
        public readonly ?int $paymentMethodId = null,
    ) {
    }

    /**
     * Create DTO from a standard array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            branchId: isset($data['branch_id']) && $data['branch_id'] !== '' ? (int) $data['branch_id'] : null,
            year: isset($data['year']) && $data['year'] !== '' ? (int) $data['year'] : null,
            month: isset($data['month']) && $data['month'] !== '' ? (int) $data['month'] : null,
            employeeId: isset($data['employee_id']) && $data['employee_id'] !== '' ? (int) $data['employee_id'] : null,
            payrollRunId: isset($data['payroll_run_id']) && $data['payroll_run_id'] !== '' ? (int) $data['payroll_run_id'] : null,
            status: $data['status'] ?? null,
            dateFrom: $data['date_from'] ?? null,
            dateTo: $data['date_to'] ?? null,
            paymentMethodId: isset($data['payment_method_id']) && $data['payment_method_id'] !== '' ? (int) $data['payment_method_id'] : null,
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
