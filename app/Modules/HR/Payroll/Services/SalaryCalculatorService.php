<?php

declare(strict_types=1);

namespace App\Modules\HR\Payroll\Services;

/**
 * Main Class.
 */

use App\Enums\HR\Payroll\DailyRateMethod;
use App\Models\Employee;
use App\Modules\HR\Overtime\WeeklyLeaveCalculator\WeeklyLeaveCalculator;
use InvalidArgumentException;
use App\Modules\HR\Payroll\DTOs\CalculationContext;
use App\Modules\HR\Payroll\DTOs\SalaryMutableComponents;
use App\Modules\HR\Payroll\Traits\ResetsState;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use App\Rules\HR\Payroll\NotFuturePayrollPeriod;

use App\Modules\HR\Payroll\Contracts\SalaryCalculatorInterface;

// Calculators
use App\Modules\HR\Payroll\Calculators\RateCalculator;
use App\Modules\HR\Payroll\Calculators\AttendanceDeductionCalculator;
use App\Modules\HR\Payroll\Calculators\OvertimeCalculator;
use App\Modules\HR\Payroll\Calculators\PenaltyCalculator;
use App\Modules\HR\Payroll\Calculators\AllowanceCalculator;
use App\Modules\HR\Payroll\Calculators\AdvanceInstallmentCalculator;
use App\Modules\HR\Payroll\Calculators\AdvanceWageCalculator;
use App\Modules\HR\Payroll\Calculators\MealRequestCalculator;
use App\Modules\HR\Payroll\Calculators\GeneralDeductionCalculator;
use App\Modules\HR\Payroll\Calculators\TransactionBuilder;
use App\Modules\HR\Payroll\Calculators\MonthlyIncentiveCalculator;
use App\Modules\HR\Payroll\Calculators\CustomDeductionCalculator;
use App\Modules\HR\Payroll\Calculators\CarryForwardRecoveryCalculator;

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
        protected AdvanceWageCalculator $advanceWageCalculator,
        protected MealRequestCalculator $mealRequestCalculator,
        protected GeneralDeductionCalculator $generalDeductionCalculator,
        protected TransactionBuilder $transactionBuilder,
        protected MonthlyIncentiveCalculator $monthlyIncentiveCalculator,
        protected CustomDeductionCalculator $customDeductionCalculator,
        protected CarryForwardRecoveryCalculator $carryForwardRecoveryCalculator,
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
        ?Carbon $periodStart = null,
        bool $isMultiSegment = false, // true عندما للموظف أكثر من Segment في هذا الشهر
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

        // Check if employee has any assigned shifts (required days) in this period
        $requiredDays = (int)($employeeData['statistics']['required_days'] ?? 0);
        if ($requiredDays === 0 && $totalApprovedOvertime < 0) {
            throw new InvalidArgumentException(
                "Skipped: Employee [{$employee->name}] (No: {$employee->employee_no}) has no assigned shifts for this period. Salary cannot be calculated."
            );
        }

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
        } elseif ($this->dailyRateMethod === DailyRateMethod::ByCustomDays->value) {
            $rateWorkingDays = (int) settingWithDefault('custom_month_days', 30);
        }

        // Determine how many days should be paid for the current period ($payableDays)
        $payableDays = $rateWorkingDays;
        // if ($periodEnd && $periodEnd->day < $monthDays) {
        //     // Segment ends mid-month → use the calendar end-day directly.
        //     $payableDays = $periodEnd->day;
        // } elseif (
        //     $this->dailyRateMethod === DailyRateMethod::By30Days->value
        //     && $isMultiSegment
        //     && $monthDays === 31
        //     && $periodStart && $periodStart->day > 1
        // ) {
        //     // Last segment of a branch-transfer employee in a 31-day month:
        //     // subtract the rate-days already consumed by prior segments
        //     // so the total across all segments equals 30 (not 31).
        //     $previousUsedDays = (int) round(($periodStart->day -1) / $monthDays * $rateWorkingDays);
        //     $payableDays      = max(0, $rateWorkingDays - $previousUsedDays);
        // }

      
    if ($isMultiSegment) {
    // Branch-transfer employee (2+ segments in the same month): distribute
    // $rateWorkingDays proportionally across ALL segments using cumulative
    // boundaries, so segments telescope to exactly $rateWorkingDays —
    // regardless of segment count, month length, or $dailyRateMethod.
    $cumulativeRateDaysAt = fn (int $calendarDayIndex): int =>
        (int) round($calendarDayIndex / $monthDays * $rateWorkingDays);

    $startBoundary = $periodStart ? max(0, $periodStart->day - 1) : 0;
    $endBoundary   = $periodEnd ? min($periodEnd->day, $monthDays) : $monthDays;

    $payableDays = max(
        0,
        $cumulativeRateDaysAt($endBoundary) - $cumulativeRateDaysAt($startBoundary)
    );
    } elseif ($periodEnd && $periodEnd->day < $monthDays) {
        // Single-segment employee ending mid-month (new hire / termination):
        // use the calendar end-day directly. The $requiredDays cap below is
        // the real limiter for this case — it must NOT be scaled proportionally.
        $payableDays = $periodEnd->day;
    }
        // if($startBoundary != 0){

        //     dd($cumulativeRateDaysAt,
        //     $startBoundary,
        //     $endBoundary,
        //     $cumulativeRateDaysAt);
            
        // }   
        // Cap payable days by required shift days (exclude no_periods days)
        // For By30Days method: only skip the cap if the employee covers the full month
        // (requiredDays >= monthDays), so short months like February still get full salary.
        // If the employee has partial shifts (e.g. 6 out of 28), the cap still applies.
        if ($requiredDays < $payableDays) {
            $isFullMonthBy30 = $this->dailyRateMethod === DailyRateMethod::By30Days->value
                && $requiredDays >= $monthDays;

            if (!$isFullMonthBy30) {
                $payableDays = $requiredDays;
            }
        }

        // dd($payableDays,$monthDays);
        if (!$periodYear || !$periodMonth) {
            throw new InvalidArgumentException('periodYear and periodMonth are required to compute penalty deductions.');
        }

        // Professional Validation: Prevent future periods at the service level
        $validator = Validator::make(
            ['year' => $periodYear, 'month' => $periodMonth],
            ['month' => [new NotFuturePayrollPeriod()]]
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
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
            periodMonth: $periodMonth,
            periodEndDate: $periodEnd ? $periodEnd->toDateString() : null,
            periodStartDate: $periodStart?->toDateString(),   // ← بداية فترة الفرع الدقيقة
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

        // 6b. Calculate advance wages (pre-paid salary to be deducted)
        $advanceWages = $this->advanceWageCalculator->calculate($context);

        // 6c. Calculate meal requests
        $mealRequests = $this->mealRequestCalculator->calculate($context);

        // 6c. Calculate monthly incentives
        $monthlyIncentives = $this->monthlyIncentiveCalculator->calculate($context);

        // 6d. Calculate custom deductions
        $customDeductions = $this->customDeductionCalculator->calculate($context);

        // 6e. Calculate carry forward recovery (debts from previous months)
        $carryForwardRecovery = $this->carryForwardRecoveryCalculator->calculate($context);


        $statistics = $employeeData['statistics'];
        $totalDeductionDays =  $statistics['weekly_leave_calculation']['result']['total_deduction_days'];

        $overTimeDays = $statistics['weekly_leave_calculation']['result']['overtime_days'];
        $overtimeDaysAmount = ($overTimeDays * $rates->dailyRate) ?? 0;

        // جلب الإضافي اليومي اليدوي (بالأيام) مقيّدًا بفترة الفرع الفعلية
        $segmentStart = $periodStart ?? Carbon::create($periodYear, $periodMonth, 1)->startOfMonth();
        $segmentEnd   = $periodEnd   ?? Carbon::create($periodYear, $periodMonth, 1)->endOfMonth();

        $manualOvertimeRecords = $employee->dailyOvertimes()
            ->whereBetween('date', [$segmentStart->toDateString(), $segmentEnd->toDateString()])
            ->get();

        $manualOvertimeAmount = 0;
        $manualOvertimeTransactions = [];

        foreach ($manualOvertimeRecords as $record) {
            $recordAmount = (float) $rates->dailyRate;
            $manualOvertimeAmount += $recordAmount;

            $manualOvertimeTransactions[] = [
                'type'        => \App\Enums\HR\Payroll\SalaryTransactionType::TYPE_ALLOWANCE,
                'sub_type'    => \App\Enums\HR\Payroll\SalaryTransactionSubType::OVERTIME_DAYS,
                'amount'      => $recordAmount,
                'operation'   => '+',
                'description' => $record->reason ?? 'Manual Daily Overtime',
                'unit'        => 'day',
                'qty'         => 1,
                'rate'        => $this->round($rates->dailyRate),
                'multiplier'  => 1.0,
            ];
        }
        // --------------------------------------------------------------------

        // Calculate totals
        // If payableDays is NOT full month, calculate pro-rated base
        if ($payableDays < $rateWorkingDays) {
            $this->baseSalary = $this->round($rates->dailyRate * $payableDays);
        } else {
            $this->baseSalary = $salary;
        }

        $this->grossSalary = $this->round(
            $this->baseSalary + $overtime['amount'] + $allowances['total'] + $overtimeDaysAmount + $manualOvertimeAmount + ($monthlyIncentives['total'] ?? 0)
        );
        // 7. Calculate general deductions (taxes, insurance)
        // Statutory deductions (EPF, SOCSO, EIS) are typically based on Gross Wages EARNED.
        // Gross Wages should include Overtime, Allowances, etc.
        // However, we should subtract Unpaid Leave (Absent days) because that salary was never earned.
        // We should NOT subtract Late/Early/Penalties/Advances as those are deductions from earned salary.

        $baseForStatutoryDeductions = max(0, $this->grossSalary - $deductions->absenceDeduction);

        $dynamicDeductions = $this->generalDeductionCalculator->calculate($context, $baseForStatutoryDeductions);
        $dynamicTotal = (float)($dynamicDeductions['result'] ?? 0);

        // 7b. Calculate non-advance deductions
        $nonAdvanceDeductionsTotal = $this->round(
            $deductions->absenceDeduction +
                $deductions->lateDeduction +
                $deductions->earlyDepartureDeduction +
                $penalties['total'] +
                $advanceWages['total'] +
                $mealRequests['total'] +
                $deductions->missingHoursDeduction +
                $customDeductions['total'] +
                $carryForwardRecovery['total']
        );

        // 7c. Cap Advance Installments if necessary
        $availableNetForInstallments = $this->round($this->grossSalary - ($nonAdvanceDeductionsTotal + $dynamicTotal));
        $availableNetForInstallments = max(0, $availableNetForInstallments);

        $advanceShortfall = 0.0;
        if ($advanceInstallments['total'] > $availableNetForInstallments) {
            $originalAdvanceTotal = $advanceInstallments['total'];
            $capAmount = $availableNetForInstallments;
            $newAdvanceInstallmentTotal = 0.0;

            foreach ($advanceInstallments['items'] as &$item) {
                if ($capAmount <= 0) {
                    $item['amount'] = 0.0;
                    continue;
                }

                if ($item['amount'] > $capAmount) {
                    $item['amount'] = $this->round($capAmount);
                    $capAmount = 0.0;
                } else {
                    $capAmount -= $item['amount'];
                }
                $newAdvanceInstallmentTotal += $item['amount'];
            }
            unset($item); // Best Practice: Clean up reference

            // Filter out fully capped installments
            $advanceInstallments['items'] = array_values(array_filter($advanceInstallments['items'], fn($i) => $i['amount'] > 0));
            $advanceInstallments['total'] = $this->round($newAdvanceInstallmentTotal);
            $advanceShortfall = $this->round($originalAdvanceTotal - $advanceInstallments['total']);
        }

        $this->totalDeductions = $this->round($nonAdvanceDeductionsTotal + $advanceInstallments['total']);
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
            advanceWages: $advanceWages,
            carryForwardRecovery: $carryForwardRecovery,
            mealRequests: $mealRequests,
            dynamicDeductions: $dynamicDeductions,
            customDeductions: $customDeductions,
            monthlyIncentives: $monthlyIncentives,
            overtimeMultiplier: $this->overtimeMultiplier,
            policyHookTransactions: $policyHookTransactions,
            baseSalary: $this->baseSalary,
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

        // Add Manual Daily Overtime Transactions
        if (!empty($manualOvertimeTransactions)) {
            foreach ($manualOvertimeTransactions as $manualTx) {
                $transactions[] = $manualTx;
            }
        }

        // 9. Carry Forward: if net salary is negative or there is an advance shortfall, cap at 0 and record debt
        $carryForwarded = $advanceShortfall ?? 0.0;
        if ($finalNetSalary < 0) {
            $carryForwarded += $this->round(abs($finalNetSalary));
            $finalNetSalary = 0.0;
        }

        if ($carryForwarded > 0) {
            $notes = sprintf(
                "Gross Salary: %.2f | Processed Deductions: %.2f (Absence: %.2f, Late: %.2f, Early Departure: %.2f, Missing Hours: %.2f, Penalties: %.2f, Advances: %.2f, Meals: %.2f, Dynamic: %.2f) | Unrecovered/Deficit: %.2f",
                $this->grossSalary,
                $finalTotalDeductions,
                $deductions->absenceDeduction,
                $deductions->lateDeduction,
                $deductions->earlyDepartureDeduction,
                $deductions->missingHoursDeduction,
                $penalties['total'],
                $advanceInstallments['total'],
                $mealRequests['total'],
                $dynamicTotal + $customDeductions['total'],
                $carryForwarded
            );

            $descReason = ($advanceShortfall ?? 0) > 0 ? 'Unrecovered Advance Installment' : 'Net Salary Deficit';

            $transactions[] = [
                'type'        => \App\Enums\HR\Payroll\SalaryTransactionType::TYPE_CARRY_FORWARD,
                'sub_type'    => \App\Enums\HR\Payroll\SalaryTransactionSubType::CARRY_FORWARD,
                'amount'      => $carryForwarded,
                'operation'   => '-',
                'description' => 'Carry forward ' . $carryForwarded . ' (' . $descReason . ')',
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
            'advance_wages_total'    => $this->round($advanceWages['total']),
            'advance_wages'          => $advanceWages['items'],
            'meal_requests_total'    => $this->round($mealRequests['total']),
            'meal_requests'          => $mealRequests['items'],
            'custom_deductions_total'=> $this->round($customDeductions['total']),
            'custom_deductions'      => $customDeductions['items'],
            'monthly_incentives_total' => $this->round($monthlyIncentives['total'] ?? 0),
            'monthly_incentives'       => $monthlyIncentives['items'] ?? [],
            'carry_forward_recovery_total' => $this->round($carryForwardRecovery['total']),
            'carry_forward_recovery'       => $carryForwardRecovery['items'],
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
