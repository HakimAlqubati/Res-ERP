<?php

namespace App\Services;

use App\Models\ProductPriceHistory;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProductSupplyPriceUpdaterService
{


    public static function updateSupplyPrice(int $productId): array
    {
        DB::beginTransaction();

        try {
            // 1️⃣ الحصول على السجل الأحدث لسعر التوريد (الذي ليس له مكون مرتبط)
            $latestPriceHistory = ProductPriceHistory::where('product_id', $productId)
                ->whereNull('product_item_id')
                ->orderByDesc('date')
                ->first();

            if (! $latestPriceHistory) {
                DB::rollBack();
                return [
                    'status' => 'error',
                    'message' => '⚠️ لم يتم العثور على سجل تاريخ سعر لهذا المنتج.',
                ];
            }

            $newPrice = $latestPriceHistory->new_price;
            $priceDate = Carbon::parse($latestPriceHistory->date);

            // 2️⃣ جلب جميع الحركات المخزنية (Supply Orders) للمنتج المركب
            $transactions = InventoryTransaction::where('product_id', $productId)
                ->where('movement_type', InventoryTransaction::MOVEMENT_IN)
                ->where('transactionable_type', 'App\\Models\\StockSupplyOrder')
                ->get();

            $updatedCount = 0;

            foreach ($transactions as $transaction) {
                $movementDate = Carbon::parse($transaction->movement_date);

                // إذا كان تاريخ الحركة >= تاريخ السعر الجديد ➜ استخدم السعر الجديد
                if ($movementDate->greaterThanOrEqualTo($priceDate)) {
                    dd('d');
                    $oldPrice = $transaction->price;
                    if ($oldPrice != $newPrice) {
                        $transaction->price = $newPrice;
                        $transaction->save();

                        $updatedCount++;
                    }
                }
            }

            DB::commit();

            return [
                'status' => 'success',
                'message' => "✅ تم تحديث سعر التوريد الجديد في {$updatedCount} حركة مخزنية.",
            ];
        } catch (\Throwable $e) {
            DB::rollBack();

            return [
                'status' => 'error',
                'message' => '❌ فشل التحديث: ' . $e->getMessage(),
            ];
        }
    }
}
