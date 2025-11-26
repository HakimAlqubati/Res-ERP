<?php

namespace App\Enums;

class FinancialCategoryCode
{
    const TRANSFERS = 'transfers';
    const SALES = 'sales';
    const DIRECT_PURCHASE = 'direct_purchase';

    public static function getOptions(): array
    {
        return [
            self::TRANSFERS => 'Transfers',
            self::SALES => 'Sales Revenue',
            // self::DIRECT_PURCHASE => 'Direct Purchase',
        ];
    }
}
