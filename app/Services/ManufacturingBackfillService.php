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
        Log::info('Starting_ManufacturingBackfillService...', ['timestamp' => now()]);
        DB::transaction(function () use ($storeId) {
            // تحقق من صلاحية معرف المخزن
            if ($storeId && !Store::whereKey($storeId)->exists()) {
                throw new \InvalidArgumentException("Store with ID {$storeId} does not exist.");
            }

            // نفذ المحاكاة للحصول على الحركات التي سيتم إنشاؤها
            $simulatedTransactions = $this->simulateBackfill($storeId);

            // 🟡 قبل إضافة الحركات، احذف الحركات OUT الحالية التي ستكون بديلة
            $transactionableIds = collect($simulatedTransactions)->pluck('source_transaction_id')->unique();

            InventoryTransaction::where('movement_type', InventoryTransaction::MOVEMENT_OUT)
                ->where('transactionable_type', StockSupplyOrder::class)
                ->whereIn('transactionable_id', $transactionableIds)
                ->withTrashed()
                ->forceDelete();

            // احفظ كل سجل كـ InventoryTransaction فعلي
            foreach ($simulatedTransactions as $data) {
                InventoryTransaction::create([
                    'movement_type'        => InventoryTransaction::MOVEMENT_OUT,
                    'product_id'           => $data['product_id'],
                    'quantity'             => $data['quantity'],
                    'unit_id'              => $data['unit_id'],
                    'package_size'         => $data['package_size'],
                    'store_id'             => $data['store_id'],
                    'movement_date'        => $data['movement_date'],
                    'transaction_date'     => $data['transaction_date'],
                    'notes'                => $data['notes'],
                    'source_transaction_id' => $data['source_transaction_id'],
                    'price'                => $data['price'],
                    'transactionable_type' => StockSupplyOrder::class,
                    'transactionable_id'   => $data['source_transaction_id'], // أو التعديل إذا لزم
                ]);
            }
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
            });


        $transactions->where('store_id', $storeId);

        $transactions = $transactions->get();
        return $transactions->flatMap(function ($transaction) {
            $components = ProductItemCalculatorService::calculateComponents($transaction->product_id, $transaction->quantity);

            $compositeProductName = $transaction->product->name;
            $notes = "Auto OUT for manufacturing of '{$compositeProductName}' from Supply Order #{$transaction->transactionable_id}";

            return collect($components)->map(function ($component) use ($transaction, $notes) {
                $packageSize = \App\Models\UnitPrice::where('product_id', $component['product_id'])
                    ->where('unit_id', $component['unit_id'])
                    ->value('package_size');
                return [
                    'movement_type' => InventoryTransaction::MOVEMENT_OUT,
                    'product_id' => $component['product_id'],
                    'product_name' => $component['product_name'],
                    'quantity' => $component['quantity_after_waste'],
                    'unit_id' => $component['unit_id'],
                    'unit_name' => $component['unit_name'],
                    'store_id' => $transaction->store_id,
                    'movement_date' => $transaction->movement_date,
                    'transaction_date' => $transaction->transaction_date,
                    'notes' => $notes,
                    'package_size' => $packageSize,
                    // 'source_transaction_id' => InventoryTransaction::query()
                    //     ->where('product_id', $component['product_id'])
                    //     ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
                    //     ->where('store_id', $transaction->store_id)
                    //     ->orderByDesc('movement_date')
                    //     ->orderByDesc('id')
                    //     ->value('id'),
                    'source_transaction_id' => $transaction->transactionable_id,
                    'price' => $component['price_per_unit'],
                    'total_price' => $component['total_price'],
                ];
            });
        });
    }
}
