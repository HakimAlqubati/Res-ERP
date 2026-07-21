<?php

declare(strict_types=1);

namespace App\Modules\Stock\Actions\Manufacturing;

use App\Models\InventoryTransaction;
use App\Models\ProductItem;
use App\Models\StockSupplyOrder;
use App\Models\StockSupplyOrderDetail;
use App\Models\UnitPrice;
use App\Services\FifoMethodService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class DeductCompositeProductComponentsAction
{

    public function executeForDetail(StockSupplyOrderDetail $detail): void
    {
        Log::info("start DeductCompositeProductComponentsAction: executeForDetail - detailId: {$detail->id}");
        
        DB::beginTransaction();
        try {
            $order = $detail->order;
            if (!$order) {
                DB::rollBack();
                return;
            }

        $components = ProductItem::with('product')
            ->where('parent_product_id', $detail->product_id)
            ->get();

        if ($components->isEmpty()) {
            DB::rollBack();
            return;
        }

        // Validate stock for this specific composite product
        app(ValidateStockForManufacturingAction::class)->execute($order, collect([$detail->product_id => $components]));

        $outboundTransactions = [];
        $now = now();

        $hasPriceChanged = false;
        $changedComponentsDetails = [];
        
        $allComponentsAllocations = [];
        
        // 🟢 1. سحب الكميات بالكامل لكل مكون بمرة واحدة (أداء عالي)
        foreach ($components as $component) {
            $totalQtyToDeduct = $this->calculateRequiredQuantity(
                (float) $component->quantity,
                (float) $detail->quantity,
                (float) ($component->qty_waste_percentage ?? 0)
            );

            if ($totalQtyToDeduct <= 0) continue;

            $allocations = (new FifoMethodService($order))->getAllocateFifo(
                (int) $component->product_id,
                (int) $component->unit_id,
                (float) $totalQtyToDeduct,
                (int) $order->store_id
            );

            $this->collectOutboundTransactions(
                $outboundTransactions,
                $order,
                $detail->product_id,
                $component,
                $allocations,
                $now
            );

            // تحديث أسعار المكونات (الوصفة) بناءً على آخر سعر سحب
            if (! empty($allocations)) {
                $lastAllocation = end($allocations);
                $sourcePrice = (float) $lastAllocation['price_based_on_unit'];

                if (round((float) $component->price, 2) !== round($sourcePrice, 2)) {
                    $oldPrice = round((float) $component->price, 2) + 0;
                    $newPrice = round($sourcePrice, 2) + 0;

                    $component->price = $sourcePrice;
                    $component->total_price = $sourcePrice * (float) $component->quantity;
                    $component->total_price_after_waste = ProductItem::calculateTotalPriceAfterWaste(
                        $component->total_price,
                        (float) ($component->qty_waste_percentage ?? 0)
                    );
                    $component->save();
                    
                    $hasPriceChanged = true;
                    $componentName = $component->product ? $component->product->name : "ID #{$component->product_id}";
                    $changeMsg = "{$componentName} ({$oldPrice} -> {$newPrice})";
                    if (!in_array($changeMsg, $changedComponentsDetails)) {
                        $changedComponentsDetails[] = $changeMsg;
                    }
                }
            }
            
            // تخزين التوزيعات في الذاكرة لتقسيمها على دفعات المنتج المركب لاحقاً
            $allComponentsAllocations[$component->id] = [
                'recipe_qty_per_unit' => $this->calculateRequiredQuantity(
                    (float) $component->quantity,
                    1.0, // الكمية المطلوبة لإنتاج 1 حبة من المنتج المركب
                    (float) ($component->qty_waste_percentage ?? 0)
                ),
                'allocations' => $allocations
            ];
        }

        // 🟢 2. تقسيم المنتج المركب إلى دفعات (حبات) واحتساب التكلفة في الذاكرة (بدون استعلامات إضافية)
        $remainingCompositeQty = (float) $detail->quantity;
        $producedBatches = [];

        while ($remainingCompositeQty > 0) {
            $currentBatchQty = min(1.0, $remainingCompositeQty);
            $currentBatchCost = 0;
            
            foreach ($allComponentsAllocations as $compId => &$compData) {
                // الكمية المطلوبة من هذا المكون لهذه الدفعة من المنتج المركب
                $neededQty = $compData['recipe_qty_per_unit'] * $currentBatchQty;
                
                foreach ($compData['allocations'] as &$alloc) {
                    if ($neededQty <= 0) break;
                    if ($alloc['deducted_qty'] <= 0) continue;
                    
                    $take = min($neededQty, $alloc['deducted_qty']);
                    $currentBatchCost += ($take * (float) $alloc['price_based_on_unit']);
                    
                    $alloc['deducted_qty'] -= $take;
                    $neededQty -= $take;
                }
            }
            
            $unitCost = $currentBatchQty > 0 ? ($currentBatchCost / $currentBatchQty) : 0;
            
            if (!empty($producedBatches) && abs(end($producedBatches)['unit_cost'] - $unitCost) < 0.001) {
                $lastIdx = count($producedBatches) - 1;
                $producedBatches[$lastIdx]['quantity'] += $currentBatchQty;
                $producedBatches[$lastIdx]['total_cost'] += $currentBatchCost;
            } else {
                $producedBatches[] = [
                    'quantity' => $currentBatchQty,
                    'unit_cost' => $unitCost,
                    'total_cost' => $currentBatchCost,
                ];
            }

            $remainingCompositeQty -= $currentBatchQty;
            if ($remainingCompositeQty < 0.0001) break;
        }

        // 🟢 3. إنشاء حركات الدخول للمنتج المركب (مقسمة لباتشات فعلية بناءً على التكلفة)
        $notes = 'Stock supply with ID ' . $detail->stock_supply_order_id;
        if (isset($order->store_id)) {
            $notes .= ' in (' . $order->store->name . ')';
        }
     
        foreach ($producedBatches as $batch) {
            InventoryTransaction::create([
                'product_id' => $detail->product_id,
                'movement_type' => InventoryTransaction::MOVEMENT_IN,
                'quantity' => $batch['quantity'],
                'unit_id' => $detail->unit_id,
                'movement_date' => $order->date ?? $now,
                'package_size' => $detail->package_size,
                'store_id' => $order->store_id,
                'price' => $batch['unit_cost'],
                'transaction_date' => $order->date ?? $now,
                'notes' => $notes,
                'transactionable_id' => $detail->stock_supply_order_id,
                'transactionable_type' => StockSupplyOrder::class,
                'waste_stock_percentage' => $detail->waste_stock_percentage,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 🟢 4. تحديث السعر العام
        if ($hasPriceChanged) {
            $newCompositeCost = (float) $components->sum('total_price_after_waste');
            $componentsChangesStr = implode(', ', $changedComponentsDetails);
            $updateNote = "Price updated due to comps: {$componentsChangesStr}";

            $this->updateGlobalPrice($detail, $newCompositeCost, $updateNote);
        }

        // 🟢 5. إدخال حركات سحب المكونات
        if (! empty($outboundTransactions)) {
            foreach (array_chunk($outboundTransactions, 500) as $chunk) {
                InventoryTransaction::insert($chunk);
            }
        }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Failed in DeductCompositeProductComponentsAction: " . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
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
                'created_at' => $now->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ];
        }
    }

    private function updateGlobalPrice($detail, float $newCost, string $updateNote): void
    {
        $unitPrices = UnitPrice::where('product_id', $detail->product_id)->get();

        foreach ($unitPrices as $unitPrice) {
            $packageSize = $unitPrice->package_size ?: 1;
            $finalPrice = round($packageSize * $newCost, 2);

            if (round((float) $unitPrice->price, 2) !== $finalPrice) {
                $unitPrice->price = $finalPrice;
                $unitPrice->notes = "{$updateNote} in Supply Order #{$detail->stock_supply_order_id}";
                $unitPrice->save();
            }
        }
    }
}
