<?php

namespace App\Services\HR\Payroll;

use App\Models\Employee;

class PayrollBatchHandler
{
    public function __construct(
        protected PayrollCalculationService $calculationService
    ) {}

    /**
     * احسب الرواتب لمجموعة موظفين عبر معرفاتهم
     */
    public function handleByEmployeeIds(array $employeeIds, int $year, int $month): array
    {
        return $this->calculationService->calculateForEmployees($employeeIds, $year, $month);
    }

    /**
     * احسب الرواتب لكل موظفي فرع معين
     */
    public function handleByBranch(int $branchId, int $year, int $month): array
    {
        $employeeIds = Employee::where('branch_id', $branchId)
            ->active()
            ->pluck('id')
            ->toArray();

        return $this->handleByEmployeeIds($employeeIds, $year, $month);
    }
}
