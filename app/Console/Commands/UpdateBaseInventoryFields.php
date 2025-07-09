<?php
namespace App\Console\Commands;

use App\Models\InventoryTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateBaseInventoryFields extends Command
{
    protected $signature   = 'inventory:update-base-fields';
    protected $description = 'Update base_quantity, base_unit_id, and base_unit_package_size for existing inventory_transactions';

    public function handle()
    {
        $this->info('🔄 Updating inventory_transactions with base unit info...');

        DB::beginTransaction();

        try {
            $updatedCount = 0;

            InventoryTransaction::
                // whereNull('base_quantity')->
                where('product_id', 116)
                ->chunkById(100, function ($transactions) use (&$updatedCount) {
                    foreach ($transactions as $transaction) {
                        $productId = $transaction->product_id;
                        $unitId    = $transaction->unit_id;
                        $quantity  = $transaction->quantity;

                        $product = $transaction->product;
                        if (is_null($productId) || is_null($unitId) || is_null($quantity)) {
                            continue;
                        }

                        $currentUnitPrice = $product->supplyOutUnitPrices()
                            ->where('unit_id', $transaction->unit_id)
                            ->first();

                        // 2. جلب أصغر وحدة مرتبطة بالمنتج من unit_prices (package_size الأصغر)
                        $baseUnitPrice = $product->supplyOutUnitPrices()
                            ->orderBy('package_size', 'asc')
                            ->first();

                        if (
                            ! $currentUnitPrice || ! $baseUnitPrice ||
                            $currentUnitPrice->package_size == 0 || $baseUnitPrice->package_size == 0
                        ) {
                            continue;
                        }

                        $conversionRate = $currentUnitPrice->package_size / $baseUnitPrice->package_size;
                        $baseQuantity   = $quantity * $conversionRate;

                        $baseQuantity = round($baseQuantity, 1);

                        $pricePerBaseUnit = null;
                        if (
                            is_numeric($transaction->price) &&
                            $transaction->price > 0 &&
                            $currentUnitPrice->package_size > 0
                        ) {
                            $pricePerBaseUnit = round(
                                $transaction->price / $currentUnitPrice->package_size,
                                6
                            );
                        }

                        $transaction->update([
                            'base_unit_id'           => $baseUnitPrice->unit_id,
                            'base_unit_package_size' => $baseUnitPrice->package_size,
                            'base_quantity'          => $baseQuantity,
                            'price_per_base_unit'    => $pricePerBaseUnit,
                        ]);

                        $updatedCount++;
                    }
                });

            DB::commit();

            $this->info("✅ Done. Updated $updatedCount transactions.");
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('❌ Failed to update transactions: ' . $e->getMessage());
        }
    }
}