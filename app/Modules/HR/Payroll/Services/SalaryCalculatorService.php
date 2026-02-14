<?php

declare(strict_types=1);

namespace App\Modules\HR\Payroll\Services;

use App\Enums\HR\Payroll\DailyRateMethod;
use App\Models\Employee;
use App\Modules\HR\Overtime\WeeklyLeaveCalculator\WeeklyLeaveCalculator;
use InvalidArgumentException;
use App\Modules\HR\Payroll\DTOs\CalculationContext;
use App\Modules\HR\Payroll\DTOs\SalaryMutableComponents;
use App\Modules\HR\Payroll\Traits\ResetsState;
use Carbon\Carbon;

use App\Modules\HR\Payroll\Contracts\SalaryCalculatorInterface;

// Calculators
use App\Modules\HR\Payroll\Calculators\RateCalculator;
use App\Modules\HR\Payroll\Calculators\AttendanceDeductionCalculator;
use App\Modules\HR\Payroll\Calculators\OvertimeCalculator;
use App\Modules\HR\Payroll\Calculators\PenaltyCalculator;
use App\Modules\HR\Payroll\Calculators\AllowanceCalculator;
use App\Modules\HR\Payroll\Calculators\AdvanceInstallmentCalculator;
use App\Modules\HR\Payroll\Calculators\MealRequestCalculator;
use App\Modules\HR\Payroll\Calculators\GeneralDeductionCalculator;
use App\Modules\HR\Payroll\Calculators\TransactionBuilder;
use App\Modules\HR\Payroll\Calculators\MonthlyIncentiveCalculator;

/**
 * The Core Payroll Calculation Engine.
 *
 * This service is the central authority for all payroll logic within the application.
 * It serves as the main entry point for salary computations, orchestrating the flow
 * of data through various specialized calculators (e.g., Overtime, Deductions, Allowances).
 *
 * Architectural Overview:
 * - **Orchestrator**: It aggregates results from granular calculators to build the final salary structure.
 * - **Extensible**: Supports `SalaryPolicyHookInterface` for injecting custom organizational policies
 *   (tax rules, caps, or dynamic adjustments) without modifying core logic.
 * - **Robust**: Ensures precision with consistent rounding strategies and safe time-tracking parsing.
 *
 * Usage:
 * calling `calculate()` triggers the full pipeline, returning a comprehensive array of
 * salary components, transactions, and statistics ready for persistence or simulation.
 */
class SalaryCalculatorService implements SalaryCalculatorInterface
{
    use ResetsState;

    // Defaults
    public const DEFAULT_OVERTIME_MULTIPLIER = 1.5;
    public const DEFAULT_ROUND_SCALE = 2;

    // Config
    protected string $dailyRateMethod;
    protected float $overtimeMultiplier;
    protected int $roundScale;

    // Internal state for result building
    protected float $baseSalary = 0.0;
    protected float $grossSalary = 0.0;
    protected float $totalDeductions = 0.0;
    protected float $netSalary = 0.0;

    public function __construct(
        protected RateCalculator $rateCalculator,
        protected AttendanceDeductionCalculator $attendanceDeductionCalculator,
        protected OvertimeCalculator $overtimeCalculator,
        protected PenaltyCalculator $penaltyCalculator,
        protected AllowanceCalculator $allowanceCalculator,
        protected AdvanceInstallmentCalculator $advanceInstallmentCalculator,
        protected MealRequestCalculator $mealRequestCalculator,
        protected GeneralDeductionCalculator $generalDeductionCalculator,
        protected TransactionBuilder $transactionBuilder,
        protected MonthlyIncentiveCalculator $monthlyIncentiveCalculator,
        /** @var SalaryPolicyHookInterface[] */
        protected array $policyHooks = []
    ) {
        $this->dailyRateMethod = DailyRateMethod::ByWorkingDays->value;
        $this->overtimeMultiplier = self::DEFAULT_OVERTIME_MULTIPLIER;
        $this->roundScale = self::DEFAULT_ROUND_SCALE;
    }

