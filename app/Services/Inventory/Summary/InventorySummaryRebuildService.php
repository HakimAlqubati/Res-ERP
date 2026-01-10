<?php

namespace App\Services\Inventory\Summary;

use App\Models\InventorySummary;
use App\Models\Product;
use App\Models\Store;
use App\Services\Inventory\Optimized\OptimizedInventoryService;
use Illuminate\Support\Facades\DB;

/**
 * InventorySummaryRebuildService
 * 
 * إعادة بناء جدول inventory_summary
 */
class InventorySummaryRebuildService
{
    /**
     * الخطوة 1: توليد صفوف فارغة لكل (منتج × وحدة × مخزن)
     */
    public function generateEmptyRows(?callable $onProgress = null): int
    {
        InventorySummary::truncate();

        $stores = Store::pluck('id')->toArray();
        $count = 0;
        $batchData = [];

        $products = Product::whereHas('unitPrices')
            ->where('type', '!=', Product::TYPE_FINISHED_POS)
            ->with('unitPrices:id,product_id,unit_id,package_size')
            ->select('id')
            ->get();

        if ($onProgress) {
            $total = 0;
            $storesCount = count($stores);
            foreach ($products as $p) {
                $total += $p->unitPrices->count() * $storesCount;
            }
            $onProgress('start', $total);
        }

        foreach ($products as $product) {
            foreach ($product->unitPrices as $unitPrice) {
                foreach ($stores as $storeId) {
                    $batchData[] = [
                        'store_id' => $storeId,
                        'product_id' => $product->id,
                        'unit_id' => $unitPrice->unit_id,
                        'package_size' => $unitPrice->package_size ?? 1,
                        'remaining_qty' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $count++;

                    if ($onProgress) {
                        $onProgress('advance');
                    }

                    if (count($batchData) >= 1000) {
                        DB::table('inventory_summary')->insert($batchData);
                        $batchData = [];
                    }
                }
            }
        }

        if ($onProgress) {
            $onProgress('finish');
        }

        return $count;
    }

    /**
     * الخطوة 2: حساب الكميات
     */
    public function calculateQuantities(?callable $onProgress = null): int
    {
        $updated = 0;

        $summaries = InventorySummary::select('id', 'store_id', 'product_id', 'unit_id')->get();

        if ($onProgress) {
            $onProgress('start', $summaries->count());
        }

        foreach ($summaries as $summary) {
            $remainingQty = OptimizedInventoryService::getRemainingQty(
                $summary->product_id,
                $summary->unit_id,
                $summary->store_id
            );

            if ($remainingQty != 0) {
                InventorySummary::where('id', $summary->id)->update([
                    'remaining_qty' => $remainingQty,
                ]);
                $updated++;
            }

            if ($onProgress) {
                $onProgress('advance');
            }
        }

        if ($onProgress) {
            $onProgress('finish');
        }

        return $updated;
    }

    /**
     * إعادة بناء كامل
     */
    public function rebuildAll(?callable $onProgress = null): array
    {
        $generated = $this->generateEmptyRows($onProgress);
        $calculated = $this->calculateQuantities($onProgress);

        return [
            'generated' => $generated,
            'calculated' => $calculated,
        ];
    }

    /**
     * إعادة بناء لمنتج/مخزن محدد
     */
    public function rebuildForStore(int $storeId, int $productId): void
    {
        $product = Product::with('unitPrices:id,product_id,unit_id,package_size')->find($productId);
        if (!$product) return;

        foreach ($product->unitPrices as $unitPrice) {
            $summary = InventorySummary::getOrCreate(
                $storeId,
                $productId,
                $unitPrice->unit_id,
                $unitPrice->package_size ?? 1
            );

            $remainingQty = OptimizedInventoryService::getRemainingQty(
                $productId,
                $unitPrice->unit_id,
                $storeId
            );

            $summary->setQty($remainingQty);
        }
    }

    /**
     * إعادة بناء لمخزن محدد
     */
    public function rebuildForStoreOnly(int $storeId, ?callable $onProgress = null): int
    {
        $products = Product::whereHas('unitPrices')
            ->where('type', '!=', Product::TYPE_FINISHED_POS)
            ->pluck('id')
            ->toArray();

        if ($onProgress) {
            $onProgress('start', count($products));
        }

        $count = 0;
        foreach ($products as $productId) {
            $this->rebuildForStore($storeId, $productId);
            $count++;

            if ($onProgress) {
                $onProgress('advance');
            }
        }

        if ($onProgress) {
            $onProgress('finish');
        }

        return $count;
    }
}
