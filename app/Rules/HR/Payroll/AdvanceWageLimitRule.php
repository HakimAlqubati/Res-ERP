<?php

namespace App\Rules\HR\Payroll;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Modules\HR\Payroll\Contracts\PayrollSimulatorInterface;
use Illuminate\Support\Facades\Log;

/**
 * Class AdvanceWageLimitRule
 * 
 * Validates that an advance wage amount does not exceed the employee's net salary
 * for a specific period (year/month).
 * 
 * @package App\Rules\HR\Payroll
 */
class AdvanceWageLimitRule implements ValidationRule
{
    /**
     * Create a new rule instance.
     *
     * @param int|null $employeeId
     * @param int|null $year
     * @param int|null $month
     */
    public function __construct(
        protected ?int $employeeId,
        protected ?int $year,
        protected ?int $month,
    ) {}

    /**
     * Run the validation rule.
     *
     * @param string $attribute
     * @param mixed $value
     * @param \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Basic requirement checks
        if (!$this->employeeId || !$this->year || !$this->month || !$value) {
            return;
        }

        try {
            /** @var PayrollSimulatorInterface $simulator */
            $simulator = app(PayrollSimulatorInterface::class);
            
            // Simulate payroll for the specific employee and period
            $results = $simulator->simulateForEmployees(
                [$this->employeeId], 
                (int) $this->year, 
                (int) $this->month
            );
            
            // Extract net salary from simulation results
            $netSalary = (float) ($results[0]['data']['net_salary'] ?? 0);
            
            // Validate if requested amount exceeds the net salary
            if ((float) $value > $netSalary) {
                $fail(__('The amount exceeds the employee\'s net salary for this period (:amount).', [
                    'amount' => formatMoneyWithCurrency($netSalary)
                ]));
            }
        } catch (\Exception $e) {
            // Log the error but don't block the user unless necessary
            // In a senior-level implementation, we might want to fail gracefully or 
            // allow the transaction if the simulator is down, depending on business rules.
            Log::error("Advance Wage Validation Failure: [Employee ID: {$this->employeeId}] " . $e->getMessage());
        }
    }
}
