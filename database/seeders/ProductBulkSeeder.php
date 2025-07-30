<?php
namespace Database\Seeders;

use App\Models\Product;
use App\Models\Unit;
use App\Models\UnitPrice;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class ProductBulkSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();

// 1. جلب جميع الوحدات الموجودة
        $units       = Unit::pluck('id')->toArray();
        $categoryIds = [7, 13, 16];

        $productsToInsert   = [];
        $unitPricesToInsert = [];
        $bulkSize           = 5000; // كل دفعة 5 آلاف (يمكن تغييره حسب الرام)
        $productCount       = 100000;
        $now                = now();

        for ($i = 0; $i < $productCount; $i++) {$code = $faker->unique()->ean8;
            $productsToInsert[]              = [
                'name'                   => $faker->unique()->words(3, true),
                'code'                   => $code,
                'description'            => $faker->sentence,
                'active'                 => true,
                'category_id'            => $categoryIds[array_rand($categoryIds)],
                'product_code'           => $faker->unique()->ean13,
                'category_code'          => $faker->bothify('CAT###'),
                'main_unit_id'           => null,
                'basic_price'            => $faker->randomFloat(2, 10, 100),
                'minimum_stock_qty'      => $faker->numberBetween(0, 20),
                'waste_stock_percentage' => $faker->randomFloat(2, 0, 5),
                'created_at'             => $now,
                'updated_at'             => $now,
            ];

            // دفعة كل bulkSize
            if (count($productsToInsert) >= $bulkSize || $i == $productCount - 1) {
                // إدخال المنتجات دفعة واحدة
                Product::insert($productsToInsert);

                // جلب الـ ids للمنتجات المضافة حديثًا (افترض auto-increment ID)
                $lastInsertedId  = Product::orderBy('id', 'desc')->first()->id ?? 0;
                $firstInsertedId = $lastInsertedId - count($productsToInsert) + 1;

                foreach (range($firstInsertedId, $lastInsertedId) as $productId) {
                    // لكل منتج أضف له 2 أو 3 وحدات بشكل عشوائي
                    $numUnits      = rand(2, 3);
                    $selectedUnits = Arr::random($units, $numUnits);
                    $order         = 1;
                    foreach ($selectedUnits as $unitId) {
                        $unitPricesToInsert[] = [
                            'product_id'   => $productId,
                            'unit_id'      => $unitId,
                            'price'        => $faker->randomFloat(2, 10, 200),
                            'package_size' => $faker->randomFloat(2, 1, 10),
                            'order'        => $order++,
                            'created_at'   => $now,
                            'updated_at'   => $now,
                        ];
                    }
                }

                // bulk insert للوحدات
                if (count($unitPricesToInsert) >= $bulkSize * 2 || $i == $productCount - 1) {
                    UnitPrice::insert($unitPricesToInsert);
                    $unitPricesToInsert = [];
                }

                $productsToInsert = [];
            }}
    }
}