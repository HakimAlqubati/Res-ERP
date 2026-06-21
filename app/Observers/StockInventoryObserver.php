<?php

namespace App\Observers;

use App\Models\StockInventory;
use App\Services\Financial\ClosingStockCalculationService;
use App\Validators\Inventory\StockInventoryCreationValidator;

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
     * Handle the StockInventory "creating" event.
     */
    public function creating(StockInventory $stockInventory): void
    {
        StockInventoryCreationValidator::validate($stockInventory);
    }

    /**
     * Handle the StockInventory "updated" event.
     */
    public function updated(StockInventory $stockInventory): void
    {
        // Check if 'finalized' was changed from false to true
        if ($stockInventory->wasChanged('finalized') && $stockInventory->finalized == true) {
            // 1. Create Closing + Opening Stock transactions (single valuation pass)
            $this->calculationService->createStockValueTransactions($stockInventory);

            // 2. Generate Stock Adjustments for differences
            $this->adjustmentService->createFromInventory($stockInventory);
        }
    }
}
