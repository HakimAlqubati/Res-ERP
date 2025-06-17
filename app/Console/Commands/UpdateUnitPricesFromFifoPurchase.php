<?php

namespace App\Console\Commands;

use App\Models\InventoryTransaction;
use App\Models\ProductPriceHistory;
use App\Models\UnitPrice;
use Illuminate\Console\Command;
use App\Models\PurchaseInvoice;

class UpdateUnitPricesFromFifoPurchase extends Command
{
    protected $signature = 'fifo:update-unit-prices {productId?}';
    protected $description = 'Update unit prices from first real purchase transaction (FIFO)';

    public function handle()
    {
        $productId = $this->argument('productId');

        if ($productId) {
            $this->updateUnitPricesFromFifoPurchase((int) $productId);
            $this->info("✅ Updated prices for product ID {$productId}");
        } else {
            $productIds = InventoryTransaction::where('movement_type', InventoryTransaction::MOVEMENT_IN)
                ->whereNotIn('transactionable_type', ['ExcelImport'])
                ->whereNull('deleted_at')
                ->distinct()
                ->pluck('product_id');

            foreach ($productIds as $id) {
                $this->updateUnitPricesFromFifoPurchase($id);
                $this->info("✅ Updated prices for product ID {$id}");
            }
        }

        return 0;
    }

    protected function updateUnitPricesFromFifoPurchase(int $productId): void
    {
        $transaction = InventoryTransaction::where('product_id', $productId)
            ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
            ->whereNotIn('transactionable_type', ['ExcelImport'])
            ->whereNull('deleted_at')
            ->orderBy('transaction_date')
            ->first();

        if (!$transaction || $transaction->package_size == 0) {
            return;
        }

        $pricePerPiece = $transaction->price / $transaction->package_size;

        $unitPrices = UnitPrice::where('product_id', $productId)->get()->keyBy('unit_id');

        foreach ($unitPrices as $unitId => $unitPrice) {
            $newPrice = $pricePerPiece * $unitPrice->package_size;

            ProductPriceHistory::create([
                'product_id'       => $productId,
                'product_item_id'  => null,
                'unit_id'          => $unitId,
                'old_price'        => $unitPrice->price,
                'new_price'        => $newPrice,
                'source_type' => $transaction->transactionable_type,
                // 'source_type' => PurchaseInvoice::class,
                'source_id'        => $transaction->transactionable_id,
                'note'             => 'Updated from FIFO purchase transaction',
                'date'             => $transaction->transaction_date,
            ]);

            $unitPrice->update([
                'price' => $newPrice,
            ]);
        }
    }
}
