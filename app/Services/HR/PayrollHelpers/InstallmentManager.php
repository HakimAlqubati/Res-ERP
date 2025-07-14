<?php

namespace App\Services\HR\PayrollHelpers;

class InstallmentManager
{
    public function getInstallmentAdvancedMonthly($employee, $year, $month)
    {
         // Check if the employee has an advance transaction for the specified month and year
        $advancedInstalmment = $employee?->advancedInstallments()
            ->whereYear('due_date', $year)
            ->whereMonth('due_date', $month)
            ->where('is_paid', false)
            ->first();
        // dd($employee,$year,$month,$advancedInstalmment);

        return $advancedInstalmment;
    }
}