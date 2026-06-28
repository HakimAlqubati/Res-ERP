<?php

namespace App\Services\Financial;

use App\Enums\FinancialCategoryCode;
use App\Models\Branch;
use App\Models\FinancialCategory;
use App\Models\FinancialTransaction;
use App\Models\StockInventory;
use Carbon\Carbon;
use App\Services\PurchasedReports\PurchaseInvoiceProductSummaryReportService;
use Illuminate\Support\Facades\DB;

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
        $detailedValues = $this->getDetailedClosingStockValues($inventory);

        return (float) array_sum(array_column($detailedValues, 'total_value'));
    }

     
    public function getDetailedClosingStockValues(StockInventory $inventory): array
    {
        $inventory->loadMissing('details.product');

         $rows = [];

        foreach ($inventory->details as $detail) {
            $physicalQty = (float) $detail->physical_quantity;
            $productId   = $detail->product_id;
            if($detail->difference <= 0) continue;

            // جلب السعر من الحركة المخزنية المرتبطة عبر التسوية (إن وجدت)
            $transaction = \Illuminate\Support\Facades\DB::table('inventory_transactions as it')
                ->join('stock_adjustment_details as sad', function ($join) {
                    $join->on('it.transactionable_id', '=', 'sad.id')
                         ->where('it.transactionable_type', '=', \App\Models\StockAdjustmentDetail::class);
                })
                ->where('sad.source_type', \App\Models\StockInventory::class)
                ->where('sad.source_id', $inventory->id)
                ->where('sad.product_id', $productId)
                ->where('sad.unit_id', $detail->unit_id)
                ->where('it.movement_type', \App\Models\InventoryTransaction::MOVEMENT_IN)
                ->select('it.price', 'it.package_size')
                ->first();

                $unitPrice = $transaction->price ?? 0;
 

            $totalValue = $physicalQty * $unitPrice;

            $rows[] = [
                'product_id'   => $productId,
                'product_code' => $detail->product->code ?? '—',
                'product_name' => $detail->product->name ?? '—',
                'unit_name'    => optional($detail->unit)->name ?? '—',
                'package_size' => $detail->package_size ?? 0,
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
    private function calculateProductValueLatestPrice(int $productId, float $quantity, $inventoryDate): float
    {
         $latestPrice = $this->getLatestPurchasePrice($productId,$inventoryDate);

        if ($latestPrice && $latestPrice->package_size > 0) {
            $unitPrice = $latestPrice->price / $latestPrice->package_size;
        } else {
            // Fallback if no price found: 0
            $unitPrice = 0;
        }

        return $quantity * $unitPrice;
    }

      public function getLatestPurchasePrice(int $productId, $inventoryDate)
    {
        $date = Carbon::parse($inventoryDate)->endOfMonth()->format('Y-m-d');
        $latestPrice = DB::table('purchase_invoice_details as pid')
            ->join('purchase_invoices as pi', 'pid.purchase_invoice_id', '=', 'pi.id')
            ->select('pid.price', 'pid.unit_id', 'pid.package_size')
            ->where('pid.product_id', $productId)
            ->whereNull('pid.deleted_at')
            ->whereDate('pi.created_at', '<=', $date)
            ->whereNull('pi.deleted_at')
            ->orderByDesc('pid.id')
            ->first();

        if ($latestPrice) {
            return $latestPrice;
        }

        // ⛔️ لم نجد سعر في الفواتير، نبحث في unit_prices
        return DB::table('unit_prices')
            ->select('price', 'unit_id', 'package_size')
            ->where('product_id', $productId)
            ->whereNull('deleted_at')
            ->orderByDesc('id') // آخر وحدة
            ->first();
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
