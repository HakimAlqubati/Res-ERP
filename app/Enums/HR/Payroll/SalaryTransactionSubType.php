<?php

namespace App\Enums\HR\Payroll;

enum SalaryTransactionSubType: string
{
    case BASE_SALARY = 'base_salary';       // الراتب الأساسي

        // خصومات (Deduction)
    case ABSENCE = 'absence';               // خصم غياب
    case LATE = 'late';                     // خصم تأخير
    case LOAN = 'loan';                     // خصم سلفة
    case MISSING_HOURS = 'missing_hours';
    case EARLY_DEPARTURE_HOURS = 'early_departure_hours';

        // أقساط السلف (Installments)
    case ADVANCE_INSTALLMENT = 'advance_installment';             // قسط سلفة (الشهر الحالي)
    case EARLY_ADVANCE_INSTALLMENT = 'early_advance_installment'; // قسط سلفة مبكر (شهر قادم)

        // إضافات / علاوات (Allowance)
    case HOUSING = 'housing_allowance';     // بدل سكن
    case TRANSPORT = 'transport_allowance'; // بدل مواصلات
    case OVERTIME = 'overtime';             // عمل إضافي
    case OVERTIME_DAYS = 'overtime_days';   // أيام عمل إضافية (رصيد إجازات)

        // مكافآت (Bonus)
    case ANNUAL = 'annual_bonus';           // مكافأة سنوية
    case PERFORMANCE = 'performance_bonus'; // مكافأة أداء
    case SALARY_CORRECTION = 'salary_correction'; // تعديل / تسوية راتب

        // ترحيل (Carry Forward)
    case CARRY_FORWARD = 'carry_forward';           // مبلغ مرحّل على الموظف

    public function parentType(): SalaryTransactionType
    {
        return match ($this) {
            self::BASE_SALARY   => SalaryTransactionType::TYPE_SALARY,

            self::ABSENCE,
            self::LATE,
            self::LOAN,
            self::MISSING_HOURS,
            self::EARLY_DEPARTURE_HOURS,
            self::ADVANCE_INSTALLMENT,
            self::EARLY_ADVANCE_INSTALLMENT => SalaryTransactionType::TYPE_DEDUCTION,

            self::HOUSING,
            self::TRANSPORT,
            self::OVERTIME,
            self::OVERTIME_DAYS => SalaryTransactionType::TYPE_ALLOWANCE,

            self::ANNUAL,
            self::PERFORMANCE   => SalaryTransactionType::TYPE_BONUS,

            self::SALARY_CORRECTION => SalaryTransactionType::TYPE_ADJUSTMENT,

            self::CARRY_FORWARD => SalaryTransactionType::TYPE_CARRY_FORWARD,
        };
    }
}