    /**
     * Main entry point.
     */
    public function calculate(
        Employee $employee,
        array $employeeData,
        float $salary,
        int $workingDays,
        int $dailyHours,
        int $monthDays,
        string|array $totalDuration,
        string|array $totalActualDuration,
        float $totalApprovedOvertime,
        ?int $periodYear = null,
        ?int $periodMonth = null,
        ?Carbon $periodEnd = null,
    ): array {
        $this->resetState();

        // Load settings
        $this->dailyRateMethod = settingWithDefault('daily_salary_calculation_method', DailyRateMethod::ByWorkingDays->value);
        $this->overtimeMultiplier = (float) settingWithDefault('overtime_hour_multiplier', self::DEFAULT_OVERTIME_MULTIPLIER);
        $this->overtimeCalculator->setMultiplier($this->overtimeMultiplier);

        // Validate
        $this->assertPositive($salary, 'Salary');
        $this->assertPositive($workingDays, 'Working days');
        $this->assertPositive($dailyHours, 'Daily hours');
        $this->assertPositive($monthDays, 'Month days');

        // Determine the denominator for rate calculation ($rateWorkingDays)
        $rateWorkingDays = $workingDays;
        if ($this->dailyRateMethod !== DailyRateMethod::ByEmployeeWorkingDays->value) {
            $rateWorkingDays = max(1, $monthDays - 4);
        }

        // Use standard month days for specific methods
        if ($this->dailyRateMethod === DailyRateMethod::By30Days->value) {
            $rateWorkingDays = 30;
        } elseif ($this->dailyRateMethod === DailyRateMethod::ByMonthDays->value) {
            $rateWorkingDays = $monthDays;
        }

        // Determine how many days should be paid for the current period ($payableDays)
        $payableDays = $rateWorkingDays;
        if ($periodEnd && $periodEnd->day < $monthDays) {
            $payableDays = $periodEnd->day;
        }

        if (!$periodYear || !$periodMonth) {
            throw new InvalidArgumentException('periodYear and periodMonth are required to compute penalty deductions.');
        }

        // Create calculation context
        $context = new CalculationContext(
            employee: $employee,
            employeeData: $employeeData,
            salary: $salary,
            workingDays: (int)$payableDays,
            dailyHours: $dailyHours,
            monthDays: $monthDays,
            periodYear: $periodYear,
            periodMonth: $periodMonth, // Using provided month
            periodEndDate: $periodEnd ? $periodEnd->toDateString() : null,
        );


        // Policy hooks (pre calculation)
        foreach ($this->policyHooks as $hook) {
            $hook->beforeRates($employee, $employeeData);
        }

        // 1. Calculate rates
        $rates = $this->rateCalculator->calculate(
            $salary,
            (int)$rateWorkingDays,
            $dailyHours,
            $monthDays,
            $this->dailyRateMethod
        );
        $context = $context->withRates($rates);

        // 2. Calculate attendance deductions
        $deductions = $this->attendanceDeductionCalculator->calculate($context);

        // 3. Calculate overtime
        $overtime = $this->overtimeCalculator->calculate($context, $totalApprovedOvertime);

        // Hook: allow policies to alter deductions and overtime
        foreach ($this->policyHooks as $hook) {
            $hook->adjustDeductions($employee, $employeeData, $deductions->absenceDeduction, $deductions->lateDeduction, $deductions->missingHoursDeduction, $deductions->earlyDepartureDeduction);
            $overtime['amount'] = $this->round($hook->adjustOvertime($employee, $employeeData, $overtime['amount']));
        }

        // 4. Calculate allowances
        $allowances = $this->allowanceCalculator->calculate($context);

        // 5. Calculate penalty deductions
        $penalties = $this->penaltyCalculator->calculate($context);

        // 6. Calculate advance installments
        $advanceInstallments = $this->advanceInstallmentCalculator->calculate($context);

        // 6b. Calculate meal requests
        $mealRequests = $this->mealRequestCalculator->calculate($context);

        // 6c. Calculate monthly incentives
        $monthlyIncentives = $this->monthlyIncentiveCalculator->calculate($context);


        $statistics = $employeeData['statistics'];
        $totalDeductionDays =  $statistics['weekly_leave_calculation']['result']['total_deduction_days'];

        $overTimeDays = $statistics['weekly_leave_calculation']['result']['overtime_days'];
        $overtimeDaysAmount = ($overTimeDays * $rates->dailyRate) ?? 0;
        // --------------------------------------------------------------------

        // Calculate totals
        // If payableDays is NOT full month, calculate pro-rated base
        if ($payableDays < $rateWorkingDays) {
            $this->baseSalary = $this->round($rates->dailyRate * $payableDays);
        } else {
            $this->baseSalary = $salary;
        }

        $this->grossSalary = $this->round(
            $this->baseSalary + $overtime['amount'] + $allowances['total'] + $overtimeDaysAmount + ($monthlyIncentives['total'] ?? 0)
        );
        $this->totalDeductions = $this->round(
            $deductions->absenceDeduction +
                $deductions->lateDeduction +
                $deductions->earlyDepartureDeduction +
                $penalties['total'] +
                $advanceInstallments['total'] +
                $mealRequests['total'] +
                $deductions->missingHoursDeduction
        );
        $this->netSalary = $this->round($this->grossSalary - $this->totalDeductions);

        // Policy hooks (post calculation: taxes, caps, extra allowances…)
        foreach ($this->policyHooks as $hook) {
            $this->netSalary = $this->round($hook->afterTotals(
                employee: $employee,
                context: $employeeData,
                baseSalary: $this->baseSalary,
                grossSalary: $this->grossSalary,
                totalDeductions: $this->totalDeductions,
                currentNet: $this->netSalary,
                mut: $this->mutableComponents($deductions, $overtime),
            ));
        }

        // 7. Calculate general deductions (taxes, insurance)
        $dynamicDeductions = $this->generalDeductionCalculator->calculate($context, $this->netSalary);
        $dynamicTotal = (float)($dynamicDeductions['result'] ?? 0);

        // Include dynamic deductions in totals
        $finalTotalDeductions = $this->round($this->totalDeductions + $dynamicTotal);
        $finalNetSalary = $this->round($this->grossSalary - $finalTotalDeductions);

        // Collect policy hook transactions
        $policyHookTransactions = [];
        foreach ($this->policyHooks as $hook) {
            $extra = $hook->extraTransactions($employee, $employeeData);
            if (is_array($extra) && !empty($extra)) {
                $policyHookTransactions = array_merge($policyHookTransactions, $extra);
            }
        }

        // 8. Build transactions
        $transactions = $this->transactionBuilder->build(
            context: $context,
            deductions: $deductions,
            overtime: $overtime,
            allowances: $allowances,
            penalties: $penalties,
            advanceInstallments: $advanceInstallments,
            mealRequests: $mealRequests,
            dynamicDeductions: $dynamicDeductions,
            monthlyIncentives: $monthlyIncentives,
            overtimeMultiplier: $this->overtimeMultiplier,
            policyHookTransactions: $policyHookTransactions
        );

        // Add Overtime Days Transaction (Unused Leave Balance)
        if ($overtimeDaysAmount > 0) {
            $transactions[] = [
                'type'        => \App\Enums\HR\Payroll\SalaryTransactionType::TYPE_ALLOWANCE,
                'sub_type'    => \App\Enums\HR\Payroll\SalaryTransactionSubType::OVERTIME_DAYS,
                'amount'      => $overtimeDaysAmount,
                'operation'   => '+',
                'description' => 'Overtime days (Unused Leave Balance)',
                'unit'        => 'day',
                'qty'         => $overTimeDays,
                'rate'        => $this->round($rates->dailyRate),
                'multiplier'  => 1.0,
            ];
        }

        // 9. Carry Forward: if net salary is negative, cap at 0 and record debt
        $carryForwarded = 0.0;
        if ($finalNetSalary < 0) {
            $carryForwarded = $this->round(abs($finalNetSalary));
            $finalNetSalary = 0.0;

            $notes = sprintf(
                "Gross Salary: %.2f | Total Deductions: %.2f (Absence: %.2f, Late: %.2f, Early Departure: %.2f, Missing Hours: %.2f, Penalties: %.2f, Advances: %.2f, Meals: %.2f, Dynamic: %.2f) | Deficit: %.2f",
                $this->grossSalary,
                $finalTotalDeductions,
                $deductions->absenceDeduction,
                $deductions->lateDeduction,
                $deductions->earlyDepartureDeduction,
                $deductions->missingHoursDeduction,
                $penalties['total'],
                $advanceInstallments['total'],
                $mealRequests['total'],
                $dynamicTotal,
                $carryForwarded
            );

            $transactions[] = [
                'type'        => \App\Enums\HR\Payroll\SalaryTransactionType::TYPE_CARRY_FORWARD,
                'sub_type'    => \App\Enums\HR\Payroll\SalaryTransactionSubType::CARRY_FORWARD,
                'amount'      => $carryForwarded,
                'operation'   => '-',
                'description' => 'Carry forward ' . $carryForwarded . ' (Gross ' . $this->grossSalary . ' - Ded. ' . $finalTotalDeductions . ')',
                'notes'       => $notes,
                'unit'        => 'flat',
                'qty'         => 1,
                'rate'        => $carryForwarded,
                'multiplier'  => 1.0,
            ];
        }

        // Parse durations
        $totalDurationParsed = is_array($totalDuration) ? $this->sanitizeHM($totalDuration) : $this->parseHM($totalDuration);
        $totalActualDurationParsed = is_array($totalActualDuration) ? $this->sanitizeHM($totalActualDuration) : $this->parseHM($totalActualDuration);

        // Extract attendance stats
        $stats = $employeeData['statistics'] ?? [];
        $presentDays = (int)($stats['present_days'] ?? $stats['present'] ?? 0);

        return [
            // Core
            'base_salary'            => $this->round($this->baseSalary),
            'gross_salary'           => $this->round($this->grossSalary),
            'total_deductions'       => $this->round($finalTotalDeductions),
            'net_salary'             => $this->round($finalNetSalary),
            'is_negative'            => $finalNetSalary < 0,

            // Components
            'absence_deduction'      => $this->round($deductions->absenceDeduction),
            'late_deduction'         => $this->round($deductions->lateDeduction),
            'missing_hours'          => $deductions->missingHours,
            'missing_hours_deduction' => $this->round($deductions->missingHoursDeduction),
            'early_departure_hours'      => $this->round($deductions->earlyDepartureHours),
            'early_departure_deduction'  => $this->round($deductions->earlyDepartureDeduction),
            'overtime_amount'        => $this->round($overtime['amount']),
            'overtime_hours'         => $this->round($overtime['hours']),

            'allowance_total' => $this->round($allowances['total']),
            'allowances'      => $allowances['items'],

            // Rates
            'daily_rate'             => $this->round($rates->dailyRate),
            'hourly_rate'            => $this->round($rates->hourlyRate),

            // Attendance context
            'month_days'             => $monthDays,
            'daily_rate_method'      => $this->dailyRateMethod,
            'working_days'           => $workingDays,
            'daily_hours'            => $dailyHours,
            'present_days'           => $presentDays,
            'absent_days'            => $totalDeductionDays,
            // 'absent_days'            => $deductions->absentDays,
            'total_duration'         => $totalDurationParsed,
            'total_actual_duration'  => $totalActualDurationParsed,
            'total_approved_overtime' => $this->round($totalApprovedOvertime),
            'late_hours'             => $this->round($deductions->lateHours),

            // Raw details
            'details'                => $employeeData,

            // Transactions (ready for persistence layer)
            'transactions'           => $transactions,
            'dynamic_deductions'     => $dynamicDeductions,
            'penalty_total'          => $this->round($penalties['total']),
            'penalties'              => $penalties['items'],
            'advance_installments_total' => $this->round($advanceInstallments['total']),
            'advance_installments'   => $advanceInstallments['items'],
            'meal_requests_total'    => $this->round($mealRequests['total']),
            'meal_requests'          => $mealRequests['items'],
            'monthly_incentives_total' => $this->round($monthlyIncentives['total'] ?? 0),
            'monthly_incentives'       => $monthlyIncentives['items'] ?? [],
        ];
    }

