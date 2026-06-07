<?php

namespace App\Modules\Stock\Reports\Enums;

/**
 * Filter enum for invoice linkage status.
 */
enum FilterInvoiceLinkStatus: string
{
    case ALL = 'all';
    case WITH_INVOICE = 'with_invoice';
    case WITHOUT_INVOICE = 'without_invoice';

    public function label(): string
    {
        return match($this) {
            self::ALL => 'All',
            self::WITH_INVOICE => 'With Invoice',
            self::WITHOUT_INVOICE => 'Without Invoice',
        };
    }
}
