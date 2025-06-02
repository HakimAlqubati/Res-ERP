<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\Product;

class WrongStoreProductReportService
{
    /**
     * ترجع تقرير المنتجات التي دخلت مخازن غير خاصة بها.
     *
     * @return array
     */
    public function getReport($movementType): array
    {

        if(is_null($movementType)){
            $movementType = 'in';
        }
        // بداية الاستعلام
        $query = InventoryTransaction::with(['product', 'store'])
            ->where('movement_type', $movementType);

        // إضافة الشروط الإضافية حسب نوع الحركة
        if ($movementType === 'in') {
            $query->whereNotIn('transactionable_type', ['App\\Models\\Order']);
        } elseif ($movementType === 'out') {
            $query->whereNotIn('transactionable_type', ['App\\Models\\StockSupplyOrder']);
        }

        // تنفيذ الاستعلام
        $transactions = $query->get();

        $report = [];

        foreach ($transactions as $transaction) {
            $product = $transaction->product;
            if (!$product) {
                continue;
            }

            $defaultStore = defaultManufacturingStore($product);

            if ($defaultStore && $transaction->store_id != $defaultStore->id) {
                $report[] = [
                    'product_id' => $product->id,
                    'product_code' => $product->code,
                    'product_name' => $product->name,
                    'actual_store' => $transaction->store->name ?? 'N/A',
                    'expected_store' => $defaultStore->name,
                    'movement_date' => $transaction->movement_date,
                    'quantity' => $transaction->quantity,
                    'notes' => $transaction->notes,
                    'transactionable_id' => $transaction->transactionable_id,
                    'transactionable_type' => $transaction->transactionable_type,
                ];
            }
        }

        return $report;
    }
}
