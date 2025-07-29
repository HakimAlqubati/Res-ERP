<?php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = \App\Models\Product::class;

    public function definition()
    {
// عشوائي بين القيم الثلاث
        $categoryIds = [7, 13, 16];

        return [
            'name'                   => $this->faker->unique()->words(3, true),
            'code'                   => $this->faker->unique()->ean8,
            'description'            => $this->faker->sentence,
            'active'                 => true,
            'category_id'            => $categoryIds[array_rand($categoryIds)],
            'product_code'           => $this->faker->unique()->ean13,
            'category_code'          => $this->faker->bothify('CAT###'),
            'main_unit_id'           => null, // سيتم ملؤها لاحقاً إذا لزم الأمر
            'basic_price'            => $this->faker->randomFloat(2, 10, 100),
            'minimum_stock_qty'      => $this->faker->numberBetween(0, 20),
            'waste_stock_percentage' => $this->faker->randomFloat(2, 0, 5),
        ];
    }
}