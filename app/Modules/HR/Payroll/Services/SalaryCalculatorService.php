<?php

declare(strict_types=1);

namespace App\Modules\HR\Payroll\Services;

use App\Enums\HR\Payroll\DailyRateMethod;
use App\Enums\HR\Payroll\SalaryTransactionSubType;
use App\Enums\HR\Payroll\SalaryTransactionType;
use App\Models\Deduction;
use App\Models\Employee;
use InvalidArgumentException;
use App\Models\PenaltyDeduction;
use App\Modules\HR\Payroll\DTOs\SalaryMutableComponents;
use App\Modules\HR\Payroll\Traits\ResetsState;

use App\Modules\HR\Payroll\Contracts\SalaryCalculatorInterface;

use App\Models\AdvanceRequest; // NEW
use App\Models\EmployeeAdvanceInstallment; // NEW

/**
 * Professional, extensible salary calculator.
 *
 * Design notes:
 * - Policy hooks (pre/post) to allow injecting discounts/taxes caps etc.
 * - Clear separation of rate, overtime, deductions.
 * - Safe parsing of time inputs and consistent rounding.
 */
class SalaryCalculatorService implements SalaryCalculatorInterface
{
    use ResetsState;
    // Defaults
    public const DEFAULT_OVERTIME_MULTIPLIER = 1.5;
    public const DEFAULT_ROUND_SCALE = 2;

    // Inputs (immutable per calculation run)
    protected Employee $employee;
    protected array $employeeData = [];
    protected float $salary;
    protected int $workingDays;
    protected int $dailyHours;
    protected int $monthDays;

    // Attendance aggregates (parsed)
    protected int $presentDays = 0;
    protected int $absentDays  = 0;
    protected array $totalDuration = ['hours' => 0, 'minutes' => 0];
    protected array $totalActualDuration = ['hours' => 0, 'minutes' => 0];
    protected float $totalApprovedOvertime = 0.0;
    protected float $lateHours = 0.0;   // from analyzer
    protected float $missingHours = 0.0;

    // Calculated rates
    protected float $dailyRate   = 0.0;
    protected float $hourlyRate  = 0.0;

    // Components
    protected float $absenceDeduction = 0.0;
    protected float $lateDeduction    = 0.0;
    protected float $overtimeHours    = 0.0;
    protected float $overtimeAmount   = 0.0;
    protected float $missingHoursDeduction = 0.0; // NEW


    protected float $earlyDepartureHours = 0.0;       // NEW
    protected float $earlyDepartureDeduction = 0.0;   // NEW

    // Totals
    protected float $grossSalary = 0.0; // base + positive additions
    protected float $baseSalary  = 0.0;
    protected float $totalDeductions = 0.0; // absence + late + any policy-added
    protected float $netSalary   = 0.0;

    protected array $dynamicDeductions = [];

    // Period (for per-month penalties)
    protected ?int $periodYear  = null;
    protected ?int $periodMonth = null;

    // Penalties
    protected array $penaltyItems = [];   // list of approved penalties for the month
    protected float $penaltyTotal = 0.0;  // sum of penalties for the month

    // Advance installments
    protected array $advanceItems = [];      // list of scheduled installments in the month
    protected float $advanceInstallmentsTotal = 0.0; // sum of installments this month


    protected array $allowanceItems = [];
    protected float $allowanceTotal = 0.0;

    // Config
    public function __construct(
        protected string $dailyRateMethod = DailyRateMethod::ByWorkingDays->value,
        protected float $overtimeMultiplier = self::DEFAULT_OVERTIME_MULTIPLIER,
        protected int $roundScale = self::DEFAULT_ROUND_SCALE,
        /** @var SalaryPolicyHookInterface[] */
        protected array $policyHooks = [] // optional plug-ins (taxes, caps, bonuses…)
    ) {}

