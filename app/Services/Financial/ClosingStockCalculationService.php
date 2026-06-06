<?php

namespace App\Services\Financial;

use App\Enums\FinancialCategoryCode;
use App\Models\Branch;
use App\Models\FinancialCategory;
use App\Models\FinancialTransaction;
use App\Models\StockInventory;
use Carbon\Carbon;
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
     * Return per-product breakdown: product name, physical qty, unit price, total value.
     */
    public function getDetailedClosingStockValues(StockInventory $inventory): array
    {
        $inventory->loadMissing('details.product');

        $reportService = new PurchaseInvoiceProductSummaryReportService();
        $rows = [];

        foreach ($inventory->details as $detail) {
            $physicalQty = (float) $detail->physical_quantity;
            $productId   = $detail->product_id;
            $latestPrice = $reportService->getLatestPurchasePrice($productId);

            if ($latestPrice && $latestPrice->package_size > 0) {
                $unitPrice = $latestPrice->price / $latestPrice->package_size;
            } else {
                $unitPrice = 0;
            }

            $totalValue = $physicalQty * $unitPrice;

            $rows[] = [
                'product_id'   => $productId,
                'product_name' => $detail->product->name ?? '—',
                'unit_name'    => optional($detail->unit)->name ?? '—',
                'package_size' => $latestPrice->package_size ?? 0,
                'physical_qty' => $physicalQty,
                'unit_price'   => $unitPrice,
                'total_value'  => $totalValue,
            ];
        }

        // Sort by product name
        usort($rows, fn($a, $b) => strcmp($a['product_name'], $b['product_name']));

        return $rows;
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
     * Single entry point called by the Observer.
     * Performs one valuation pass and creates both Closing and Opening transactions.
     */
    public function createStockValueTransactions(StockInventory $inventory): void
    {
        $amount = $this->calculateClosingStockValue($inventory);

        $this->createClosingStockTransaction($inventory, $amount);
        $this->createOpeningStockTransaction($inventory, $amount);
    }

    /**
     * Record "Closing Stock" — reduces COGS for the current period (credit / income).
     * Dated on the inventory date itself.
     * Keyed on reference_type + reference_id + category_id to prevent duplicates.
     */
    public function createClosingStockTransaction(StockInventory $inventory, ?float $amount = null): ?FinancialTransaction
    {
        try {
            $amount   ??= $this->calculateClosingStockValue($inventory);
            $category   = FinancialCategory::findByCode(FinancialCategoryCode::CLOSING_STOCK);
            $branchId   = $this->resolveBranchId($inventory);
            $date       = Carbon::parse($inventory->inventory_date);
            if (! $category) {
                return null;
            }

            return FinancialTransaction::updateOrCreate(
                [
                    'reference_type' => StockInventory::class,
                    'reference_id'   => $inventory->id,
                    'category_id'    => $category->id,
                ],
                [
                    'branch_id'        => $branchId,
                    'amount'           => $amount,
                    'type'             => FinancialCategory::TYPE_INCOME,
                    'transaction_date' => $date->toDateString(),
                    'status'           => FinancialTransaction::STATUS_PAID,
                    'description'      => "Closing Stock — Inventory #{$inventory->id} — Store: " . ($inventory->store->name ?? 'N/A'),
                    'created_by'       => auth()->id() ?? $inventory->created_by,
                    'month'            => $date->month,
                    'year'             => $date->year,
                ]
            );
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Record "Opening Stock" — adds to COGS for the next period (debit / expense).
     * Always dated on the 1st of the following month (not +1 day).
     * Keyed on reference_type + reference_id + category_id to prevent duplicates.
     */
    public function createOpeningStockTransaction(StockInventory $inventory, float $amount): ?FinancialTransaction
    {
        try {
            $category = FinancialCategory::findByCode(FinancialCategoryCode::OPENING_STOCK);
            $branchId = $this->resolveBranchId($inventory);
            $date     = Carbon::parse($inventory->inventory_date)->startOfMonth()->addMonth();
            if (! $category) {
                return null;
            }

            return FinancialTransaction::updateOrCreate(
                [
                    'reference_type' => StockInventory::class,
                    'reference_id'   => $inventory->id,
                    'category_id'    => $category->id,
                ],
                [
                    'branch_id'        => $branchId,
                    'amount'           => $amount,
                    'type'             => FinancialCategory::TYPE_EXPENSE,
                    'transaction_date' => $date->toDateString(),
                    'status'           => FinancialTransaction::STATUS_PAID,
                    'description'      => "Opening Stock — Inventory #{$inventory->id} — Store: " . ($inventory->store->name ?? 'N/A'),
                    'created_by'       => auth()->id() ?? $inventory->created_by,
                    'month'            => $date->month,
                    'year'             => $date->year,
                ]
            );
        } catch (\Exception) {
            return null;
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function resolveBranchId(StockInventory $inventory): ?int
    {
        return Branch::where('store_id', $inventory->store_id)->value('id');
    }
}
