<?php

namespace App\Services;

use App\Models\Deduction;
use App\Models\MonthlySalaryDeductionsDetail;
use App\Models\MonthSalary;

class DeductionService
{
    /**
     * Get deductions for a specific employee and month-year.
     *
     * @param int $employeeId The ID of the employee.
     * @param string $monthYear The month and year in "MM-YYYY" format.
     * @return array An array of deductions with their amounts.
     */
    public function getDeductionsForEmployee($employeeId, $startMonth, $endMonth)
    {
        // Retrieve all MonthSalary records for the given range
        $monthSalaries = MonthSalary::whereBetween('month', [$startMonth, $endMonth])
            ->orderBy('month', 'asc')
            ->get();

        if ($monthSalaries->isEmpty()) {
            return [
                'summed_deductions' => [],
                'last_month_deductions' => [],
                'total_deductions' => [],
            ]; // No salary records found
        }

        // Retrieve deductions where is_specific == 0 and active == 1
        $deductions = Deduction::where('is_specific', 0)
            ->where('active', 1)
            ->get();

        // Initialize variables for results
        $summedDeductions = [];
        $lastMonthDeductions = [];
        $totalDeductions = [];
        $lastMonthSalary = $monthSalaries->last(); // Get the last month record

        // Iterate over all MonthSalary records
        foreach ($monthSalaries as $monthSalary) {
            // Retrieve the deduction details for the employee and the salary month
            $deductionDetails = MonthlySalaryDeductionsDetail::where('month_salary_id', $monthSalary->id)
                ->where('employee_id', $employeeId)
                ->whereIn('deduction_id', $deductions->pluck('id')) // Match with relevant deductions
                ->get();

            foreach ($deductionDetails as $detail) {
                // If it's the last month, store deductions separately
                if ($monthSalary->id === $lastMonthSalary->id) {
                    $lastMonthDeductions[] = [
                        'deduction_name' => $detail->deduction_name,
                        'deduction_amount' => $detail->deduction_amount,
                    ];
                } else {
                    // Sum the deductions for all previous months
                    if (!isset($summedDeductions[$detail->deduction_name])) {
                        $summedDeductions[$detail->deduction_name] = 0;
                    }
                    $summedDeductions[$detail->deduction_name] += $detail->deduction_amount;
                }

                // Calculate total deductions (sum all months, including the last month)
                if (!isset($totalDeductions[$detail->deduction_name])) {
                    $totalDeductions[$detail->deduction_name] = 0;
                }
                $totalDeductions[$detail->deduction_name] += $detail->deduction_amount;
            }
        }

        // Format summed deductions as an array of objects
        $summedDeductions = collect($summedDeductions)->map(function ($amount, $name) {
            return [
                'deduction_name' => $name,
                'deduction_amount' => $amount,
            ];
        })->values();

        // Format total deductions as an array of objects
        $totalDeductions = collect($totalDeductions)->map(function ($amount, $name) {
            return [
                'deduction_name' => $name,
                'deduction_amount' => $amount,
            ];
        })->values();

        return [
            'summed_deductions' => $summedDeductions,
            'last_month_deductions' => $lastMonthDeductions,
            'total_deductions' => $totalDeductions,
        ];
    }
}
