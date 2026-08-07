<?php

declare(strict_types=1);

namespace App\Modules\HR\Payroll\DTOs;

use InvalidArgumentException;

/**
 * فلاتر تقرير الاستقطاعات المسبق (قبل إنشاء البايرول).
 *
 * يعمل بـ year + month لأن PayrollSimulationService يحسب شهراً بشهر.
 */
final class PrePayrollDeductionFilterDTO
{
    public const GROUP_BY_EMPLOYEE = 'employee';
    public const GROUP_BY_BRANCH   = 'branch';

    public function __construct(
        public readonly int  $year,
        public readonly int  $month,
        public readonly ?int $employeeId = null,
        public readonly ?array $branchIds  = null,
        public readonly string $groupBy  = self::GROUP_BY_EMPLOYEE,
    ) {
        if ($this->month < 1 || $this->month > 12) {
            throw new InvalidArgumentException('Month must be between 1 and 12.');
        }

        if ($this->year < 2000 || $this->year > (int) date('Y') + 1) {
            throw new InvalidArgumentException('Invalid year provided.');
        }
    }

    public static function fromArray(array $data): self
    {
        return new self(
            year:       (int) ($data['year']        ?? now()->year),
            month:      (int) ($data['month']       ?? now()->month),
            employeeId: isset($data['employee_id']) ? (int) $data['employee_id'] : null,
            branchIds:  isset($data['branch_ids'])  ? (array) $data['branch_ids']  : [],
            groupBy:    $data['group_by']            ?? self::GROUP_BY_EMPLOYEE,
        );
    }
}
