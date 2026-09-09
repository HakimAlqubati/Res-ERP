<?php

namespace App\Rules\HR\Payroll;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Modules\HR\Payroll\Contracts\PayrollSimulatorInterface;
use App\Models\AdvanceWage;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

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
        protected ?int $ignoreAdvanceId = null,
    ) {}

    /**
     * One-liner façade for use in Observers or Services.
     *
     * @throws ValidationException
     */
    public static function check(AdvanceWage $advanceWage): void
    {
        $errors = [];

        (new static(
            employeeId: $advanceWage->employee_id ? (int) $advanceWage->employee_id : null,
            year: $advanceWage->year ? (int) $advanceWage->year : null,
            month: $advanceWage->month ? (int) $advanceWage->month : null,
            ignoreAdvanceId: $advanceWage->id ? (int) $advanceWage->id : null,
        ))->validate(
            attribute: 'amount',
            value: $advanceWage->amount,
            fail: static function (string $message) use (&$errors): void {
                $errors[] = $message;
            },
        );

        if (! empty($errors)) {
            throw ValidationException::withMessages(['amount' => $errors]);
        }
    }

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
            
            // Calculate existing approved pending advance wages
            $employee = \App\Models\Employee::find($this->employeeId);
            $query = $employee ? $employee->approvedPendingAdvanceWagesByPeriod((int) $this->year, (int) $this->month) : null;
            if ($query && $this->ignoreAdvanceId) {
                $query->where('id', '!=', $this->ignoreAdvanceId);
            }
            $existingAdvances = $query ? (float) $query->sum('amount') : 0;
            
            // Available amount
            $availableAmount = max(0, $netSalary - $existingAdvances);

            // Validate if requested amount exceeds the available amount
            if ((float) $value > $availableAmount) {
                $fail(__('The amount exceeds the employee\'s available net salary for this period (:amount).', [
                    'amount' => formatMoneyWithCurrency($availableAmount)
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
