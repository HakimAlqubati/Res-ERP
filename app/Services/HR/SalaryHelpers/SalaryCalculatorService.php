<?php
declare(strict_types=1);

namespace App\Services\HR\SalaryHelpers;

use App\Enums\HR\Payroll\DailyRateMethod;
use App\Enums\HR\Payroll\SalaryTransactionSubType;
use App\Enums\HR\Payroll\SalaryTransactionType;
use App\Models\Employee;
use InvalidArgumentException;

/**
 * Professional, extensible salary calculator.
 *
 * Design notes:
 * - Policy hooks (pre/post) to allow injecting discounts/taxes caps etc.
 * - Clear separation of rate, overtime, deductions.
 * - Safe parsing of time inputs and consistent rounding.
 */
class SalaryCalculatorService
{
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
    protected array $totalApprovedOvertime = ['hours' => 0, 'minutes' => 0];
    protected float $lateHours = 0.0;   // from analyzer

    // Calculated rates
    protected float $dailyRate   = 0.0;
    protected float $hourlyRate  = 0.0;

    // Components
    protected float $absenceDeduction = 0.0;
    protected float $lateDeduction    = 0.0;
    protected float $overtimeHours    = 0.0;
    protected float $overtimeAmount   = 0.0;

    // Totals
    protected float $grossSalary = 0.0; // base + positive additions
    protected float $baseSalary  = 0.0;
    protected float $totalDeductions = 0.0; // absence + late + any policy-added
    protected float $netSalary   = 0.0;

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
     * @param string|array $totalApprovedOvertime   "
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
        string|array $totalApprovedOvertime,
    ): array {
        // Validate
        $this->assertPositive($salary, 'Salary');
        $this->assertPositive($workingDays, 'Working days');
        $this->assertPositive($dailyHours, 'Daily hours');
        $this->assertPositive($monthDays, 'Month days');

        // Bind inputs
        $this->employee     = $employee;
        $this->employeeData = $employeeData;
        $this->salary       = $salary;
        $this->workingDays  = $workingDays;
        $this->dailyHours   = $dailyHours;
        $this->monthDays    = $monthDays;

        $this->totalDuration          = is_array($totalDuration) ? $this->sanitizeHM($totalDuration) : $this->parseHM($totalDuration);
        $this->totalActualDuration    = is_array($totalActualDuration) ? $this->sanitizeHM($totalActualDuration) : $this->parseHM($totalActualDuration);
        $this->totalApprovedOvertime  = is_array($totalApprovedOvertime) ? $this->sanitizeHM($totalApprovedOvertime) : $this->parseHM($totalApprovedOvertime);
        $this->lateHours              = $this->extractLateHours($employeeData);

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

        // Totals
        $this->baseSalary     = $this->salary;
        $this->grossSalary    = $this->round($this->baseSalary + $this->overtimeAmount);
        $this->totalDeductions= $this->round($this->absenceDeduction + $this->lateDeduction);
        $this->netSalary      = $this->round($this->grossSalary - $this->totalDeductions);

        // Policy hooks (post calculation: taxes, caps, extra allowances…)
        foreach ($this->policyHooks as $hook) {
            $this->netSalary = $this->round($hook->afterTotals(
                employee:          $this->employee,
                context:           $this->employeeData,
                baseSalary:        $this->baseSalary,
                grossSalary:       $this->grossSalary,
                totalDeductions:   $this->totalDeductions,
                currentNet:        $this->netSalary,
                mut:               $this->mutableComponents(),
            ));
        }

        return $this->buildResult();
    }

    /* ===================== Core Steps ===================== */

    protected function computeRates(): void
    {
        $this->dailyRate = match ($this->dailyRateMethod) {
            DailyRateMethod::By30Days->value      => $this->salary / 30,
            DailyRateMethod::ByMonthDays->value   => $this->salary / $this->monthDays,
            default                               => $this->salary / $this->workingDays,
        };

        $this->hourlyRate = $this->dailyRate / $this->dailyHours;
    }

    protected function computeDeductions(): void
    {
        $this->absenceDeduction = $this->round($this->absentDays * $this->dailyRate);
        $this->lateDeduction    = $this->round($this->lateHours * $this->hourlyRate);

        // Hook: allow policies to alter deductions (caps, minimums…)
        foreach ($this->policyHooks as $hook) {
            $adj = $hook->adjustDeductions($this->employee, $this->employeeData, $this->absenceDeduction, $this->lateDeduction);
            if (is_array($adj) && count($adj) === 2) {
                [$this->absenceDeduction, $this->lateDeduction] = [
                    max(0.0, $this->round((float)$adj[0])),
                    max(0.0, $this->round((float)$adj[1])),
                ];
            }
        }
    }

    protected function computeOvertime(): void
    {
        $this->overtimeHours  = $this->toHours($this->totalApprovedOvertime);
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
            lateDeduction:    $this->lateDeduction,
            overtimeAmount:   $this->overtimeAmount,
            grossSalary:      $this->grossSalary,
            totalDeductions:  $this->totalDeductions,
        );
    }

    /* ===================== Result ===================== */

    protected function buildResult(): array
    {
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
            'overtime_amount'        => $this->round($this->overtimeAmount),
            'overtime_hours'         => $this->round($this->overtimeHours),

            // Rates
            'daily_rate'             => $this->round($this->dailyRate),
            'hourly_rate'            => $this->round($this->hourlyRate),

            // Attendance context
            'month_days'             => $this->monthDays,
            'working_days'           => $this->workingDays,
            'daily_hours'            => $this->dailyHours,
            'present_days'           => $this->presentDays,
            'absent_days'            => $this->absentDays,
            'total_duration'         => $this->totalDuration,
            'total_actual_duration'  => $this->totalActualDuration,
            'total_approved_overtime'=> $this->totalApprovedOvertime,
            'late_hours'             => $this->round($this->lateHours),

            // Raw details
            'details'                => $this->employeeData,

            // Transactions (ready for persistence layer)
            'transactions'           => $transactions,
        ];
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
        ];

        // Overtime (if any)
        if ($this->overtimeAmount > 0) {
            $tx[] = [
                'type'        => SalaryTransactionType::TYPE_ALLOWANCE,
                'sub_type'    => SalaryTransactionSubType::OVERTIME,
                'amount'      => $this->round($this->overtimeAmount),
                'operation'   => '+',
                'description' => 'Approved overtime',
            ];
        }

        // Absence
        if ($this->absenceDeduction > 0) {
            $tx[] = [
                'type'        => SalaryTransactionType::TYPE_DEDUCTION,
                'sub_type'    => SalaryTransactionSubType::ABSENCE,
                'amount'      => $this->round($this->absenceDeduction),
                'operation'   => '-',
                'description' => 'Absence deduction',
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
                    $tx[] = $t;
                }
            }
        }

        return $tx;
    }
}