    /**
     * Main entry point.
     *
     * @param Employee $employee
     * @param array $employeeData Structured attendance & stats payload
     * @param float $salary Base salary (monthly)
     * @param int $workingDays Working days in schedule (e.g. 26)
     * @param int $dailyHours  Daily hours (e.g. 8)
     * @param int $monthDays   Actual month days (28..31)
     * @param string|array $totalDuration           "HH:MM:SS" or ['hours'=>..,'minutes'=>..]
     * @param string|array $totalActualDuration     "
     * @param float $totalApprovedOvertime   "
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
    ): array {

        $this->resetstate();

        $this->dailyRateMethod = settingWithDefault('daily_salary_calculation_method', DailyRateMethod::ByWorkingDays->value);
        $this->overtimeMultiplier = (float) settingWithDefault('overtime_hour_multiplier', self::DEFAULT_OVERTIME_MULTIPLIER);

        // Validate
        $this->assertPositive($salary, 'Salary');
        $this->assertPositive($workingDays, 'Working days');
        $this->assertPositive($dailyHours, 'Daily hours');
        $this->assertPositive($monthDays, 'Month days');

        // Bind inputs
        $this->employee     = $employee;
        $this->employeeData = $employeeData;
        $this->salary       = $salary;
        $this->workingDays  = $workingDays ?? 30;
        $this->dailyHours   = $dailyHours;
        $this->monthDays    = $monthDays;




        $this->totalDuration          = is_array($totalDuration) ? $this->sanitizeHM($totalDuration) : $this->parseHM($totalDuration);
        $this->totalActualDuration    = is_array($totalActualDuration) ? $this->sanitizeHM($totalActualDuration) : $this->parseHM($totalActualDuration);
        $this->totalApprovedOvertime = (float) $totalApprovedOvertime;
        $this->lateHours              = $this->extractLateHours($employeeData);
        $this->missingHours = $this->extractMissingHours($employeeData); // NEW
        $this->earlyDepartureHours = $this->extractEarlyDepartureHours($employeeData); // NEW


        $this->periodYear  = $periodYear;
        $this->periodMonth = $periodMonth;

        // Try to infer from employeeData if not provided
        if (!$this->periodYear || !$this->periodMonth) {
            throw new InvalidArgumentException('periodYear and periodMonth are required to compute penalty deductions.');
        }

        // Penalty deductions (approved in this month)
        $this->computePenaltyDeductions();

        // Allowances
        $this->computeAllowances();


        // Advance installments (scheduled in this month)
        $this->computeAdvanceInstallments(); // NEW

        // Attendance stats
        $this->extractAttendanceStats($employeeData);


        // Policy hooks (pre calculation)
        foreach ($this->policyHooks as $hook) {
            $hook->beforeRates($this->employee, $this->employeeData);
        }

        // Rates
        $this->computeRates();

        // Components
        $this->computeDeductions();
        $this->computeOvertime();

        // dd($this->dailyRate,$this->dailyRateMethod,$this->monthDays,$this->salary);
        // Totals
        $this->baseSalary     = $this->salary;
        $this->grossSalary    = $this->round($this->baseSalary
            +  $this->overtimeAmount +
            $this->allowanceTotal);
        $this->totalDeductions = $this->round(
            $this->absenceDeduction +
                $this->lateDeduction
                + $this->penaltyTotal
                + $this->advanceInstallmentsTotal
                + $this->missingHoursDeduction
        );
        $this->netSalary      = $this->round($this->grossSalary - $this->totalDeductions);

        // Policy hooks (post calculation: taxes, caps, extra allowances…)
        foreach ($this->policyHooks as $hook) {
            $this->netSalary = $this->round($hook->afterTotals(
                employee: $this->employee,
                context: $this->employeeData,
                baseSalary: $this->baseSalary,
                grossSalary: $this->grossSalary,
                totalDeductions: $this->totalDeductions,
                currentNet: $this->netSalary,
                mut: $this->mutableComponents(),
            ));
        }

        return $this->buildResult();
    }

    protected function computePenaltyDeductions(): void
    {
        $this->penaltyItems = [];
        $this->penaltyTotal = 0.0;

        // If period is unknown, skip silently (keeps backward compatibility)
        if (!$this->periodYear || !$this->periodMonth) {
            return;
        }

        // Fetch approved penalties for this employee in the given month/year
        $penalties = PenaltyDeduction::query()
            ->where('employee_id', $this->employee->id)
            ->where('status', PenaltyDeduction::STATUS_APPROVED)
            ->where('year',  $this->periodYear)
            ->where('month', $this->periodMonth)
            ->with(['deduction:id,name']) // to show a readable name
            ->get();

        foreach ($penalties as $p) {
            $amount = (float)$p->penalty_amount;
            if ($amount <= 0) continue;

            $this->penaltyItems[] = [
                'id'             => $p->id,
                'deduction_id'   => $p->deduction_id,
                'deduction_name' => optional($p->deduction)->name ?? 'Penalty deduction',
                'amount'         => $this->round($amount),
                'description'    => $p->description,
                'date'           => $p->date,
                'reference_type' => PenaltyDeduction::class,
                'reference_id'   => $p->id,
            ];

            $this->penaltyTotal += $amount;
        }

        $this->penaltyTotal = $this->round($this->penaltyTotal);
    }


    /* ===================== Core Steps ===================== */

