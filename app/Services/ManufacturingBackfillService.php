<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\ProductItem;
use App\Models\StockSupplyOrder;
use App\Models\Store;
use App\Traits\Inventory\InventoryStaticMethods;
use Illuminate\Support\Facades\DB;
use App\Services\ProductItemCalculatorService;
use Filament\Notifications\Collection;
use Illuminate\Support\Facades\Log;

class ManufacturingBackfillService
{
    use InventoryStaticMethods;

    /**
     * Process previous IN transactions for manufactured products
     * and create corresponding OUT transactions for raw materials.
     */
    public function handleFromSimulation(?int $storeId): void
    {
        Log::info('Starting handleFromSimulation...', ['timestamp' => now()]);

        DB::transaction(function () use ($storeId) {
            if ($storeId && !Store::whereKey($storeId)->exists()) {
                throw new \InvalidArgumentException("Store with ID {$storeId} does not exist.");
            }

            $simulatedTransactions = $this->simulateBackfill($storeId);

            $stockSupplyOrderIds = collect($simulatedTransactions)->pluck('transactionable_id')->unique();

            InventoryTransaction::where('movement_type', InventoryTransaction::MOVEMENT_OUT)
                ->where('transactionable_type', StockSupplyOrder::class)
                ->whereIn('transactionable_id', $stockSupplyOrderIds)
                ->withTrashed()
                ->forceDelete();

            foreach ($simulatedTransactions as $data) {
                InventoryTransaction::create($data);
            }

            Log::info('handleFromSimulation completed', [
                'transactions_created' => count($simulatedTransactions),
                'store_id' => $storeId,
            ]);
        });
    }



    public function simulateBackfill(
        ?int $storeId,
        $productId = null
    ) {
        if ($storeId && !Store::whereKey($storeId)->exists()) {
            throw new \InvalidArgumentException("Store with ID {$storeId} does not exist.");
        }

        $globalAllocatedPerSource = [];

        $transactions = InventoryTransaction::query()
            ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
            ->where('transactionable_type', StockSupplyOrder::class)
            ->whereHas('product', function ($query) use ($productId) {
                $query->has('productItems');
                if ($productId) {
                    $query->where('product_id', $productId);
                }
            })
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->get();


        return $transactions->flatMap(function ($transaction) use ($storeId, &$globalAllocatedPerSource) {
            $components = ProductItemCalculatorService::calculateComponents(
                $transaction->product_id,
                $transaction->quantity
            );

            $supplyOrderId = $transaction->transactionable_id;
            $movementDate = $transaction->transaction_date;
            $result = [];
            $componentCostSummary = [];
            foreach ($components as $component) {
                $productKey = $component['product_id'];

                if (!isset($globalAllocatedPerSource[$productKey])) {
                    $globalAllocatedPerSource[$productKey] = [];
                }

                $packageSize = \App\Models\UnitPrice::where('product_id', $component['product_id'])
                    ->where('unit_id', $component['unit_id'])
                    ->value('package_size');

                if (!$packageSize || $packageSize == 0) {
                    continue;
                }

                $component['package_size'] = $packageSize;

                try {
                    $fifoAllocations = $this->allocateFifoOutTransactionsForRawMaterial(
                        $component,
                        $storeId,
                        $movementDate,
                        $supplyOrderId,
                        $globalAllocatedPerSource[$productKey],
                        $transaction->product->name
                    );

                    $result = array_merge($result, $fifoAllocations);
                    foreach ($fifoAllocations as $allocation) {
                        $key = $allocation['product_id'];

                        if (!isset($componentCostSummary[$key])) {
                            $componentCostSummary[$key] = [
                                'total_cost' => 0,
                                'total_qty' => 0,
                                'latest_date' => null,
                            ];
                        }

                        $cost = $allocation['quantity'] * $allocation['price'];
                        $componentCostSummary[$key]['total_cost'] += $cost;
                        $componentCostSummary[$key]['total_qty'] += $allocation['quantity'];
                        $componentCostSummary[$key]['latest_date'] = $allocation['transaction_date'];
                    }
                } catch (\RuntimeException $e) {
                    Log::warning("FIFO Allocation failed for product {$component['product_id']}: " . $e->getMessage());
                }
            }
            $this->updateCompositeProductPriceBasedOnAllocations(
                $transaction->product_id,
                $transaction->store_id,
                $componentCostSummary
            );

            return $result;
        });
    }


    public function getRawMaterialInTransactions(int $productId, int $storeId): \Illuminate\Support\Collection
    {
        return \App\Models\InventoryTransaction::query()
            ->where('movement_type', \App\Models\InventoryTransaction::MOVEMENT_IN)
            ->where('transactionable_type', \App\Models\Order::class)
            ->where('product_id', $productId)
            ->where('store_id', $storeId)
            ->whereNull('deleted_at')
            ->orderBy('transaction_date') // FIFO
            ->orderBy('id')               // لحل التعادل في التواريخ
            ->get();
    }