/* ===================== Policy Hook Interface & DTO ===================== */

interface SalaryPolicyHookInterface
{
    /**
     * Called before computing rates (can enrich $context).
     */
    public function beforeRates(Employee $employee, array &$context): void;

    /**
     * Adjust overtime amount if needed (caps/multipliers).
     */
    public function adjustOvertime(Employee $employee, array $context, float $overtimeAmount): float;

    /**
     * Adjust absence/late deductions (caps/floors).
     * Return [absenceDeduction, lateDeduction].
     */
    public function adjustDeductions(Employee $employee, array $context, float $absenceDeduction, float $lateDeduction): array;

    /**
     * After totals computed; return final net salary (allow taxes, insurances…).
     * You may also modify $mut (gross/deductions/overtime…) to reflect changes externally if you wish.
     */
    public function afterTotals(
        Employee $employee,
        array $context,
        float $baseSalary,
        float $grossSalary,
        float $totalDeductions,
        float $currentNet,
        SalaryMutableComponents $mut
    ): float;

    /**
     * Optional extra line-items to be persisted as transactions.
     * Each item: ['type'=>..,'sub_type'=>..(opt),'amount'=>..,'operation'=>'+|-', 'description'=>..]
     */
    public function extraTransactions(Employee $employee, array $context): array;
}

final class SalaryMutableComponents
{
    public function __construct(
        public float $absenceDeduction,
        public float $lateDeduction,
        public float $overtimeAmount,
        public float $grossSalary,
        public float $totalDeductions,
    ) {}
}
