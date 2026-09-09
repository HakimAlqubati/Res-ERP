<?php

declare(strict_types=1);

namespace App\Modules\HR\Payroll\Calculators;

use App\Models\AdvanceRequest;
use App\Models\EmployeeAdvanceInstallment;
use App\Modules\HR\Payroll\DTOs\CalculationContext;
use Carbon\Carbon;

/**
 * حساب أقساط السلف المستحقة
 */
class AdvanceInstallmentCalculator
{
    public const DEFAULT_ROUND_SCALE = 2;

    public function __construct(
        protected int $roundScale = self::DEFAULT_ROUND_SCALE
    ) {}

    /**
     * حساب أقساط السلف للموظف في الشهر المحدد
     */
    public function calculate(CalculationContext $context): array
    {
        $advanceItems = [];
        $advanceInstallmentsTotal = 0.0;

        if (!$context->periodYear || !$context->periodMonth) {
            return [
                'items' => $advanceItems,
                'total' => $advanceInstallmentsTotal,
            ];
        }

        // حدود الشهر الكاملة — الأقساط مجدولة شهرياً ومستقلة عن الفرع
        // نستخدم بداية ونهاية الشهر الكاملة دائماً حتى لا تضيع الأقساط عند الانتقال بين الفروع
        $start = sprintf('%04d-%02d-01', $context->periodYear, $context->periodMonth);
        $end   = date('Y-m-t', strtotime($start)); // نهاية الشهر الفعلية دائماً

        // نسبة أيام هذا الـ segment من إجمالي أيام الشهر (للتوزيع النسبي عند multi-segment)
        $ratio = $this->calculateSegmentRatio($context);

        // جلب أقساط الموظف المجدولة وغير المسددة ضمن الشهر
        // نستبعد الأقساط المؤجلة (skipped) أو الملغاة (cancelled)
        $rows = EmployeeAdvanceInstallment::query()
            ->where('employee_id', $context->employee->id)
            ->where('is_paid', false)
            ->where('status', EmployeeAdvanceInstallment::STATUS_SCHEDULED)
            ->whereBetween('due_date', [$start, $end])
            ->with(['application:id,employee_id'])
            ->get([
                'id',
                'application_id',
                'sequence',
                'installment_amount',
                'due_date',
                'status',
            ]);

        if ($rows->isEmpty()) {
            return [
                'items' => $advanceItems,
                'total' => $advanceInstallmentsTotal,
            ];
        }

        // جلب أكواد السلف المرتبطة عبر application_id
        $applicationIds = $rows->pluck('application_id')->filter()->unique()->values()->all();

        $codesByApp = [];
        if (!empty($applicationIds)) {
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

            $fullAmount = (float) $r->installment_amount;
            if ($fullAmount <= 0) {
                continue;
            }

            // توزيع مبلغ القسط نسبياً حسب أيام الفترة (segment)
            $amount = $this->round($fullAmount * $ratio);
            if ($amount <= 0) {
                continue;
            }

            $advanceItems[] = [
                'installment_id' => (int) $r->id,
                'application_id' => (int) $r->application_id,
                'advance_request_id' => $meta['advance_request_id'],
                'sequence' => (int) $r->sequence,
                'months' => (int) $meta['months'],
                'amount' => $amount,
                'due_date' => $r->due_date,
                'code' => $meta['code'],
            ];

            $advanceInstallmentsTotal += $amount;
        }

        return [
            'items' => $advanceItems,
            'total' => $this->round($advanceInstallmentsTotal),
        ];
    }

    /**
     * حساب نسبة أيام الفترة الحالية (segment) من إجمالي أيام الشهر.
     * إذا كانت الفترة تغطي الشهر كاملاً، ترجع 1.0.
     */
    protected function calculateSegmentRatio(CalculationContext $context): float
    {
        $monthStart = sprintf('%04d-%02d-01', $context->periodYear, $context->periodMonth);
        $monthEnd   = date('Y-m-t', strtotime($monthStart));
        $totalDays  = (int) date('t', strtotime($monthStart));

        $segStart = $context->periodStartDate ?? $monthStart;
        $segEnd   = $context->periodEndDate   ?? $monthEnd;

        // إذا كانت الفترة تغطي الشهر كاملاً — لا حاجة للتوزيع
        if ($segStart <= $monthStart && $segEnd >= $monthEnd) {
            return 1.0;
        }

        $segDays = (int) Carbon::parse($segStart)->diffInDays(Carbon::parse($segEnd)) + 1;

        return min(1.0, max(0.0, $segDays / $totalDays));
    }

    protected function round(float $value): float
    {
        return round($value, $this->roundScale);
    }
}
