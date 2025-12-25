<?php

namespace App\Observers;

use App\Models\StockInventory;
use App\Services\Financial\ClosingStockCalculationService;


class StockInventoryObserver
{
    protected ClosingStockCalculationService $calculationService;

    public function __construct(ClosingStockCalculationService $calculationService)
    {
        $this->calculationService = $calculationService;
    }

    /**
     * Handle the StockInventory "updated" event.
     */
    public function updated(StockInventory $stockInventory): void
    {
        // Check if 'finalized' was changed from false to true
        if ($stockInventory->wasChanged('finalized') && $stockInventory->finalized === true) {
            $transaction = $this->calculationService->createClosingStockTransaction($stockInventory);
        }
    }
}
