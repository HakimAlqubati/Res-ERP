<?php

namespace App\Enums;

class FinancialCategoryCode
{
    // Existing codes
    const TRANSFERS = 'transfers';
    const SALES = 'sales';
    const DIRECT_PURCHASE = 'direct_purchase';
    const CLOSING_STOCK = 'closing_stock';

    // Payroll related codes
    const PAYROLL_SALARIES = 'payroll_salaries';     // صافي الرواتب
    const PAYROLL_ADVANCES = 'payroll_advances';     // صرف السلف
    const PAYROLL_TAX = 'payroll_tax';               // الضرائب المحتجزة
    const PAYROLL_INSURANCE = 'payroll_insurance';   // التأمينات (SOCSO/EPF)

    // Maintenance related codes
    const MAINTENANCE_REPAIR = 'maintenance_repair';
    const EQUIPMENT_PURCHASE = 'equipment_purchase';

    public static function getOptions(): array
    {
        return [
            self::TRANSFERS => 'Transfers',
            self::SALES => 'Sales',
            self::CLOSING_STOCK => 'Closing Stock',
            self::DIRECT_PURCHASE => 'Direct Purchase',
        ];
    }

    /**
     * Get payroll category codes
     */
    public static function getPayrollCodes(): array
    {
        return [
            self::PAYROLL_SALARIES,
            self::PAYROLL_ADVANCES,
            self::PAYROLL_TAX,
            self::PAYROLL_INSURANCE,
        ];
    }

    /**
     * Get maintenance category codes
     */
    public static function getMaintenanceCodes(): array
    {
        return [
            self::MAINTENANCE_REPAIR,
            self::EQUIPMENT_PURCHASE,
        ];
    }
}
