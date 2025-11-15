<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Models\UnitPrice;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;

class ItemSalesByClassImport implements ToCollection
{
    /**
     * يتم استدعاء هذه الدالة وتمرير كل الصفوف كـ Collection
     */
    public function collection(Collection $rows): void
    {
        $currentCategoryName    = null; // الفئة الرئيسية الحالية
        $currentSubcategoryName = null; // الفئة الفرعية الحالية

        // نحضر/ننشيء وحدة PIECE مرة واحدة فقط
        $pieceUnit = Unit::firstOrCreate(
            ['name' => 'PIECE'],
            [
                // عدّل أو أضف حقول أخرى لو عندك مثل code, short_name, etc.
                // 'short_name' => 'PC',
            ]
        );

        foreach ($rows as $index => $row) {
            // تخطي أول 3 صفوف (هيدر وترويسة الأكسل)
            if ($index < 3) {
                continue;
            }

            // الأعمدة في الأكسل (بدون HeadingRow)
            $col0 = trim((string) ($row[0] ?? '')); // Category
            $col1 = trim((string) ($row[1] ?? '')); // Sub Category
            $col2 = trim((string) ($row[2] ?? '')); // Product Name
            // $qty = $row[3] ?? null; // Grand Total  (لن نستخدمه)

            // تخطي الصفوف الفارغة تماماً
            if ($col0 === '' && $col1 === '' && $col2 === '') {
                continue;
            }

            // تخطي أي صف فيه كلمة Total (Totals, Beverage Total, 01Mula Total, ...)
            if (
                Str::contains($col0, 'Total', true) ||
                Str::contains($col1, 'Total', true) ||
                Str::contains($col2, 'Total', true)
            ) {
                continue;
            }

            /**
             * 1) صف يحتوي فقط على Category
             * مثال: [ "Beverage", "", "" ]
             */
            if ($col0 !== '' && $col1 === '' && $col2 === '') {
                $currentCategoryName    = $col0;
                $currentSubcategoryName = null;
                continue;
            }

            /**
             * 2) صف يحتوي على Category + SubCategory + Product
             * مثال: [ "Beverage", "BLENDED/FRAPE", "Chocolate Chip Blended" ]
             */
            if ($col0 !== '' && $col1 !== '' && $col2 !== '') {
                $currentCategoryName    = $col0;
                $currentSubcategoryName = $col1;
            }

            /**
             * 3) صف يحتوي على SubCategory + Product فقط
             * مثال: [ "", "COLD BEVERAGE", "100 Plus" ]
             * نفترض أن الـ Category هو آخر Category تم قراءته سابقاً
             */
            if ($col0 === '' && $col1 !== '' && $col2 !== '') {
                $currentSubcategoryName = $col1;
            }

            /**
             * 4) صف يحتوي فقط على Product
             * مثال: [ "", "", "Dark Mocha Frape" ]
             * هنا نستخدم آخر Category و SubCategory محفوظة
             */
            if ($col2 === '') {
                // لا يوجد اسم منتج، لا نضيف شيء
                continue;
            }

            // الآن لدينا اسم منتج في $col2، ويجب أن يكون لدينا Category واحدة على الأقل
            if (!$currentCategoryName) {
                // أمان إضافي: لا نضيف منتج بدون Category
                continue;
            }

            /**
             * 1) Category رئيسية:
             *    لا نستخدم for_pos في البحث كي لا تتكرر الأسماء،
             *    ثم نحدث for_pos = 1 إن وُجدت وكانت غير مضبوطة.
             */
            $category = Category::where('name', $currentCategoryName)
                ->whereNull('parent_id')
                ->first();

            if (! $category) {
                // إنشاء جديد
                $category = Category::create([
                    'name'      => $currentCategoryName,
                    'parent_id' => null,
                    'for_pos'   => 1,
                ]);
            } else {
                // تحديث for_pos إن لم تكن 1
                if ((int) $category->for_pos !== 1) {
                    $category->for_pos = 1;
                    $category->save();
                }
            }

            $finalCategoryId = $category->id;

            /**
             * 2) Sub Category (ابن للـ Category) إن وجدت:
             *    أيضاً نبحث بالاسم + parent_id فقط، ثم نضبط for_pos = 1.
             */
            if ($currentSubcategoryName) {
                $subCategory = Category::where('name', $currentSubcategoryName)
                    ->where('parent_id', $category->id)
                    ->first();

                if (! $subCategory) {
                    $subCategory = Category::create([
                        'name'      => $currentSubcategoryName,
                        'parent_id' => $category->id,
                        'for_pos'   => 1,
                    ]);
                } else {
                    if ((int) $subCategory->for_pos !== 1) {
                        $subCategory->for_pos = 1;
                        $subCategory->save();
                    }
                }

                $finalCategoryId = $subCategory->id;
            }

            /**
             * 3) المنتج:
             *    نستخدم type = TYPE_FINISHED_POS كما وضعت أنت.
             */
            $product = Product::firstOrCreate(
                [
                    'name' => $col2,
                    'type' => Product::TYPE_FINISHED_POS,
                ],
                [
                    'category_id' => $finalCategoryId,
                    'type'        => Product::TYPE_FINISHED_POS,
                    // يمكنك هنا ضبط active/basic_price إن أحببت
                    // 'active'      => 1,
                    // 'basic_price' => 1,
                ]
            );

            /**
             * 4) ربط المنتج بوحدة PIECE كوحدة رئيسية، إن لم تكن مضبوطة
             */
            if (! $product->main_unit_id) {
                $product->main_unit_id = $pieceUnit->id;

                // اختياري: لو تحب تضبط basic_price = 1 افتراضياً
                if (! $product->basic_price) {
                    $product->basic_price = 1;
                }

                $product->save();
            }

            /**
             * 5) إنشاء UnitPrice للمنتج مع وحدة PIECE
             *    package_size = 1, price = 1
             *    مع usage_scope/use_in_orders (عدّل حسب جدولك إن لزم)
             */
            $existingUnitPrice = UnitPrice::where('product_id', $product->id)
                ->where('unit_id', $pieceUnit->id)
                ->where('package_size', 1)
                ->first();

            if (! $existingUnitPrice) {
                UnitPrice::create([
                    'product_id'   => $product->id,
                    'unit_id'      => $pieceUnit->id,
                    'price'        => 1,
                    'package_size' => 1,
                    'usage_scope'  => UnitPrice::USAGE_ALL,  // بناءً على الـ scope في موديل Product
                    'use_in_orders'=> 1,
                    // أضف أعمدة أخرى إلزامية عندك (مثلاً min_qty, max_qty, etc.) إن وجدت
                ]);
            }
        }
    }
}
