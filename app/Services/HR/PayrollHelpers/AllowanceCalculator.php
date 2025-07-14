<?php

namespace App\Services\HR\PayrollHelpers;

class AllowanceCalculator
{
    public function calculateAllowances(array $allowances, float $basicSalary): array
    {
         $finalAllowances = [];
        $totalAllowances = 0.0; // Initialize total allowances

        foreach ($allowances as $allowance) {
            if ($allowance['is_percentage']) {
                // Calculate the allowance based on the percentage
                $allowanceAmount = ($basicSalary * $allowance['percentage']) / 100;
            } else {
                // Use the fixed amount directly
                $allowanceAmount = (float) $allowance['amount'];
            }

            // Add to total allowances
            $totalAllowances += $allowanceAmount;

            // Store the result
            $finalAllowances[] = [
                'id' => $allowance['id'],
                'name' => $allowance['name'],
                'allowance_amount' => $allowanceAmount,
                'is_percentage' => $allowance['is_percentage'],
                'amount_value' => $allowance['amount'],
                'percentage_value' => $allowance['percentage'],
            ];
        }

        // Add the total allowances to the result
        $finalAllowances['result'] = $totalAllowances;

        return $finalAllowances;
    }
}