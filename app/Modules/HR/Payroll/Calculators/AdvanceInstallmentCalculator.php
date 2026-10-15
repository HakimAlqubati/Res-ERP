<?php

declare(strict_types=1);

namespace App\Modules\HR\Payroll\Calculators;

use App\Models\AdvanceRequest;
use App\Models\EmployeeAdvanceInstallment;
use App\Models\EmployeeBranchLog;
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

        // السلف تُخصم من الراتب النهائي للموظف في الشهر بنسبة 100% كاملة، وليس بحسب الفرع.
        // إذا كان الموظف قد تنقل بين فروع، فإن الفروع السابقة لا يُخصم منها أقساط،
        // ويتم خصم القسط كاملاً (100%) من مسير الراتب النهائي (الفرع الأخير).
        if (!$this->isFinalSalarySegment($context)) {
            return [
                'items' => $advanceItems,
                'total' => $advanceInstallmentsTotal,
            ];
        }

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

            // احتساب قسط السلفة كاملاً بنسبة 100% من الراتب النهائي دون تجزئة بحسب الفرع
            $amount = $this->round($fullAmount);
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
     * التحقق مما إذا كانت هذه الفترة هي فترة الراتب النهائي للموظف في الشهر الحالي.
     * السلف لا تُجزأ بحسب الفرع، بل تُخصم 100% بالكامل من الراتب النهائي للموظف.
     */
    protected function isFinalSalarySegment(CalculationContext $context): bool
    {
        // 1. إذا تم تحديدها صراحة من سياق الاحتساب (مثل محاكي مسير الرواتب)
        if (!$context->isFinalSegment) {
            return false;
        }

        // 2. إذا لم يكن الموظف متعدداً للفروع، فهو حتماً الراتب النهائي
        if (!$context->isMultiSegment && !$this->hasMultipleBranchesInPeriod($context)) {
            return true;
        }

        // 3. كإجراء احتياطي عند تعدد الفروع: التحقق هل توجد شريحة أو سجل فرع لاحق لنفس الموظف في هذا الشهر
        if ($context->periodYear && $context->periodMonth && $context->periodEndDate && $context->employee?->id) {
            $monthEnd = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $context->periodYear, $context->periodMonth)));

            // إذا كان تاريخ نهاية الشريحة يغطي نهاية الشهر، فهو الشريحة النهائية
            if ($context->periodEndDate >= $monthEnd) {
                return true;
            }

            // فحص ما إذا كان يوجد سجل فرع آخر يبدأ بعد نهاية هذه الفترة في نفس الشهر
            $hasLaterLog = EmployeeBranchLog::query()
                ->where('employee_id', $context->employee->id)
                ->where('start_at', '>', $context->periodEndDate)
                ->where('start_at', '<=', $monthEnd . ' 23:59:59')
                ->exists();

            if ($hasLaterLog) {
                return false;
            }
        }

        return true;
    }

    /**
     * نسبة خصم السلفة للشريحة الحالية:
     * 1.0 (100%) إذا كانت شريحة الراتب النهائي، و 0.0 إذا كانت شريحة فرع سابق.
     */
    protected function calculateSegmentRatio(CalculationContext $context): float
    {
        return $this->isFinalSalarySegment($context) ? 1.0 : 0.0;
    }

    /**
     * التحقق مما إذا كان الموظف مرتبطاً بأكثر من فرع خلال فترة الراتب الحالية.
     */
    protected function hasMultipleBranchesInPeriod(CalculationContext $context): bool
    {
        // 1. إذا كان محدداً مسبقاً من مسير الرواتب/المحاكي أن الموظف متعدد الشرائح
        if ($context->isMultiSegment) {
            return true;
        }

        // 2. كإجراء احتياطي (Fallback): فحص سجلات الفروع التاريخية للموظف في قاعدة البيانات
        if (!$context->periodYear || !$context->periodMonth || !$context->employee?->id) {
            return false;
        }

        $monthStart = sprintf('%04d-%02d-01', $context->periodYear, $context->periodMonth);
        $monthEnd   = date('Y-m-t', strtotime($monthStart));

        $branchesCount = EmployeeBranchLog::query()
            ->where('employee_id', $context->employee->id)
            ->where('start_at', '<=', $monthEnd . ' 23:59:59')
            ->where(function ($q) use ($monthStart) {
                $q->whereNull('end_at')
                  ->orWhere('end_at', '>=', $monthStart . ' 00:00:00');
            })
            ->distinct('branch_id')
            ->count('branch_id');

        return $branchesCount > 1;
    }

    protected function round(float $value): float
    {
        return round($value, $this->roundScale);
    }
}
