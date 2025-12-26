<?php

namespace App\Services\Docs;

/**
 * Service class for Financial HR Report data.
 * Contains all data and text content for the report.
 */
class FinancialHRReportService
{
    /**
     * Get page metadata (title, description).
     */
    public function getPageMeta(): array
    {
        return [
            'title' => 'بنود الراتب والتسجيل المالي',
            'description' => 'دليل البنود التي تحتاج تسجيل في النظام المالي',
            'copyright' => '',
            'brand' => 'Workbench ERP',
        ];
    }

    /**
     * Get navigation/UI labels.
     */
    public function getLabels(): array
    {
        return [
            'backLink' => 'العودة للوحة التحكم',
            'itemColumn' => 'البند',
            'reasonColumn' => 'السبب',
            'codeColumn' => 'الكود',
            'typeColumn' => 'النوع',
            'requiredTitle' => 'البنود التي تحتاج تسجيل في النظام المالي',
            'notRequiredTitle' => 'البنود التي لا تحتاج تسجيل في النظام المالي',
            'categoriesTitle' => 'الفئات المالية للرواتب',
            'goldenRuleTitle' => 'القاعدة الذهبية',
        ];
    }

    /**
     * Get items that require financial transaction recording.
     */
    public function getRequiredItems(): array
    {
        return [
            // === صافي الراتب ===
            [
                'name' => 'صافي الرواتب',
                'reason' => 'مبلغ يخرج من خزينة الشركة ويُدفع للموظفين',
            ],

            // === السلف ===
            [
                'name' => 'صرف السلف',
                'reason' => 'مبلغ يخرج من خزينة الشركة ويُدفع للموظف',
            ],

            // === التأمينات الاجتماعية (حصة صاحب العمل) ===
            [
                'name' => 'SOCSO - حصة صاحب العمل',
                'reason' => 'مبلغ يخرج من خزينة الشركة ويُدفع لـ PERKESO',
            ],
            [
                'name' => 'EPF - حصة صاحب العمل',
                'reason' => 'مبلغ يخرج من خزينة الشركة ويُدفع لـ KWSP',
            ],
            [
                'name' => 'EIS - حصة صاحب العمل',
                'reason' => 'مبلغ يخرج من خزينة الشركة ويُدفع لـ PERKESO',
            ],

            // === الضرائب ===
            [
                'name' => 'MTD - الضريبة الشهرية',
                'reason' => 'مبلغ يخرج من خزينة الشركة ويُدفع لـ LHDN',
            ],
        ];
    }

    /**
     * Get items that do NOT require financial transaction recording.
     */
    public function getNotRequiredItems(): array
    {
        return [
            // === الاستحقاقات (مدمجة في صافي الراتب) ===
            [
                'name' => 'الراتب الأساسي',
                'reason' => 'جزء من حساب صافي الراتب - لا يُصرف منفرداً',
            ],
            [
                'name' => 'بدل السكن',
                'reason' => 'جزء من حساب صافي الراتب - لا يُصرف منفرداً',
            ],
            [
                'name' => 'بدل المواصلات',
                'reason' => 'جزء من حساب صافي الراتب - لا يُصرف منفرداً',
            ],
            [
                'name' => 'العمل الإضافي',
                'reason' => 'جزء من حساب صافي الراتب - لا يُصرف منفرداً',
            ],
            [
                'name' => 'المكافآت',
                'reason' => 'جزء من حساب صافي الراتب - لا يُصرف منفرداً',
            ],

            // === خصومات الحضور (داخلية) ===
            [
                'name' => 'خصم الغياب',
                'reason' => 'تخفيض داخلي من الراتب - لا يخرج من الشركة',
            ],
            [
                'name' => 'خصم التأخير',
                'reason' => 'تخفيض داخلي من الراتب - لا يخرج من الشركة',
            ],
            [
                'name' => 'خصم الخروج المبكر',
                'reason' => 'تخفيض داخلي من الراتب - لا يخرج من الشركة',
            ],
            [
                'name' => 'خصم الساعات الناقصة',
                'reason' => 'تخفيض داخلي من الراتب - لا يخرج من الشركة',
            ],

            // === العقوبات والجزاءات ===
            [
                'name' => 'خصم العقوبات (Penalty Deduction)',
                'reason' => 'جزاء تأديبي - تخفيض داخلي لا يخرج من الشركة',
            ],

            // === السلف وأقساطها ===
            [
                'name' => 'أقساط السلف',
                'reason' => 'استرداد لمبالغ سبق صرفها وتسجيلها مالياً',
            ],
            [
                'name' => 'أقساط السلف المبكرة',
                'reason' => 'استرداد مبكر لأقساط مستقبلية - سبق تسجيلها',
            ],

            // === التأمينات (حصة الموظف فقط) ===
            [
                'name' => 'SOCSO - حصة الموظف',
                'reason' => 'تُخصم من راتبه وتُجمع مع حصة صاحب العمل',
            ],
            [
                'name' => 'EPF - حصة الموظف',
                'reason' => 'تُخصم من راتبه وتُجمع مع حصة صاحب العمل',
            ],
            [
                'name' => 'EIS - حصة الموظف',
                'reason' => 'تُخصم من راتبه وتُجمع مع حصة صاحب العمل',
            ],
        ];
    }

    /**
     * Get financial categories needed for payroll.
     */
    public function getFinancialCategories(): array
    {
        return [
            [
                'name' => 'صافي الرواتب',
                'code' => 'payroll_salaries',
                'type' => 'مصروف',
            ],
            [
                'name' => 'صرف السلف للموظفين',
                'code' => 'payroll_advances',
                'type' => 'مصروف',
            ],
            [
                'name' => 'SOCSO - حصة صاحب العمل',
                'code' => 'employer_socso',
                'type' => 'مصروف',
            ],
            [
                'name' => 'EPF/KWSP - حصة صاحب العمل',
                'code' => 'employer_epf',
                'type' => 'مصروف',
            ],
            [
                'name' => 'EIS - حصة صاحب العمل',
                'code' => 'employer_eis',
                'type' => 'مصروف',
            ],
            [
                'name' => 'MTD/PCB - الضريبة الشهرية',
                'code' => 'payroll_tax_mtd',
                'type' => 'مصروف',
            ],
        ];
    }

    /**
     * Get the golden rule text.
     */
    public function getGoldenRule(): string
    {
        return 'يُسجَّل في النظام المالي فقط ما يمثل تدفق نقدي فعلي خارج الشركة';
    }

    /**
     * Get all report data.
     */
    public function getReportData(): array
    {
        return [
            'meta' => $this->getPageMeta(),
            'labels' => $this->getLabels(),
            'requiredItems' => $this->getRequiredItems(),
            'notRequiredItems' => $this->getNotRequiredItems(),
            'financialCategories' => $this->getFinancialCategories(),
            'goldenRule' => $this->getGoldenRule(),
        ];
    }
}
