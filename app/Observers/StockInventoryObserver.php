<?php

namespace App\Observers;

use App\Models\StockInventory;
use App\Services\Financial\ClosingStockCalculationService;
use Illuminate\Support\Facades\Log;

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
            Log::info("StockInventory #{$stockInventory->id} finalized. Creating closing stock transaction...");

            $transaction = $this->calculationService->createClosingStockTransaction($stockInventory);

            if ($transaction) {
                Log::info("Closing stock transaction created successfully: #{$transaction->id}");
            } else {
                Log::warning("Failed to create closing stock transaction for StockInventory #{$stockInventory->id}");
            }
        }
    }
}