    /* ===================== Helpers ===================== */

    protected function parseHM(string $time): array
    {
        $parts = array_map('intval', explode(':', $time));
        return [
            'hours'   => $parts[0] ?? 0,
            'minutes' => $parts[1] ?? 0,
        ];
    }

    protected function sanitizeHM(array $hm): array
    {
        return [
            'hours'   => (int)($hm['hours'] ?? 0),
            'minutes' => (int)($hm['minutes'] ?? 0),
        ];
    }

    protected function assertPositive(float|int $value, string $label): void
    {
        if ($value <= 0) {
            throw new InvalidArgumentException("$label must be greater than 0.");
        }
    }

    protected function round(float $value): float
    {
        return round($value, $this->roundScale);
    }

    protected function mutableComponents($deductions, array $overtime): SalaryMutableComponents
    {
        return new SalaryMutableComponents(
            absenceDeduction: $deductions->absenceDeduction,
            lateDeduction: $deductions->lateDeduction,
            overtimeAmount: $overtime['amount'],
            grossSalary: $this->grossSalary,
            totalDeductions: $this->totalDeductions,
        );
    }

    protected array $defaultState = [
        'baseSalary' => 0.0,
        'grossSalary' => 0.0,
        'totalDeductions' => 0.0,
        'netSalary' => 0.0,
    ];

    protected function resetState(): void
    {
        $this->applyDefaults($this->defaultState);
    }
}
