<?php

namespace App\Enums\HR\Payroll  ;

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
}