    protected function computeRates(): void
    {
        $this->dailyRate = match ($this->dailyRateMethod) {
            DailyRateMethod::By30Days->value      => $this->salary / 30,
            DailyRateMethod::ByMonthDays->value   => $this->salary / $this->monthDays,
            default                               => $this->salary / $this->workingDays,
        };
        $this->dailyRate = $this->round($this->dailyRate, 2);

        $this->hourlyRate = $this->dailyRate / $this->dailyHours;
    }

    protected function computeDeductions(): void
    {
        $this->absenceDeduction = $this->round($this->absentDays * $this->dailyRate);
        $this->lateDeduction    = $this->round($this->lateHours * $this->hourlyRate);
        $this->missingHoursDeduction = $this->round($this->missingHours * $this->hourlyRate);
        $this->earlyDepartureDeduction = $this->round($this->earlyDepartureHours * $this->hourlyRate); // NEW

        // Hook: allow policies to alter deductions (caps, minimums…)
        foreach ($this->policyHooks as $hook) {
            $adj = $hook->adjustDeductions(
                $this->employee,
                $this->employeeData,
                $this->absenceDeduction,
                $this->lateDeduction,
                $this->missingHoursDeduction,
                $this->earlyDepartureDeduction
            );
            if (is_array($adj) && count($adj) === 2) {
                [$this->absenceDeduction, $this->lateDeduction, $this->missingHoursDeduction] = [
                    max(0.0, $this->round((float)$adj[0])),
                    max(0.0, $this->round((float)$adj[1])),
                    max(0.0, $this->round((float)$adj[2])),
                ];
            }
        }
    }

    protected function computeOvertime(): void
    {
        $this->overtimeHours  = $this->totalApprovedOvertime;
        $this->overtimeAmount = $this->round($this->overtimeHours * $this->hourlyRate * $this->overtimeMultiplier);

        // Hook: policies can cap or boost overtime
        foreach ($this->policyHooks as $hook) {
            $this->overtimeAmount = $this->round($hook->adjustOvertime($this->employee, $this->employeeData, $this->overtimeAmount));
        }
    }

    /* ===================== Helpers ===================== */

    protected function extractAttendanceStats(array $data): void
    {
        $stats = $data['statistics'] ?? [];
        $this->presentDays = (int)($stats['present_days'] ?? $stats['present'] ?? 0);
        $this->absentDays  = (int)($stats['absent'] ?? $stats['absent_days'] ?? 0);
    }

    protected function extractMissingHours(array $data): float
    {
        $mh = $data['total_missing_hours'] ?? null;
        if (is_array($mh) && isset($mh['total_hours'])) {
            return (float) $mh['total_hours'];
        }
        return 0.0;
    }

    protected function extractEarlyDepartureHours(array $data): float
    {
        $ed = $data['total_early_departure_minutes'] ?? null;
        if (is_array($ed) && isset($ed['total_hours'])) {
            return (float) $ed['total_hours'];
        }
        return 0.0;
    }



    protected function extractLateHours(array $data): float
    {
        // Expect: ['late_hours']['totalHoursFloat'] or a total minutes/seconds form
        $late = $data['late_hours'] ?? null;
        if (is_array($late)) {
            if (isset($late['totalHoursFloat'])) {
                return (float)$late['totalHoursFloat'];
            }
            // Fallback: try hh:mm
            if (isset($late['hours'], $late['minutes'])) {
                return $this->toHours(['hours' => (int)$late['hours'], 'minutes' => (int)$late['minutes']]);
            }
        }
        return 0.0;
    }

