<?php

namespace App\Services;

use App\Models\UnitPrice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BulkPricingAdjustmentService
{

    public function updateAllHistoricalPrices(int $categoryId, int $unitId, float $oldPrice, float $newPrice)
    {
        try {
            $updateReport = DB::transaction(function () use ($categoryId, $unitId, $oldPrice, $newPrice) {

                // ------------------
                // الخطوة 1: تحديد قائمة المنتجات المستهدفة بدقة
                // ------------------
                $productIdsToUpdate = UnitPrice::query()
                    ->whereHas('product', function ($q) use ($categoryId) {
                        $q->where('category_id', $categoryId);
                    })
                    ->where('unit_id', $unitId)
                    // ->where('price', $oldPrice)
                    ->pluck('product_id')
                    ->unique();

                if ($productIdsToUpdate->isEmpty()) {
                    return ['message' => 'لم يتم العثور على منتجات مطابقة للشروط. لم يتم تحديث أي شيء.'];
                }

                // ------------------
                // الخطوة 2: تحديث كل الجداول المعنية مع شرط الوحدة (unit_id)
                // ------------------
                $report = [];

                // 1. تحديث جدول الأسعار الرئيسي (Master Price Table)
                $report['unit_prices'] = UnitPrice::whereIn('product_id', $productIdsToUpdate)
                    ->where('unit_id', $unitId)
                    // ->where('price', $oldPrice)
                    ->update(['price' => $newPrice]);

                // 2. تحديث جدول حركات المخزون
                $report['inventory_transactions'] = DB::table('inventory_transactions')
                    ->whereIn('product_id', $productIdsToUpdate)
                    ->where('unit_id', $unitId) // -> تم إضافة شرط الوحدة
                    // ->where('price', $oldPrice)
                    ->update(['price' => $newPrice]);

                // 3. تحديث جدول تفاصيل الطلبات
                $report['orders_details'] = DB::table('orders_details')
                    ->whereIn('product_id', $productIdsToUpdate)
                    ->where('unit_id', $unitId) // -> تم إضافة شرط الوحدة
                    // ->where('price', $oldPrice)
                    ->update(['price' => $newPrice]);

                // 4. تحديث جدول تفاصيل مذكرات استلام البضاعة
                $report['goods_received_note_details'] = DB::table('goods_received_note_details')
                    ->whereIn('product_id', $productIdsToUpdate)
                    // ->where('unit_id', $unitId) // -> تم إضافة شرط الوحدة
                    // ->where('price', $oldPrice)
                    ->update(['price' => $newPrice]);

                // 5. تحديث جدول تفاصيل فواتير الشراء
                $report['purchase_invoice_details'] = DB::table('purchase_invoice_details')
                    ->whereIn('product_id', $productIdsToUpdate)
                    ->where('unit_id', $unitId) // -> تم إضافة شرط الوحدة
                    // ->where('price', '>=', $oldPrice - 0.001) // استخدام مقارنة مرنة للتعامل مع الفروقات العشرية
                    // ->where('price', '<=', $oldPrice + 0.001)
                    ->update(['price' => $newPrice]);

                // 6. تحديث جدول تفاصيل طلبات التوريد المخزني
                $report['stock_supply_order_details'] = DB::table('stock_supply_order_details')
                    ->whereIn('product_id', $productIdsToUpdate)
                    ->where('unit_id', $unitId) // -> تم إضافة شرط الوحدة
                    // ->where('price', '>=', $oldPrice - 0.001)
                    // ->where('price', '<=', $oldPrice + 0.001)
                    ->update(['price' => $newPrice]);


                return $report;
            });
            return $updateReport;
        } catch (\Exception $e) {
            Log::error('فشل تحديث الأسعار الشامل: ' . $e->getMessage(), ['exception' => $e]);
        }
    }
}
