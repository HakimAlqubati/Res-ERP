<?php

namespace App\Modules\HR\Payroll\DTOs;

final class RunPayrollData
{
    public function __construct(
        public readonly int $branchId,
        public readonly int $year,
        public readonly int $month,
        public readonly bool $overwriteExisting = false, // optional flag
        public readonly ?string $payDate = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            branchId: (int) $data['branch_id'],
            year: (int) $data['year'],
            month: (int) $data['month'],
            overwriteExisting: (bool) ($data['overwrite_existing'] ?? false),
            payDate: isset($data['pay_date'])
                ? \Carbon\Carbon::parse($data['pay_date'])->toDateString()
                : null,
        );
    }
}
