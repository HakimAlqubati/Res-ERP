<?php

declare(strict_types=1);

namespace App\Modules\HR\Payroll\DTOs;

use Carbon\Carbon;
use InvalidArgumentException;

final class DeductionReportFilterDTO
{
    public function __construct(
        public readonly Carbon $fromDate,
        public readonly Carbon $toDate,
        public readonly ?int $employeeId = null,
        public readonly ?int $branchId = null,
        public readonly bool $includeEmployerContribution = true,
        public readonly ?array $deductionTypes = null
    ) {
        if ($this->fromDate->isAfter($this->toDate)) {
            throw new InvalidArgumentException('Start date cannot be after end date.');
        }
    }

    /**
     * Create DTO from an array of validated data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            fromDate: Carbon::parse($data['from_date']),
            toDate: Carbon::parse($data['to_date']),
            employeeId: isset($data['employee_id']) ? (int) $data['employee_id'] : null,
            branchId: isset($data['branch_id']) ? (int) $data['branch_id'] : null,
            includeEmployerContribution: isset($data['include_employer_contribution']) ? (bool) $data['include_employer_contribution'] : true,
            deductionTypes: $data['deduction_types'] ?? null
        );
    }
}
