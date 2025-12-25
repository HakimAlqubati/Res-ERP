<?php

namespace App\Services\Financial;

use App\Enums\FinancialCategoryCode;
use App\Models\FinancialCategory;
use App\Models\FinancialTransaction;
use App\Models\InventoryTransaction;
use App\Models\StockInventory;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;

use App\Services\PurchasedReports\PurchaseInvoiceProductSummaryReportService;

class ClosingStockCalculationService
{
    /**
     * Calculate the total value of closing stock for a StockInventory based on FIFO.
     *
     * @param StockInventory $inventory
     * @return float
     */
    public function calculateClosingStockValue(StockInventory $inventory): float
    {
        $totalValue = 0.0;

        // Load details with product
        $inventory->loadMissing('details.product');

        foreach ($inventory->details as $detail) {
            $physicalQty = (float) $detail->physical_quantity;

            if ($physicalQty <= 0) {
                continue;
            }

            $productId = $detail->product_id;
            $storeId = $inventory->store_id;

            // Calculate value for this product using Latest Purchase Price
            $productValue = $this->calculateProductValueLatestPrice($productId, $physicalQty);
            $totalValue += $productValue;
        }

        return $totalValue;
    }

    /**
     * Calculate value for a single product using Latest Purchase Price logic.
     *
     * @param int $productId
     * @param float $quantity
     * @return float
     */
    private function calculateProductValueLatestPrice(int $productId, float $quantity): float
    {
        $reportService = new PurchaseInvoiceProductSummaryReportService();
        $latestPrice = $reportService->getLatestPurchasePrice($productId);

        if ($latestPrice && $latestPrice->package_size > 0) {
            $unitPrice = $latestPrice->price / $latestPrice->package_size;
        } else {
            // Fallback if no price found: 0
            $unitPrice = 0;
        }

        return $quantity * $unitPrice;
    }

    /**
     * Create a Closing Stock financial transaction for the finalized inventory.
     *
     * @param StockInventory $inventory
     * @return ?FinancialTransaction
     */
    public function createClosingStockTransaction(StockInventory $inventory): ?FinancialTransaction
    {
        try {
            // 1. Calculate Total Value
            $amount = $this->calculateClosingStockValue($inventory);

            // 2. Get Closing Stock Category
            $category = FinancialCategory::findByCode(FinancialCategoryCode::CLOSING_STOCK);

            if (!$category) {
                return null;
            }

            // 3. Determine Branch
            // Try to find a branch associated with this store
            $branch = Branch::where('store_id', $inventory->store_id)->first();
            $branchId = $branch ? $branch->id : null;

            // 4. Create Transaction
            return FinancialTransaction::create([
                'branch_id' => $branchId,
                'category_id' => $category->id,
                'amount' => $amount,
                'type' => FinancialCategory::TYPE_INCOME, // Closing stock reduces COGS, so it's treated as income/credit
                'transaction_date' => $inventory->inventory_date,
                'status' => FinancialTransaction::STATUS_PAID, // It's an accounting entry, effectively "paid"/realized
                'description' => "Closing Stock - Inventory #{$inventory->id} - Store: " . ($inventory->store->name ?? 'N/A'),
                'created_by' => auth()->id() ?? $inventory->created_by,
                'reference_type' => StockInventory::class,
                'reference_id' => $inventory->id,
                'month' => \Carbon\Carbon::parse($inventory->inventory_date)->month,
                'year' => \Carbon\Carbon::parse($inventory->inventory_date)->year,
            ]);
        } catch (\Exception $e) {
            return null;
        }
    }
}
