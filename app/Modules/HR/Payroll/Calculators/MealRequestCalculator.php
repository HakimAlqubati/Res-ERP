<?php

declare(strict_types=1);

namespace App\Modules\HR\Payroll\Calculators;

use App\Models\EmployeeMealRequest;
use App\Modules\HR\Payroll\DTOs\CalculationContext;

/**
 * حاسب تكاليف وجبات الموظفين المعتمدة
 */
class MealRequestCalculator
{
    public const DEFAULT_ROUND_SCALE = 2;

    public function __construct(
        protected int $roundScale = self::DEFAULT_ROUND_SCALE
    ) {}

    /**
     * حساب إجمالي تكاليف وجبات الموظف المعتمدة للشهر
     */
    public function calculate(CalculationContext $context): array
    {
        $mealItems = [];
        $mealTotal = 0.0;

        if (!$context->periodYear || !$context->periodMonth) {
            return [
                'items' => $mealItems,
                'total' => $mealTotal,
            ];
        }

        // جلب طلبات الوجبات المعتمدة لهذا الموظف في الشهر/السنة المحددة
        // تاريخ الطلب يجب أن يكون ضمن الفترة المحددة
        $mealRequests = EmployeeMealRequest::query()
            ->where('employee_id', $context->employee->id)
            ->where('status', 'approved') // نفترض أن الحالة هي approved
            ->whereYear('date', $context->periodYear)
            ->whereMonth('date', $context->periodMonth)
            ->get();

        foreach ($mealRequests as $mr) {
            $cost = (float)$mr->cost;
            if ($cost <= 0) {
                continue;
            }

            $mealItems[] = [
                'id'             => $mr->id,
                'meal_details'   => $mr->meal_details ?? 'Employee Meal',
                'amount'         => $this->round($cost),
                'date'           => $mr->date,
                'reference_type' => EmployeeMealRequest::class,
                'reference_id'   => $mr->id,
            ];

            $mealTotal += $cost;
        }

        return [
            'items' => $mealItems,
            'total' => $this->round($mealTotal),
        ];
    }

    protected function round(float $value): float
    {
        return round($value, $this->roundScale);
    }
}
