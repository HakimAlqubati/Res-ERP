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
            self::ALL => 'All Invoices',
            self::WITH_INVOICE => 'Linked to Invoice',
            self::WITHOUT_INVOICE => 'Not Linked',
        };
    }
}
