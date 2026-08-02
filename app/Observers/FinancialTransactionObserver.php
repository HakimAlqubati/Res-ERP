<?php

namespace App\Observers;

use App\Models\FinancialTransaction;
use Exception;

class FinancialTransactionObserver
{
    /**
     * Handle the FinancialTransaction "deleting" event.
     *
     * @param  \App\Models\FinancialTransaction  $financialTransaction
     * @return void
     * @throws \Exception
     */
    public function deleting(FinancialTransaction $financialTransaction): void
    {
        if ($financialTransaction->reference_type && $financialTransaction->reference_id) {
            // Check if the referenced model record actually exists in the database
            if ($financialTransaction->reference()->exists()) {
                // throw new Exception("Cannot delete this financial transaction because it is linked to a related record.");
            }
        }
    }
}
