<?php

namespace App\Services\HR\SalaryHelpers;

use App\Enums\HR\Payroll\DailyRateMethod;
use App\Enums\HR\Payroll\SalaryTransactionSubType;
use App\Enums\HR\Payroll\SalaryTransactionType;
use App\Models\Deduction;
use App\Models\Employee;
use InvalidArgumentException;

class SalaryCalculatorService
{
        // Constants
        public const OVERTIME_MULTIPLIER = 1.5;

        // Inputs
        protected array $employeeData;
        protected float $salary;
        protected int $dailyHours;
        protected int $monthDays;
        protected int $workingDays;
        protected float $lateHours = 0;
        protected float $missingHours = 0;

        // Parsed attendance values
        protected int $presentDays = 0;
        protected int $absentDays = 0;
        protected array $totalDuration = ['hours' => 0, 'minutes' => 0];
        protected array $totalActualDuration = ['hours' => 0, 'minutes' => 0];
        protected array $totalOvertime = ['hours' => 0, 'minutes' => 0];


        // Calculated values
        protected float $dailyRate;
        protected float $hourlyRate;
        protected float $absenceDeduction;
        protected float $lateDeduction;
        protected float $overtimeHours;
        protected float $overtimeAmount;
        protected float $baseSalary;
        protected float $netSalary;

        protected $employee;
        public function __construct(
                protected string $dailyRateMethod = DailyRateMethod::ByWorkingDays->value
        ) {}
        /**
         * Main entry to run salary calculation
         */
        public function calculate(
                $employee,
                array $employeeData,
                float $salary,
                int $workingDays,
                int $dailyHours,
                int $monthDays,
                string|array $totalDuration,
                string|array $totalActualDuration,
                string|array $totalApprovedOvertime,

        ): array {
                // Validate inputs
                if ($salary <= 0) {
                        throw new InvalidArgumentException("Salary must be greater than 0.");
                }

                if ($workingDays <= 0) {
                        throw new InvalidArgumentException("Working days must be greater than 0.");
                }

                if ($dailyHours <= 0) {
                        throw new InvalidArgumentException("Daily hours must be greater than 0.");
                }

                if ($monthDays <= 0) {
                        throw new InvalidArgumentException("Month days must be greater than 0.");
                }




                // Assign attributes
                $this->employeeData  = $employeeData;
                $this->salary        = $salary;
                $this->workingDays   = $workingDays;
                $this->dailyHours    = $dailyHours;
                $this->monthDays     = $monthDays;

                // Assign time-based data (convert string if needed)
                $this->totalDuration        = is_array($totalDuration) ? $totalDuration : $this->parseHours($totalDuration);
                $this->totalActualDuration  = is_array($totalActualDuration) ? $totalActualDuration : $this->parseHours($totalActualDuration);
                $this->totalOvertime        = is_array($totalApprovedOvertime) ? $totalApprovedOvertime : $this->parseHours($totalApprovedOvertime);
                $this->lateHours = $this->employeeData['late_hours']['totalHoursFloat'];
                $this->employee = $employee;
                // Extract stats
                $this->extractAttendanceStats();

                // Calculate base rates

                switch ($this->dailyRateMethod) {
                        case DailyRateMethod::By30Days->value:
                                $this->dailyRate = $this->salary / 30;
                                break;
                        case DailyRateMethod::ByMonthDays->value:
                                $this->dailyRate = $this->salary / $this->monthDays;
                                break;
                        default:
                                $this->dailyRate = $this->salary / $this->workingDays;
                                break;
                }


                $this->hourlyRate = $this->dailyRate / $this->dailyHours;

                // Deductions
                $this->absenceDeduction = $this->absentDays * $this->dailyRate;
                $this->lateDeduction = $this->lateHours * $this->hourlyRate;

                // Overtime
                $this->overtimeHours  = $this->convertToHours($this->totalOvertime);
                $this->overtimeAmount = $this->overtimeHours * $this->hourlyRate * self::OVERTIME_MULTIPLIER;

                // Salaries
                $this->baseSalary = $this->salary;
                $this->netSalary  = $this->baseSalary + $this->overtimeAmount
                        - $this->absenceDeduction;

                return $this->buildResult();
        }

