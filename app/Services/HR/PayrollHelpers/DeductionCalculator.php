<?php

namespace App\Services\HR\PayrollHelpers;

use App\Models\Employee;

class DeductionCalculator
{
    public function calculateDeductions(array $deductions, float $basicSalary): array
    {
           $finalDeductions = [];
        $totalDeductions = 0.0; // Initialize total deductions

        foreach ($deductions as $deduction) {
            if ($deduction['is_percentage']) {
                // Calculate the deduction based on the percentage
                $deductionAmount = ($basicSalary * $deduction['percentage']) / 100;
            } else {
                // Use the fixed amount directly
                $deductionAmount = (float) $deduction['amount'];
            }

            if (isset($deduction['has_brackets']) && $deduction['has_brackets'] && isset($deduction['brackets'])) {
                $deductionAmount = $deduction->calculateTax($basicSalary)['monthly_tax'] ?? 0;
            }
            // Add to total deductions
            $totalDeductions += $deductionAmount;

            // Store the result
            $finalDeductions[] = [
                'id' => $deduction['id'],
                'name' => $deduction['name'],
                'deduction_amount' => $deductionAmount,
                'is_percentage' => $deduction['is_percentage'],
                'amount_value' => $deduction['amount'],
                'percentage_value' => $deduction['percentage'],
                'applied_by' => $deduction['applied_by'],
            ];
        }

        // Add the total deductions to the result
        $finalDeductions['result'] = $totalDeductions;

        return $finalDeductions;
    }

    public function calculateDeductionsEmployeer($deductions, float $basicSalary): array
    {
        
        $finalDeductions = [];
        $totalDeductions = 0.0; // Initialize total deductions

        foreach ($deductions as $deduction) {
            if ($deduction['is_percentage']) {
                // Calculate the deduction based on the percentage
                $deductionAmount = ($basicSalary * $deduction['employer_percentage']) / 100;
            } else {
                // Use the fixed amount directly
                $deductionAmount = (float) $deduction['employer_amount'];
            }

            if (isset($deduction['has_brackets']) && $deduction['has_brackets'] && isset($deduction['brackets'])) {
                $deductionAmount = $deduction->calculateTax($basicSalary)['monthly_tax'] ?? 0;
            }
            // Add to total deductions
            $totalDeductions += $deductionAmount;

            // Store the result
            $finalDeductions[] = [
                'id' => $deduction['id'],
                'name' => $deduction['name'] . ' (Employer) ',
                'deduction_amount' => $deductionAmount,
                'is_percentage' => $deduction['is_percentage'],
                'amount_value' => $deduction['employer_amount'],
                'percentage_value' => $deduction['employer_percentage'],
            ];
        }

        // Add the total deductions to the result
        $finalDeductions['result'] = $totalDeductions;

        return $finalDeductions;
    }

    public function calculateYearlyTax(Employee $employee)
    {
        // Calculate yearly tax
        return 0;
    }
}