<?php

namespace App\Imports;

use Throwable;
use App\Models\Product;
use App\Models\ProductItem;
use App\Models\Unit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;

class ProductItemsQuantityImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        // تجاهل أول صف (رؤوس الأعمدة)
        $rows->shift();

        DB::beginTransaction();

        try {
            foreach ($rows as $row) {
                $parentProductCode = trim($row[0]);
                $itemProductCode = trim($row[2]);
                if (strlen($itemProductCode) === 4) {
                    $itemProductCode = '0' . $itemProductCode;
                    Log::alert('itemProductCode', [$itemProductCode]);
                }
                $unitName = trim($row[4]);
                $quantity = floatval($row[5]);

                $parentProduct = Product::where('code', $parentProductCode)->first();
                $itemProduct = Product::where('code', $itemProductCode)->first();
                $unit = Unit::where('name', $unitName)->first();

                if (!$parentProduct || !$itemProduct || !$unit) {
                    // سجل مفقود → لا نقوم بالتعديل
                    Log::warning("خطأ في السطر: تعذر العثور على منتج/مكون/وحدة", [
                        'parent_code' => $parentProductCode,
                        'item_code' => $itemProductCode,
                        'unit' => $unitName,
                    ]);
                    continue;
                }

                $productItem = ProductItem::where([
                    'parent_product_id' => $parentProduct->id,
                    'product_id' => $itemProduct->id,
                    'unit_id' => $unit->id,
                ])->first();

                if ($productItem) {
                    $productItem->quantity = $quantity;
                    $productItem->save();
                } else {
                    Log::info("لم يتم تعديل السطر لأنه لا يوجد سجل مطابق", [
                        'parent_code' => $parentProductCode,
                        'item_code' => $itemProductCode,
                        'unit' => $unitName,
                    ]);
                }
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error("فشل في تعديل كميات المكونات من ملف الإكسل", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // يمكنك رفع الاستثناء إن أردت إعلام المستخدم في الواجهة
            throw $e;
        }
    }
}
