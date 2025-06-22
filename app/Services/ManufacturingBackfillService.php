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
            // تحقق من صحة المخزن
            if ($storeId && !Store::whereKey($storeId)->exists()) {
                throw new \InvalidArgumentException("Store with ID {$storeId} does not exist.");
            }

            // نفذ المحاكاة
            $simulatedTransactions = $this->simulateBackfill($storeId);

            // اجمع كل transactionable_id المرتبطة لتفادي تكرار أو تداخل في الحركات القديمة
            $stockSupplyOrderIds = collect($simulatedTransactions)->pluck('transactionable_id')->unique();

            // حذف أي حركات OUT سابقة من نوع StockSupplyOrder مرتبطة بنفس الأوامر
            InventoryTransaction::where('movement_type', InventoryTransaction::MOVEMENT_OUT)
                ->where('transactionable_type', StockSupplyOrder::class)
                ->whereIn('transactionable_id', $stockSupplyOrderIds)
                ->withTrashed()
                ->forceDelete();

            // إنشاء الحركات الجديدة
            foreach ($simulatedTransactions as $data) {
                InventoryTransaction::create($data);
            }

            Log::info('handleFromSimulation completed', [
                'transactions_created' => count($simulatedTransactions),
                'store_id' => $storeId,
            ]);
        });
    }


    public function simulateBackfill(?int $storeId)
    {
        if ($storeId && !Store::whereKey($storeId)->exists()) {
            throw new \InvalidArgumentException("Store with ID {$storeId} does not exist.");
        }

        $transactions = InventoryTransaction::query()
            ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
            ->where('transactionable_type', StockSupplyOrder::class)
            ->whereHas('product', function ($query) {
                $query->has('productItems');
            })
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->get();

        return $transactions->flatMap(function ($transaction) use ($storeId) {
            $components = ProductItemCalculatorService::calculateComponents(
                $transaction->product_id,
                $transaction->quantity
            );

            $supplyOrderId = $transaction->transactionable_id;
            $movementDate = $transaction->transaction_date;
            $result = [];

            foreach ($components as $component) {
                $packageSize = \App\Models\UnitPrice::where('product_id', $component['product_id'])
                    ->where('unit_id', $component['unit_id'])
                    ->value('package_size');

                if (!$packageSize || $packageSize == 0) {
                    continue; // تفادي القسمة على صفر
                }

                // تحضير الداتا
                $component['package_size'] = $packageSize;

                try {
                    $fifoAllocations = $this->allocateFifoOutTransactionsForRawMaterial(
                        $component,
                        $storeId,
                        $movementDate,
                        $supplyOrderId
                    );

                    $result = array_merge($result, $fifoAllocations);
                } catch (\RuntimeException $e) {
                    // لو ما في كمية كافية، تجاهل أو احفظ الخطأ لو حبيت
                    Log::warning("FIFO Allocation failed for product {$component['product_id']}: " . $e->getMessage());
                }
            }

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
        int $stockSupplyOrderId
    ): array {
        $requiredQty = $component['quantity_after_waste'];
        $productId = $component['product_id'];
        $unitId = $component['unit_id'];

        $pricePerUnit = $component['price_per_unit'];
        $unitName = $component['unit_name'];

        $inTransactions = $this->getRawMaterialInTransactions($productId, $storeId);

        $allocated = [];
        foreach ($inTransactions as $in) {
            $totalInQty = $in->quantity * $in->package_size;

            $consumed = InventoryTransaction::where('source_transaction_id', $in->id)
                ->where('movement_type', InventoryTransaction::MOVEMENT_OUT)
                ->sum(DB::raw('quantity * package_size'));

            $remaining = round($totalInQty - $consumed, 4);

            if ($remaining <= 0) {
                continue;
            }

            $take = min($requiredQty, $remaining); // ❗️لا تأخذ أكثر من المتبقي

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
                'notes' => "FIFO OUT for '{$component['product_name']}' - SupplyOrder #$stockSupplyOrderId",
            ];

            $requiredQty = round($requiredQty - $take, 4); // ❗️حدث الكمية المتبقية بدقة

            if ($requiredQty <= 0) {
                break;
            }
        }


        if ($requiredQty > 0) {
            throw new \RuntimeException("Insufficient stock for product ID: $productId. Needed: $component[quantity_after_waste], Remaining: $requiredQty");
        }

        return $allocated;
    }
}
