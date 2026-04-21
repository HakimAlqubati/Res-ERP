<?php

namespace App\Modules\HR\PayrollReports\DTOs;

use Illuminate\Support\Collection;

class PayrollReportResultDTO implements \JsonSerializable
{
    /**
     * @param Collection<int, PayrollReportItemDTO> $items
     */
    public function __construct(
        public readonly Collection $items,
        public readonly float $grandTotalBaseSalary,
        public readonly float $grandTotalAllowances,
        public readonly float $grandTotalBonus,
        public readonly float $grandTotalOvertime,
        public readonly float $grandTotalDeductions,
        public readonly float $grandTotalAdvances,
        public readonly float $grandTotalPenalties,
        public readonly float $grandTotalGrossSalary,
        public readonly float $grandTotalNetSalary,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'summary' => [
                'total_base_salary' => round($this->grandTotalBaseSalary, 2),
                'total_allowances'  => round($this->grandTotalAllowances, 2),
                'total_bonus'       => round($this->grandTotalBonus, 2),
                'total_overtime'    => round($this->grandTotalOvertime, 2),
                'total_deductions'  => round($this->grandTotalDeductions, 2),
                'total_advances'    => round($this->grandTotalAdvances, 2),
                'total_penalties'   => round($this->grandTotalPenalties, 2),
                'total_gross'       => round($this->grandTotalGrossSalary, 2),
                'total_net'         => round($this->grandTotalNetSalary, 2),
            ],
            'data' => $this->items,
        ];
    }
}
