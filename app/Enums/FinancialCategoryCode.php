<?php

namespace App\Enums;

class FinancialCategoryCode
{
    const TRANSFERS = 'transfers';
    const SALES = 'sales';
    const DIRECT_PURCHASE = 'direct_purchase';
    const CLOSING_STOCK = 'closing_stock';

    public static function getOptions(): array
    {
        return [
            self::TRANSFERS => 'Transfers',
            self::SALES => 'Sales Revenue',
            self::CLOSING_STOCK => 'Closing Stock',
            self::DIRECT_PURCHASE => 'Direct Purchase',
        ];
    }
}
