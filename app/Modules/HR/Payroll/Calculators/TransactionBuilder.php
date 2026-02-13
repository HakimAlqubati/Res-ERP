<?php

declare(strict_types=1);

namespace App\Modules\HR\Payroll\Calculators;

use App\Enums\HR\Payroll\SalaryTransactionSubType;
use App\Enums\HR\Payroll\SalaryTransactionType;
use App\Models\Deduction;
use App\Models\EmployeeAdvanceInstallment;
use App\Models\EmployeeMealRequest;
use App\Models\PenaltyDeduction;
use App\Modules\HR\Payroll\DTOs\CalculationContext;
use App\Modules\HR\Payroll\DTOs\DeductionResult;
use Illuminate\Support\Str;

/**
 * بناء مصفوفة الحركات المالية (Transactions)
 */
class TransactionBuilder
{
    public const DEFAULT_ROUND_SCALE = 2;

    public function __construct(
        protected int $roundScale = self::DEFAULT_ROUND_SCALE
    ) {}

    /**
     * بناء جميع الحركات المالية
     */
    public function build(
        CalculationContext $context,
        DeductionResult $deductions,
        array $overtime,
        array $allowances,
        array $penalties,
        array $advanceInstallments,
        array $mealRequests,
        array $dynamicDeductions,
        array $monthlyIncentives = [],
        float $overtimeMultiplier = 1.5,
        array $policyHookTransactions = []
    ): array {
        $tx = [];
        $rates = $context->rates;

        // 1. الراتب الأساسي
        $tx[] = [
            'type'        => SalaryTransactionType::TYPE_SALARY,
            'sub_type'    => SalaryTransactionSubType::BASE_SALARY,
            'amount'      => $this->round($context->salary),
            'operation'   => '+',
            'description' => 'Base salary',
            'unit'        => 'day',
            'qty'         => $context->workingDays,
            'rate'        => $this->round($rates->dailyRate),
            'multiplier'  => 1.0,
        ];

        // 2. الأوفرتايم
        if (($overtime['amount'] ?? 0) > 0) {
            $tx[] = [
                'type'        => SalaryTransactionType::TYPE_ALLOWANCE,
                'sub_type'    => SalaryTransactionSubType::OVERTIME,
                'amount'      => $this->round($overtime['amount']),
                'operation'   => '+',
                'description' => 'Approved overtime',
                'unit'        => 'hour',
                'qty'         => $this->round($overtime['hours']),
                'rate'        => $this->round($rates->hourlyRate, 4),
                'multiplier'  => $overtimeMultiplier,
            ];
        }

        // 3. البدلات
        foreach ($allowances['items'] ?? [] as $a) {
            $tx[] = [
                'type'        => SalaryTransactionType::TYPE_ALLOWANCE,
                'sub_type'    => Str::slug($a['name']),
                'amount'      => $this->round($a['amount']),
                'operation'   => '+',
                'description' => $a['name'],
                'unit'        => null,
                'qty'         => null,
                'rate'        => null,
                'multiplier'  => null,
            ];
        }

        // 4. خصم الغياب
        if ($deductions->absenceDeduction > 0) {
            $tx[] = [
                'type'        => SalaryTransactionType::TYPE_DEDUCTION,
                'sub_type'    => SalaryTransactionSubType::ABSENCE,
                'amount'      => $this->round($deductions->absenceDeduction),
                'operation'   => '-',
                'description' => 'Absence deduction',
                'unit'        => 'day',
                'qty'         => $deductions->absentDays,
                'rate'        => $this->round($rates->dailyRate),
                'multiplier'  => 1.0,
            ];
        }

        // 5. خصم الساعات الناقصة
        if ($deductions->missingHoursDeduction > 0) {
            $tx[] = [
                'type'        => SalaryTransactionType::TYPE_DEDUCTION,
                'sub_type'    => SalaryTransactionSubType::MISSING_HOURS,
                'amount'      => $this->round($deductions->missingHoursDeduction),
                'operation'   => '-',
                'description' => 'Missing hours deduction',
                'unit'        => 'hour',
                'qty'         => $deductions->missingHours,
                'rate'        => $this->round($rates->hourlyRate),
                'multiplier'  => 1.0,
            ];
        }

        // 6. خصم المغادرة المبكرة
        if ($deductions->earlyDepartureDeduction > 0) {
            $tx[] = [
                'type'        => SalaryTransactionType::TYPE_DEDUCTION,
                'sub_type'    => SalaryTransactionSubType::EARLY_DEPARTURE_HOURS,
                'amount'      => $this->round($deductions->earlyDepartureDeduction),
                'operation'   => '-',
                'description' => 'Early departure deduction',
                'unit'        => 'hour',
                'qty'         => $this->round($deductions->earlyDepartureHours),
                'rate'        => $this->round($rates->hourlyRate),
                'multiplier'  => 1.0,
            ];
        }

        // 7. خصم التأخير
        if ($deductions->lateDeduction > 0) {
            $tx[] = [
                'type'        => SalaryTransactionType::TYPE_DEDUCTION,
                'sub_type'    => SalaryTransactionSubType::LATE,
                'amount'      => $this->round($deductions->lateDeduction),
                'operation'   => '-',
                'description' => 'Late deduction',
                'unit'        => 'hour',
                'qty'         => $this->round($deductions->lateHours),
                'rate'        => $this->round($rates->hourlyRate, 4),
                'multiplier'  => 1.0,
            ];
        }

        // 8. حركات من Policy Hooks
        foreach ($policyHookTransactions as $t) {
            if (!isset($t['type'], $t['amount'], $t['operation'])) {
                continue;
            }
            $t['amount'] = $this->round((float)$t['amount']);
            $t['unit']       = $t['unit']       ?? null;
            $t['qty']        = isset($t['qty']) ? (float)$t['qty'] : null;
            $t['rate']       = isset($t['rate']) ? (float)$t['rate'] : null;
            $t['multiplier'] = isset($t['multiplier']) ? (float)$t['multiplier'] : null;
            $tx[] = $t;
        }

        // 9. مساهمات صاحب العمل
        foreach ($dynamicDeductions as $key => $ded) {
            if ($key === 'result') continue;
            $employerAmount = (float)($ded['employer_deduction_amount'] ?? 0);
            if ($employerAmount <= 0) continue;

            $tx[] = [
                'type'         => SalaryTransactionType::TYPE_EMPLOYER_CONTRIBUTION,
                'sub_type'     => $ded['name'] ?? SalaryTransactionType::TYPE_EMPLOYER_CONTRIBUTION,
                'amount'       => $this->round($employerAmount),
                'operation'    => null,
                'description'  => $ded['name'] . ' (employer contribution)' ?? 'Employer contribution',
                'deduction_id' => $ded['id'] ?? null,
            ];
        }

        // 10. الخصومات العامة
        foreach ($dynamicDeductions as $key => $ded) {
            if ($key === 'result') continue;
            $amount = (float)($ded['deduction_amount'] ?? 0);
            if ($amount <= 0) continue;

            $tx[] = [
                'type'         => SalaryTransactionType::TYPE_DEDUCTION,
                'sub_type'     => Str::slug($ded['name']),
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

        // 11. خصومات الجزاءات
        foreach ($penalties['items'] ?? [] as $pen) {
            $tx[] = [
                'type'         => SalaryTransactionType::TYPE_DEDUCTION,
                'sub_type'     => Str::slug($pen['deduction_name'] ?? 'penalty'),
                'amount'       => $this->round((float)$pen['amount']),
                'operation'    => '-',
                'description'  => $pen['deduction_name'] ?? ($pen['description'] ?? 'Penalty deduction'),
                'reference_type' => $pen['reference_type'] ?? PenaltyDeduction::class,
                'reference_id'   => $pen['reference_id'] ?? $pen['id'] ?? null,
                'deduction_id'   => $pen['deduction_id'] ?? null,
            ];
        }

        // 12. أقساط السلف
        foreach ($advanceInstallments['items'] ?? [] as $adv) {
            $descParts = [];
            if (!empty($adv['code'])) {
                $descParts[] = $adv['code'];
            }
            if (!empty($adv['sequence']) && !empty($adv['months'])) {
                $descParts[] = "installment {$adv['sequence']}/{$adv['months']}";
            }
            if (!empty($adv['due_date'])) {
                $descParts[] = 'due ' . \Carbon\Carbon::parse($adv['due_date'])->format('Y-m-d');
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
                'reference_type' => EmployeeAdvanceInstallment::class,
                'reference_id'   => $adv['installment_id'] ?? null,
                'application_id'     => $adv['application_id'] ?? null,
                'advance_request_id' => $adv['advance_request_id'] ?? null,
            ];
        }

        // 13. خصومات الوجبات
        foreach ($mealRequests['items'] ?? [] as $mr) {
            $tx[] = [
                'type'           => SalaryTransactionType::TYPE_DEDUCTION,
                'sub_type'       => 'meal-deduction',
                'amount'         => $this->round((float)$mr['amount']),
                'operation'      => '-',
                'description'    => $mr['meal_details'] ?? 'Meal deduction',
                'reference_type' => EmployeeMealRequest::class,
                'reference_id'   => $mr['id'],
                'date'           => $mr['date'],
            ];
        }

        // 14. المكافآت الشهرية / الحوافز
        foreach ($monthlyIncentives['items'] ?? [] as $bonus) {
            $tx[] = [
                'type'         => SalaryTransactionType::TYPE_BONUS,
                'sub_type'     => SalaryTransactionSubType::PERFORMANCE->value,
                'amount'       => $this->round((float)$bonus['amount']),
                'operation'    => '+',
                'description'  => $bonus['name'] ?? 'Incentive',
                'reference_type' => \App\Models\EmployeeMonthlyIncentive::class,
                'reference_id'   => $bonus['employee_incentive_id'] ?? null,
            ];
        }

        return $tx;
    }

    protected function round(float $value, int $scale = null): float
    {
        return round($value, $scale ?? $this->roundScale);
    }
}
