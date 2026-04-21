<?php

declare(strict_types=1);

namespace App\Modules\HR\Payroll\Calculators;

use App\Modules\HR\Payroll\DTOs\CalculationContext;

/**
 * حساب الحوافز / المكافآت الشهرية للموظف
 */
class MonthlyIncentiveCalculator
{
    public const DEFAULT_ROUND_SCALE = 2;

    public function __construct(
        protected int $roundScale = self::DEFAULT_ROUND_SCALE
    ) {}

    /**
     * حساب المكافآت المخصصة للموظف
     */
    public function calculate(CalculationContext $context): array
    {
        $incentiveItems = [];
        $totalAmount = 0.0;

        // جلب المكافآت المخصصة للموظف مع تفاصيل نوع المكافأة
        // العلاقة في Employee: messages() -> return $this->hasMany(EmployeeMonthlyIncentive::class);
        $employeeIncentives = $context->employee->monthlyIncentives()
            ->with('monthlyIncentive:id,name,active')
            ->get();

        foreach ($employeeIncentives as $empIncentive) {
            $incentiveType = $empIncentive->monthlyIncentive;

            // تحقق من وجود نوع الحافز وتفعيله
            if (!$incentiveType || !$incentiveType->active) {
                continue;
            }

            $amount = (float) $empIncentive->amount;

            if ($amount <= 0) {
                continue;
            }

            $incentiveItems[] = [
                'id'            => $incentiveType->id, // ID of MonthlyIncentive (Type)
                'employee_incentive_id' => $empIncentive->id, // ID of the assignment record
                'name'          => $incentiveType->name,
                'amount'        => $this->round($amount),
                'type'          => 'monthly_incentive',
            ];

            $totalAmount += $amount;
        } 
        // جلب المكافآت المعتمدة (Employee Reward System)
        $approvedRewards = $context->employee->rewards()
            ->where('status', \App\Models\EmployeeReward::STATUS_APPROVED)
            ->where('month', $context->periodMonth)
            ->where('year', $context->periodYear)
            ->with('rewardType:id,name')
            ->get();

        foreach ($approvedRewards as $reward) {
            $amount = (float) $reward->reward_amount;

            if ($amount <= 0) {
                continue;
            }

            $incentiveItems[] = [
                'id'            => $reward->incentive_id,
                'employee_reward_id' => $reward->id,
                'name'          => ($reward->rewardType->name ?? 'Reward') . ' (Special)',
                'amount'        => $this->round($amount),
                'type'          => 'special_reward',
            ];

            $totalAmount += $amount;
        }

        return [
            'items' => $incentiveItems,
            'total' => $this->round($totalAmount),
        ];
    }

    protected function round(float $value): float
    {
        return round($value, $this->roundScale);
    }
}
