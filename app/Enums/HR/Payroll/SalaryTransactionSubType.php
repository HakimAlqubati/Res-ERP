<?php

namespace App\Enums\HR\Payroll;

enum SalaryTransactionSubType: string
{
    case BASE_SALARY = 'base_salary';       // الراتب الأساسي
        // خصومات (Deduction)
    case ABSENCE = 'absence';           // خصم غياب
    case LATE = 'late';                 // خصم تأخير
    case LOAN = 'loan';                 // خصم سلفة
    case MISSING_HOURS = 'missing_hours';
        // إضافات / علاوات (Allowance)
    case HOUSING = 'housing_allowance'; // بدل سكن
    case TRANSPORT = 'transport_allowance'; // بدل مواصلات
    case OVERTIME = 'overtime';       // عمل إضافي

        // مكافآت (Bonus)
    case ANNUAL = 'annual_bonus';       // مكافأة سنوية
    case PERFORMANCE = 'performance_bonus'; // مكافأة أداء
    case SALARY_CORRECTION = 'salary_correction'; // تعديل / تسوية راتب

    public function parentType(): SalaryTransactionType
    {
        return match ($this) {
            self::BASE_SALARY   => SalaryTransactionType::TYPE_SALARY,
            self::ABSENCE,
            self::LATE,
            self::LOAN,
            self::MISSING_HOURS => SalaryTransactionType::TYPE_DEDUCTION,
            self::HOUSING,
            self::TRANSPORT,
            self::OVERTIME      => SalaryTransactionType::TYPE_ALLOWANCE,
            self::ANNUAL,
            self::PERFORMANCE   => SalaryTransactionType::TYPE_ALLOWANCE,
            
            self::SALARY_CORRECTION => SalaryTransactionType::TYPE_ADJUSTMENT,
        };
    }
}