    protected function parseHM(string $time): array
    {
        // Accept "HH:MM" or "HH:MM:SS"
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

    protected function toHours(array $hm): float
    {
        return (int)$hm['hours'] + ((int)$hm['minutes'] / 60);
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

    protected function mutableComponents(): SalaryMutableComponents
    {
        return new SalaryMutableComponents(
            absenceDeduction: $this->absenceDeduction,
            lateDeduction: $this->lateDeduction,
            overtimeAmount: $this->overtimeAmount,
            grossSalary: $this->grossSalary,
            totalDeductions: $this->totalDeductions,
        );
    }

    /* ===================== Result ===================== */

    protected function buildResult(): array
    {
        $this->dynamicDeductions = $this->generalDeduction();
        $dynamicTotal = (float)($this->dynamicDeductions['result'] ?? 0);

        // include dynamic deductions in totals
        $finalTotalDeductions = $this->round($this->totalDeductions + $dynamicTotal);
        $finalNetSalary       = $this->round($this->grossSalary - $finalTotalDeductions);


        $transactions = $this->buildTransactions();

        return [
            // Core
            'base_salary'            => $this->round($this->baseSalary),
            'gross_salary'           => $this->round($this->grossSalary),
            'total_deductions'       => $this->round($this->totalDeductions),
            'net_salary'             => $this->round($this->netSalary),
            'is_negative'            => $this->netSalary < 0,

            // Components
            'absence_deduction'      => $this->round($this->absenceDeduction),
            'late_deduction'         => $this->round($this->lateDeduction),
            'missing_hours' => $this->missingHours,
            'missing_hours_deduction' => $this->round($this->missingHoursDeduction), // NEW

            'early_departure_hours'      => $this->round($this->earlyDepartureHours), // NEW
            'early_departure_deduction'  => $this->round($this->earlyDepartureDeduction), // NEW


            'overtime_amount'        => $this->round($this->overtimeAmount),
            'overtime_hours'         => $this->round($this->overtimeHours),

            'allowance_total' => $this->round($this->allowanceTotal),
            'allowances'      => $this->allowanceItems,


            // Rates
            'daily_rate'             => $this->round($this->dailyRate, 2),
            'hourly_rate'            => $this->round($this->hourlyRate),

            // Attendance context
            'month_days'             => $this->monthDays,
            'daily_rate_method'      => $this->dailyRateMethod,
            'working_days'           => $this->workingDays,
            'daily_hours'            => $this->dailyHours,
            'present_days'           => $this->presentDays,
            'absent_days'            => $this->absentDays,
            'total_duration'         => $this->totalDuration,
            'total_actual_duration'  => $this->totalActualDuration,
            'total_approved_overtime' => $this->round($this->totalApprovedOvertime),
            'late_hours'             => $this->round($this->lateHours),

            // Raw details
            'details'                => $this->employeeData,

            // Transactions (ready for persistence layer)
            'transactions'           => $transactions,
            'dynamic_deductions' => $this->dynamicDeductions,
            'penalty_total' => $this->round($this->penaltyTotal),
            'penalties'     => $this->penaltyItems,
            'advance_installments_total' => $this->round($this->advanceInstallmentsTotal), // NEW
            'advance_installments'       => $this->advanceItems, // NEW


        ];
    }

    public function generalDeduction()
    {
        $employee =  $this->employee;
        $generalDeducationTypes = Deduction::where('is_specific', 0)
            ->where('active', 1)
            ->select(
                'name',
                'is_percentage',
                'amount',
                'percentage',
                'id',
                'condition_applied_v2',
                'nationalities_applied',
                'less_salary_to_apply',
                'has_brackets',
                'applied_by',
                'employer_percentage',
                'employer_amount'
            )
            ->with('brackets')
            ->get();
        $deduction = [];
        foreach ($generalDeducationTypes as  $deductionType) {

            if ($deductionType->condition_applied_v2 == Deduction::CONDITION_APPLIED_V2_ALL) {
                $deduction[] = $deductionType;
            }
            if (
                $deductionType->condition_applied_v2 == Deduction::CONDITION_APPLIED_V2_CITIZEN_EMPLOYEE &&
                $employee->is_citizen
                && $deductionType->has_brackets == 1
            ) {
                $deduction[] = $deductionType;
            }
            if (
                $deductionType->condition_applied_v2 == Deduction::CONDITION_APPLIED_V2_CITIZEN_EMPLOYEE &&
                $employee->is_citizen
            ) {
                $deduction[] = $deductionType;
            }
            // if (
            //     $deductionType->condition_applied_v2 == Deduction::CONDITION_APPLIED_V2_CITIZEN_EMPLOYEE &&
            //     $employee->is_citizen
            //     && $deductionType->has_brackets == 1
            // ) {
            //     $deduction[] = $deductionType;
            // }
            if (
                $deductionType->condition_applied_v2 == Deduction::CONDITION_APPLIED_V2_CITIZEN_EMPLOYEE_AND_FOREIGN_HAS_PASS &&
                ($employee->is_citizen || ($employee->has_employee_pass))
                // && $basicSalary >= $deductionType->less_salary_to_apply
            ) {
                $deduction[] = $deductionType;
            }
        }
        $generalDedeucationResultCalculated = $this->calculateDeductions($deduction, $this->netSalary);
        return $generalDedeucationResultCalculated;
    }
    public function calculateDeductions(array $deductions, float $basicSalary): array
    {
        $finalDeductions = [];
        $totalDeductions = 0.0;

        foreach ($deductions as $deduction) {
            $deductionAmount = 0.0;
            $employerAmount = 0.0;
            $effectivePercentage = null;
            $notes = null;
            $appliedBrackets = null;

            // Calculate employee deduction
            if (isset($deduction->has_brackets) && $deduction->has_brackets && $deduction->brackets->isNotEmpty()) {
                // Bracket-based calculation (e.g., progressive tax)
                $taxResult = $deduction->calculateTax($basicSalary);
                $deductionAmount = $taxResult['monthly_tax'] ?? 0;
                $effectivePercentage = $taxResult['effective_percentage'] ?? null;
                $notes = $taxResult['notes'] ?? null;
                $appliedBrackets = $taxResult['applied_brackets'] ?? null;
            } elseif ($deduction->is_percentage) {
                // Percentage-based calculation
                $deductionAmount = ($basicSalary * $deduction->percentage) / 100;
                $effectivePercentage = $deduction->percentage;
                $notes = sprintf(
                    "Flat rate deduction: %.2f%% of %.2f = %.2f",
                    $deduction->percentage,
                    $basicSalary,
                    $deductionAmount
                );
            } else {
                // Fixed amount
                $deductionAmount = (float) $deduction->amount;
                $notes = sprintf("Fixed amount deduction: %.2f", $deductionAmount);
            }

            // Calculate employer contribution
            if ($deduction->employer_percentage > 0) {
                $employerAmount = ($basicSalary * $deduction->employer_percentage) / 100;
            } elseif ($deduction->employer_amount > 0) {
                $employerAmount = (float) $deduction->employer_amount;
            }

            $totalDeductions += $deductionAmount;

            $deductionArray = $deduction->toArray();

            $finalDeductions[] = [
                'id' => $deductionArray['id'],
                'name' => $deductionArray['name'],
                'deduction_amount' => $this->round($deductionAmount),
                'employer_deduction_amount' => $this->round($employerAmount),
                'is_percentage' => $deductionArray['is_percentage'],
                'amount_value' => $deductionArray['amount'],
                'percentage_value' => $deductionArray['percentage'],
                'applied_by' => $deductionArray['applied_by'],
                'has_brackets' => $deductionArray['has_brackets'] ?? false,
                'effective_percentage' => $effectivePercentage,
                'notes' => $notes,
                'applied_brackets' => $appliedBrackets,
            ];
        }

        $finalDeductions['result'] = $totalDeductions;

        return $finalDeductions;
    }
    protected function buildTransactions(): array
    {
        $tx = [];

        // Base salary
        $tx[] = [
            'type'        => SalaryTransactionType::TYPE_SALARY,
            'sub_type'    => SalaryTransactionSubType::BASE_SALARY,
            'amount'      => $this->round($this->salary),
            'operation'   => '+',
            'description' => 'Base salary',

            'unit'        => 'day',
            'qty'         => $this->workingDays,
            'rate'        => $this->round($this->dailyRate, 2),
            'multiplier'  => 1.0,
        ];

        // Overtime (if any)
        if ($this->overtimeAmount > 0) {
            $tx[] = [
                'type'        => SalaryTransactionType::TYPE_ALLOWANCE,
                'sub_type'    => SalaryTransactionSubType::OVERTIME,
                'amount'      => $this->round($this->overtimeAmount),
                'operation'   => '+',
                'description' => 'Approved overtime',

                'unit'        => 'hour',
                'qty'         => $this->round($this->overtimeHours),
                'rate'        => $this->round($this->hourlyRate, 4),
                'multiplier'  => $this->overtimeMultiplier,
            ];
        }

        // Allowances
        if (!empty($this->allowanceItems)) {
            foreach ($this->allowanceItems as $a) {
                $tx[] = [
                    'type'        => SalaryTransactionType::TYPE_ALLOWANCE,
                    'sub_type'    => \Illuminate\Support\Str::slug($a['name']),
                    'amount'      => $this->round($a['amount']),
                    'operation'   => '+',
                    'description' => $a['name'],
                    'unit'        => null,
                    'qty'         => null,
                    'rate'        => null,
                    'multiplier'  => null,
                ];
            }
        }


        // Absence
        if ($this->absenceDeduction > 0) {
            $tx[] = [
                'type'        => SalaryTransactionType::TYPE_DEDUCTION,
                'sub_type'    => SalaryTransactionSubType::ABSENCE,
                'amount'      => $this->round($this->absenceDeduction),
                'operation'   => '-',
                'description' => 'Absence deduction',


                'unit'        => 'day',
                'qty'         => $this->absentDays,
                'rate'        => $this->round($this->dailyRate, 2),
                'multiplier'  => 1.0,
            ];
        }


        // missing hours
        if ($this->missingHoursDeduction > 0) {
            $tx[] = [
                'type'        => SalaryTransactionType::TYPE_DEDUCTION,
                'sub_type'    => SalaryTransactionSubType::MISSING_HOURS,
                'amount'      => $this->round($this->missingHoursDeduction),
                'operation'   => '-',
                'description' => 'Missing hours deduction',


                'unit'        => 'hour',
                'qty'         => $this->missingHours,
                'rate'        => $this->round($this->hourlyRate, 2),
                'multiplier'  => 1.0,
            ];
        }

        // Early Departure
        if ($this->earlyDepartureDeduction > 0) {
            $tx[] = [
                'type'        => SalaryTransactionType::TYPE_DEDUCTION,
                'sub_type'    => SalaryTransactionSubType::EARLY_DEPARTURE_HOURS,
                'amount'      => $this->round($this->earlyDepartureDeduction),
                'operation'   => '-',
                'description' => 'Early departure deduction',

                'unit'        => 'hour',
                'qty'         => $this->round($this->earlyDepartureHours),
                'rate'        => $this->round($this->hourlyRate, 2),
                'multiplier'  => 1.0,
            ];
        }


        // Late
        if ($this->lateDeduction > 0) {
            $tx[] = [
                'type'        => SalaryTransactionType::TYPE_DEDUCTION,
                'sub_type'    => SalaryTransactionSubType::LATE,
                'amount'      => $this->round($this->lateDeduction),
                'operation'   => '-',
                'description' => 'Late deduction',

                'unit'        => 'hour',
                'qty'         => $this->round($this->lateHours),
                'rate'        => $this->round($this->hourlyRate, 4),
                'multiplier'  => 1.0,
            ];
        }

        // Hooks can append extra transactions (taxes, social insurance, bonuses…)
        foreach ($this->policyHooks as $hook) {
            $extra = $hook->extraTransactions($this->employee, $this->employeeData);
            if (is_array($extra) && !empty($extra)) {
                foreach ($extra as $t) {
                    // minimal validation
                    if (!isset($t['type'], $t['amount'], $t['operation'])) continue;
                    $t['amount'] = $this->round((float)$t['amount']);

                    // اضمن وجود مفاتيح الأعمدة الجديدة (حتى لو null)
                    $t['unit']       = $t['unit']       ?? null;
                    $t['qty']        = isset($t['qty']) ? (float)$t['qty'] : null;
                    $t['rate']       = isset($t['rate']) ? (float)$t['rate'] : null;
                    $t['multiplier'] = isset($t['multiplier']) ? (float)$t['multiplier'] : null;
                    $tx[] = $t;
                }
            }
        }

        foreach ($this->dynamicDeductions as $key => $ded) {
            if ($key === 'result') continue;

            $employerAmount = (float)($ded['employer_deduction_amount'] ?? 0);
            if ($employerAmount <= 0) continue;

            $tx[] = [
                'type'         => SalaryTransactionType::TYPE_EMPLOYER_CONTRIBUTION,
                'sub_type'     => $ded['name'] ?? SalaryTransactionType::TYPE_EMPLOYER_CONTRIBUTION,
                'amount'       => $this->round($employerAmount),
                'operation'    => null, // التزام على الشركة وليس الموظف
                'description'  => $ded['name'] ?? 'Employer contribution',
                'deduction_id' => $ded['id'] ?? null,
                // 'reference_id' => $ded['id'],
                // 'reference_type' => Deduction::class,
            ];
        }


        // Dynamic general deductions from property
        foreach ($this->dynamicDeductions as $key => $ded) {
            if ($key === 'result') continue;

            $amount = (float)($ded['deduction_amount'] ?? 0);
            if ($amount <= 0) continue;

            $tx[] = [
                'type'         => SalaryTransactionType::TYPE_DEDUCTION,
                'sub_type'     => \Illuminate\Support\Str::slug($ded['name']),
                'amount'       => $this->round($amount),
                'operation'    => '-',
                'description'  => $ded['name'] ?? 'General deduction',
                'notes'        => $ded['notes'] ?? null,
                'effective_percentage' => $ded['effective_percentage'] ?? null,
                'deduction_id' => $ded['id'] ?? null,
                'applied_by'   => $ded['applied_by'] ?? null,
                'reference_type' => Deduction::class,
                'reference_id' => $ded['id'] ?? null,
            ];
        }

        // Approved penalty deductions as transactions
        if (!empty($this->penaltyItems)) {
            foreach ($this->penaltyItems as $pen) {
                $tx[] = [
                    'type'         => SalaryTransactionType::TYPE_DEDUCTION,
                    'sub_type'     => \Illuminate\Support\Str::slug($pen['deduction_name'] ?? 'penalty'),
                    'amount'       => $this->round((float)$pen['amount']),
                    'operation'    => '-',
                    'description'  => $pen['description'] ?? ($pen['deduction_name'] ?? 'Penalty deduction'),
                    // Helpful for persistence layer (morph reference)
                    'reference_type' => $pen['reference_type'] ?? PenaltyDeduction::class,
                    'reference_id'   => $pen['reference_id']  ?? $pen['id'] ?? null,
                    'deduction_id'   => $pen['deduction_id']  ?? null,
                ];
            }
        }

        // Advance installments as transactions
        if (!empty($this->advanceItems)) {
            foreach ($this->advanceItems as $adv) {
                $descParts = [];
                if (!empty($adv['code'])) {
                    $descParts[] = $adv['code'];
                }
                if (!empty($adv['sequence']) && !empty($adv['months'])) {
                    $descParts[] = "installment {$adv['sequence']}/{$adv['months']}";
                }
                if (!empty($adv['due_date'])) {
                    $descParts[] = "due {$adv['due_date']}";
                }
                $desc = 'Advance installment';
                if (!empty($descParts)) {
                    $desc .= ' (' . implode(', ', $descParts) . ')';
                }

                $tx[] = [
                    'type'          => SalaryTransactionType::TYPE_DEDUCTION,
                    'sub_type'      => SalaryTransactionSubType::ADVANCE_INSTALLMENT->value,
                    'amount'        => $this->round((float)$adv['amount']),
                    'operation'     => '-',
                    'description'   => $desc,
                    // ربط مباشر بالقسط وليس السلفة
                    'reference_type' => EmployeeAdvanceInstallment::class,
                    'reference_id'   => $adv['installment_id'] ?? null,
                    // معلومات إضافية للتتبع
                    'application_id'     => $adv['application_id'] ?? null,
                    'advance_request_id' => $adv['advance_request_id'] ?? null,
                ];
            }
        }


        return $tx;
    }
    protected array $defaultState = [
        'employeeData' => [],
        'presentDays' => 0,
        'absentDays' => 0,
        'totalDuration' => ['hours' => 0, 'minutes' => 0],
        'totalActualDuration' => ['hours' => 0, 'minutes' => 0],
        'totalApprovedOvertime' => 0.0,
        'lateHours' => 0.0,
        'dailyRate' => 0.0,
        'hourlyRate' => 0.0,
        'absenceDeduction' => 0.0,
        'lateDeduction' => 0.0,
        'overtimeHours' => 0.0,
        'overtimeAmount' => 0.0,
        'grossSalary' => 0.0,
        'baseSalary' => 0.0,
        'totalDeductions' => 0.0,
        'netSalary' => 0.0,
        'dynamicDeductions' => [],
        'periodYear' => null,
        'periodMonth' => null,
        'penaltyItems' => [],
        'penaltyTotal' => 0.0,
        'advanceItems' => [],
        'advanceInstallmentsTotal' => 0.0,
        'missingHours' => 0.0,
        'earlyDepartureHours' => 0.0,        // NEW
        'earlyDepartureDeduction' => 0.0,    // NEW


    ];

    protected function resetState(): void
    {
        $this->applyDefaults($this->defaultState);
    }

    protected function computeAdvanceInstallments(): void
    {
        $this->advanceItems = [];
        $this->advanceInstallmentsTotal = 0.0;

        if (!$this->periodYear || !$this->periodMonth) {
            return;
        }

        // حدود الشهر
        $start = sprintf('%04d-%02d-01', $this->periodYear, $this->periodMonth);
        // استخدام endOfMonth بدون Carbon هنا: أبسط بناء تاريخ
        $end   = date('Y-m-t', strtotime($start));

        // نأتي بأقساط هذا الموظف المجدولة وغير المسددة، والتي تاريخ استحقاقها ضمن الشهر
        $rows = EmployeeAdvanceInstallment::query()
            ->where('employee_id', $this->employee->id)
            ->where('is_paid', false)
            ->whereBetween('due_date', [$start, $end])
            ->with([
                // نحتاج كود السلفة والـ application_id للرجوع
                'application:id,employee_id', // فقط لو لديك علاقة معرفة، وإلا سنجلبها يدويًا
            ])
            ->get([
                'id',
                'application_id',
                'sequence',
                'installment_amount',
                'due_date',
                'status',
            ]);

        if ($rows->isEmpty()) {
            return;
        }

        // نجلب أكواد السلف المرتبطة عبر application_id
        $applicationIds = $rows->pluck('application_id')->filter()->unique()->values()->all();

        $codesByApp = [];
        if (!empty($applicationIds)) {
            // AdvanceRequest مخزن فيه application_id و code
            $advMeta = AdvanceRequest::query()
                ->whereIn('application_id', $applicationIds)
                ->get(['id', 'application_id', 'code', 'number_of_months_of_deduction'])
                ->keyBy('application_id');

            foreach ($advMeta as $appId => $rec) {
                $codesByApp[$appId] = [
                    'code' => (string) ($rec->code ?? ''),
                    'advance_request_id' => (int) $rec->id,
                    'months' => (int) ($rec->number_of_months_of_deduction ?? 0),
                ];
            }
        }

        foreach ($rows as $r) {
            $meta = $codesByApp[$r->application_id] ?? ['code' => '', 'advance_request_id' => null, 'months' => 0];

            $amount = (float) $r->installment_amount;
            if ($amount <= 0) {
                continue;
            }

            $this->advanceItems[] = [
                'installment_id' => (int) $r->id,
                'application_id' => (int) $r->application_id,
                'advance_request_id' => $meta['advance_request_id'],
                'sequence' => (int) $r->sequence,
                'months' => (int) $meta['months'],
                'amount' => $this->round($amount),
                'due_date' => $r->due_date,
                'code' => $meta['code'],
            ];

            $this->advanceInstallmentsTotal += $amount;
        }

        $this->advanceInstallmentsTotal = $this->round($this->advanceInstallmentsTotal);
    }


    protected function computeAllowances(): void
    {
        $this->allowanceItems = [];
        $this->allowanceTotal = 0.0;

        // 1) Allowances العامة
        $generalAllowances = \App\Models\Allowance::query()
            ->where('is_specific', 0)
            ->where('active', 1)
            ->get(['id', 'name', 'is_percentage', 'amount', 'percentage']);

        foreach ($generalAllowances as $a) {
            $amount = $a->is_percentage
                ? ($this->salary * ($a->percentage / 100))
                : (float)$a->amount;

            if ($amount <= 0) continue;

            $this->allowanceItems[] = [
                'id' => $a->id,
                'name' => $a->name,
                'amount' => $this->round($amount),
                'is_percentage' => $a->is_percentage,
                'value' => $a->is_percentage ? $a->percentage : $a->amount,
                'type' => 'general',
            ];

            $this->allowanceTotal += $amount;
        }

        // 2) Allowances الخاصة بالموظف
        $specificAllowances = $this->employee->allowances()
            ->with('allowance:id,name,is_percentage,amount,percentage')
            ->get();

        foreach ($specificAllowances as $empAllowance) {
            $a = $empAllowance->allowance;
            if (!$a) continue;

            // إذا الموظف عنده نسبة أو مبلغ خاص -> استخدمه، غير كذا fallback على البدل الأساسي
            $isPercentage = $empAllowance->is_percentage ?? $a->is_percentage;
            $percentage   = $empAllowance->percentage   ?? $a->percentage;
            $fixedAmount  = $empAllowance->amount       ?? $a->amount;

            $amount = $isPercentage
                ? ($this->salary * ($percentage / 100))
                : (float) $fixedAmount;

            if ($amount <= 0) continue;

            $this->allowanceItems[] = [
                'id'            => $a->id,
                'name'          => $a->name,
                'amount'        => $this->round($amount),
                'is_percentage' => (bool) $isPercentage,
                'value'         => $isPercentage ? $percentage : $fixedAmount,
                'type'          => 'specific',
            ];

            $this->allowanceTotal += $amount;
        }
    }
}
