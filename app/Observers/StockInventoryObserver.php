<?php

namespace App\Observers;

use App\Models\StockInventory;
use App\Services\Financial\ClosingStockCalculationService;


class StockInventoryObserver
{
    protected ClosingStockCalculationService $calculationService;
    protected \App\Services\Inventory\StockAdjustment\StockAdjustmentService $adjustmentService;

    public function __construct(
        ClosingStockCalculationService $calculationService,
        \App\Services\Inventory\StockAdjustment\StockAdjustmentService $adjustmentService
    ) {
        $this->calculationService = $calculationService;
        $this->adjustmentService = $adjustmentService;
    }

    /**
     * Handle the StockInventory "updated" event.
     */
    public function updated(StockInventory $stockInventory): void
    {
        // Check if 'finalized' was changed from false to true
        if ($stockInventory->wasChanged('finalized') && $stockInventory->finalized == true) {
            // 1. Create Financial Transaction
            $this->calculationService->createClosingStockTransaction($stockInventory);

            // 2. Generate Stock Adjustments for differences
            $this->adjustmentService->createFromInventory($stockInventory);
        }
    }
}
