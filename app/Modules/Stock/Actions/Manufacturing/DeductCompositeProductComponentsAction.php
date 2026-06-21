<?php

declare(strict_types=1);

namespace App\Modules\Stock\Actions\Manufacturing;

use App\Models\InventoryTransaction;
use App\Models\ProductItem;
use App\Models\StockSupplyOrder;
use App\Models\UnitPrice;
use App\Services\FifoMethodService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class DeductCompositeProductComponentsAction
{
    public function execute(int $supplyOrderId): void
    {
        Log::info("start DeductCompositeProductComponentsAction: execute - supplyOrderId: {$supplyOrderId}");
        $order = StockSupplyOrder::with(['details'])->findOrFail($supplyOrderId);
        Log::info('order fetched ');
        // DB::transaction(function () use ($order) {
        // 1. تحسين الأداء (1): جلب جميع المكونات باستعلام واحد فقط!
        $productIds = $order->details->pluck('product_id')->unique()->toArray();

        $allComponents = ProductItem::whereIn('parent_product_id', $productIds)
            ->get()
            ->groupBy('parent_product_id');

        $outboundTransactions = [];
        $now = now();

        foreach ($order->details as $detail) {
            // جلب المكونات من المجموعة المحملة مسبقاً (بدون استعلام جديد للداتا بيز)
            $components = $allComponents->get($detail->product_id);

            if (! $components || $components->isEmpty()) {
                continue;
            }

            $hasPriceChanged = false;
            foreach ($components as $component) {
                // حساب الكمية مع الهدر
                $totalQtyToDeduct = $this->calculateRequiredQuantity(
                    (float) $component->quantity,
                    (float) $detail->quantity,
                    (float) ($component->qty_waste_percentage ?? 0)
                );

                $fifoService = new FifoMethodService($order);
                $allocations = $fifoService->getAllocateFifo(
                    $component->product_id,
                    $component->unit_id,
                    $totalQtyToDeduct,
                    $order->store_id
                );
                

                // 🟢 [التعديل 2]: مقارنة سعر الباتش المسحوب وتحديث الوصفة (ProductItem)
                if (!empty($allocations)) {
                    $lastAllocation = end($allocations);
                    $sourcePrice = (float) $lastAllocation['price_based_on_unit'];

                    if (round((float) $component->price, 6) !== round($sourcePrice, 6)) {
                        $component->price = $sourcePrice;
                        $component->total_price = $sourcePrice * (float) $component->quantity;
                        $component->total_price_after_waste = ProductItem::calculateTotalPriceAfterWaste(
                            $component->total_price,
                            (float) ($component->qty_waste_percentage ?? 0)
                        );
                        $component->save(); 
                        $hasPriceChanged = true; 
                    }
                }

                // 2. تحسين الأداء (2): تجميع البيانات للإدخال المجمع بدلاً من الإدخال الفردي
                $this->collectOutboundTransactions(
                    $outboundTransactions,
                    $order,
                    $detail->product_id,
                    $component,
                    $allocations,
                    $now
                );
            }
            // 🟢 [التعديل 3]: تحديث السعر العام للمنتج المركب (UnitPrice) فقط إذا تغيرت مكوناته
            if ($hasPriceChanged) {
                $newCompositeCost = (float) $components->sum('total_price_after_waste');
                
                // يتم تحديث الأسعار العامة فقط، دون المساس بالحركات المخزنية!
                $this->updateGlobalPrice($detail, $newCompositeCost);
            }   
        }

        // 3. تحسين الأداء (3): تنفيذ عملية إدخال واحدة (Bulk Insert) لكل الحركات!
        if (! empty($outboundTransactions)) {
            // تقسيم المصفوفة إلى دفعات (Chunks) إذا كانت ضخمة جداً لحماية الذاكرة
            foreach (array_chunk($outboundTransactions, 500) as $chunk) {
                InventoryTransaction::insert($chunk);
            }
        }
        // });
    }

    private function calculateRequiredQuantity(float $recipeQty, float $producedQty, float $wastePercentage): float
    {
        $baseRequiredQty = $recipeQty * $producedQty;

        return $baseRequiredQty * (1 + ($wastePercentage / 100));
    }

    private function collectOutboundTransactions(
        array &$transactionsArray,
        StockSupplyOrder $order,
        int $compositeProductId,
        ProductItem $component,
        array $allocations,
        $now
    ): void {
        $movementDate = $order->order_date ?? $now;

        foreach ($allocations as $alloc) {
            $transactionsArray[] = [
                'product_id' => $component->product_id,
                'movement_type' => InventoryTransaction::MOVEMENT_OUT,
                'quantity' => $alloc['deducted_qty'],
                'base_quantity' => $alloc['deducted_qty'] * ($alloc['target_unit_package_size'] ?? 1),
                'unit_id' => $alloc['target_unit_id'],
                'price' => $alloc['price_based_on_unit'],
                'package_size' => $alloc['target_unit_package_size'],
                'movement_date' => $movementDate,
                'transaction_date' => $now,
                'store_id' => $alloc['store_id'],
                'notes' => "Manufacturing deduction for Composite Product #{$compositeProductId} in Order #{$order->id}",
                'transactionable_id' => $order->id,
                'transactionable_type' => StockSupplyOrder::class,
                'source_transaction_id' => $alloc['transaction_id'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
    }

    // --- دالة مبسطة لتحديث السعر العام للمنتج المركب مباشرة ---
    private function updateGlobalPrice($detail, float $newCost): void
    {
        $unitPrices = UnitPrice::where('product_id', $detail->product_id)->get();
        
        foreach ($unitPrices as $unitPrice) {
            $packageSize = $unitPrice->package_size ?: 1;
            $finalPrice = round($packageSize * $newCost, 2);

            // مقارنة السعر الحالي بالتكلفة الجديدة وتحديثه إذا اختلف فقط
            if (round((float) $unitPrice->price, 2) !== $finalPrice) {
                $unitPrice->price = $finalPrice;
                $unitPrice->notes = "Updated price for Composite Product #{$detail->product->name} in Supply Order #{$detail->stock_supply_order_id}";
                $unitPrice->save();
            }
        }
    }
}