        /**
         * Extract attendance statistics
         */
        protected function extractAttendanceStats(): void
        {
                $stats = $this->employeeData['statistics'] ?? [];

                $this->presentDays = (int) ($stats['present_days'] ?? 0);
                $this->absentDays  = (int) ($stats['absent'] ?? 0);
        }

        /**
         * Parse time string (HH:MM:SS) into [hours, minutes]
         */
        protected function parseHours(string $timeString): array
        {
                $parts = explode(':', $timeString);
                return [
                        'hours'   => (int) ($parts[0] ?? 0),
                        'minutes' => (int) ($parts[1] ?? 0),
                ];
        }

        /**
         * Convert parsed hours array to float hours
         */
        protected function convertToHours(array $time): float
        {
                return $time['hours'] + ($time['minutes'] / 60);
        }

        /**
         * Assemble final result
         */
        protected function buildResult(): array
        {
                return [
                        'base_salary'            => round($this->baseSalary),
                        'absence_deduction'      => round($this->absenceDeduction),
                        'overtime_amount'        => round($this->overtimeAmount),
                        'net_salary'             => round($this->netSalary),
                        'gross_salary'           => round($this->baseSalary + $this->overtimeAmount),
                        'is_negative'            => $this->netSalary < 0,

                        'daily_rate'             => round($this->dailyRate),
                        'hourly_rate'            => round($this->hourlyRate, 2),
                        'overtime_hours'         => round($this->overtimeHours, 2),

                        'month_days'             => $this->monthDays,
                        'working_days'           => $this->workingDays,
                        'daily_hours'            => $this->dailyHours,
                        'total_duration'         => $this->totalDuration,
                        'total_actual_duration'  => $this->totalActualDuration,
                        'total_approved_overtime' => $this->totalOvertime,

                        'details'                => $this->employeeData,
                        'tax'                   => 0,
                        'late_hours' => $this->lateHours,
                        'transactions'           => $this->buildTransactions(),
                ];
        }

        protected function buildTransactions(): array
        {
                $transactions = [];

                // 1. خصم الغياب
                if ($this->absenceDeduction > 0) {
                        $transactions[] = [
                                'type'        => SalaryTransactionType::TYPE_DEDUCTION,          // النوع الرئيسي
                                'sub_type'    => SalaryTransactionSubType::ABSENCE,
                                'amount'      => round($this->absenceDeduction),
                                'operation'   => '-',
                                'description' => 'خصم غياب',
                        ];
                }

                if ($this->lateDeduction > 0) {
                        $transactions[] = [
                                'type'        => SalaryTransactionType::TYPE_DEDUCTION,          // النوع الرئيسي
                                'sub_type'    => SalaryTransactionSubType::LATE,
                                'amount'      => round($this->lateDeduction),
                                'operation'   => '-',
                                'description' => 'خصم تأخير',
                        ];
                }
                // 2. بدل الوقت الإضافي
                if ($this->overtimeAmount > 0) {
                        $transactions[] = [
                                'type'        => SalaryTransactionType::TYPE_ALLOWANCE,
                                'sub_type'    => SalaryTransactionSubType::OVERTIME,
                                'amount'      => round($this->overtimeAmount),
                                'operation'   => '+',
                                'description' => 'بدل وقت إضافي',
                        ];
                }

                // 3. الراتب الصافي
                if ($this->netSalary > 0) {
                        // $transactions[] = [
                        //         'type'        => 'net_salary',
                        //         'amount'      => round($this->netSalary),
                        //         'operation'   => '+',
                        //         'description' => 'الراتب الصافي',
                        // ];
                }
                $transactions[] = [
                        'type'        => SalaryTransactionType::TYPE_SALARY,
                        'sub_type'    => SalaryTransactionSubType::BASE_SALARY,
                        'amount'      => round($this->salary),
                        'operation'   => '+',
                        'description' => 'الراتب المقرر',
                ];

                return $transactions;
        }

        /**
         * Calculate the yearly tax deduction for an employee.
         *
         * @param Employee $employee The employee instance.
         * @return float The yearly tax deduction amount.
         */
        protected function calculateYearlyTax(Employee $employee)
        {
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
                dd($generalDeducationTypes, $generalDeducationTypes);
                // dd($generalDeducationTypes);
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

                dd($deduction);
        }
}
