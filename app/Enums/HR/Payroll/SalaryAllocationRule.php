<?php

namespace App\Enums\HR\Payroll;

/**
 * قاعدة توزيع الراتب عند انتقال الموظف بين الفروع
 */
enum SalaryAllocationRule: string
{
    /**
     * التناسب: كل فرع يدفع عن أيام تواجد الموظف فيه
     */
    case PROPORTIONAL = 'proportional';

    /**
     * الفرع الأول: الفرع الذي بدأ فيه الموظف الشهر يتحمل الراتب بالكامل
     */
    case FIRST_BRANCH = 'first_branch';

    /**
     * الفرع الأخير: الفرع الذي انتهى فيه الموظف الشهر يتحمل الراتب بالكامل
     */
    case LAST_BRANCH = 'last_branch';

    public function label(): string
    {
        return match ($this) {
            self::PROPORTIONAL => __('Proportional (Split by days)'),
            self::FIRST_BRANCH => __('First Branch Assignment (Source)'),
            self::LAST_BRANCH  => __('Last Branch Assignment (Recipient)'),
        };
    }

    /**
     * الحصول على الخيارات للقوائم المنسدلة
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();
    }
}