    public function getAllRawMaterialInTransactionsByStore(int $storeId): array
    {
        $rawMaterials = \App\Models\Product::whereHas('usedInProducts')->with('unitPrices.unit')->get();

        $result = [];

        foreach ($rawMaterials as $product) {
            $transactions = $this->getRawMaterialInTransactions($product->id, $storeId);

            $batches = $transactions->map(function ($txn) {
                return [
                    'transaction_id' => $txn->id,
                    'transaction_date' => $txn->transaction_date,
                    'transactionable_id' => $txn->transactionable_id,
                    'transactionable_type' => $txn->transactionable_type,
                    'store_id' => $txn->store_id,
                    'quantity' => $txn->quantity,
                    'package_size' => $txn->package_size,
                    'unit_id' => $txn->unit_id,
                    'price' => $txn->price,
                    'total_qty' => $txn->quantity * $txn->package_size,
                    'product_id' => $txn->product_id,
                ];
            });

            if (count($batches) <= 0) {
                continue;
            }
            $result[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'batches' => $batches,
            ];
        }

        return $result;
    }

    public function allocateFifoOutTransactionsForRawMaterial(
        array $component,
        int $storeId,
        string $movementDate,
        int $stockSupplyOrderId,
        array &$allocatedPerSource,
        string $compositeProductName
    ): array {
        $requiredQty = $component['quantity_after_waste'];
        $productId = $component['product_id'];
        $unitId = $component['unit_id'];
        $pricePerUnit = $component['price_per_unit'];

        $inTransactions = $this->getRawMaterialInTransactions($productId, $storeId);
        $totalBatches = count($inTransactions);
        $allocated = [];
        foreach ($inTransactions as $index => $in) {
            $isLastBatch = ($index === $totalBatches - 1);
            $totalInQty = $in->quantity * $in->package_size;

            $alreadyConsumed = InventoryTransaction::where('source_transaction_id', $in->id)
                ->where('movement_type', InventoryTransaction::MOVEMENT_OUT)
                ->sum(DB::raw('quantity * package_size'));

            $tempConsumed = $allocatedPerSource[$in->id] ?? 0;
            $consumed = $alreadyConsumed + $tempConsumed;
            $remaining = round($totalInQty - $consumed, 4);

            if ($remaining <= 0 && !$isLastBatch) {
                continue;
            }
            $take = min($requiredQty, $remaining > 0 ? $remaining : $requiredQty);
            $allocatedPerSource[$in->id] = $tempConsumed + $take;

            if ($take <= 0) {
                continue;
            }

            $quantityInUnits = $take / $in->package_size;

            $allocated[] = [
                'movement_type' => InventoryTransaction::MOVEMENT_OUT,
                'product_id' => $productId,
                'unit_id' => $unitId,
                'quantity' => round($quantityInUnits, 4),
                'package_size' => $in->package_size,
                'price' => $pricePerUnit,
                'transaction_date' => $movementDate,
                'movement_date' => $movementDate,
                'store_id' => $storeId,
                'transactionable_type' => StockSupplyOrder::class,
                'transactionable_id' => $stockSupplyOrderId,
                'source_transaction_id' => $in->id,
                'notes' => "FIFO OUT for raw '{$component['product_name']}' used in '{$compositeProductName}' - SupplyOrder #$stockSupplyOrderId",

            ];

            $requiredQty = round($requiredQty - $take, 4);

            if ($requiredQty <= 0) {
                break;
            }
        }

        if ($requiredQty > 0) {
            throw new \RuntimeException("Insufficient stock for product ID: $productId. Needed: $component[quantity_after_waste], Remaining: $requiredQty");
        }

        return $allocated;
    }

    protected function updateCompositeProductPriceBasedOnAllocations(int $productId, int $storeId, array $componentCostSummary): void
    {
        $product = Product::with('productItems')->findOrFail($productId);

        $total = 0;

        foreach ($product->productItems as $item) {
            $componentId = $item->component_product_id;
            $unitId = $item->unit_id;

            if (!isset($componentCostSummary[$componentId])) {
                continue;
            }

            $summary = $componentCostSummary[$componentId];

            if ($summary['total_qty'] <= 0) {
                continue;
            }

            $avgPrice = round($summary['total_cost'] / $summary['total_qty'], 4);

            // تحديث السعر في product_items
            $item->update([
                'price_per_unit' => $avgPrice,
            ]);

            // تحديث السعر في unit_prices
            $unitPrice = \App\Models\UnitPrice::where('product_id', $componentId)
                ->where('unit_id', $unitId)
                ->first();

            if ($unitPrice) {
                $unitPrice->update([
                    'price' => $avgPrice,
                    'date' => $summary['latest_date'],
                    'notes' => 'Updated from actual used IN transactions during manufacturing',
                ]);
            }

            $total += $item->quantity * $avgPrice;
        }

        // تحديث سعر المنتج المركب
        $product->update([
            'unit_price' => round($total, 4),
        ]);
    }
}