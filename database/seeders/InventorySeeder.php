<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all products with their related units
        $products = Product::with('unitPrices')->unmanufacturingCategory()->get();

        foreach ($products as $product) {
            foreach ($product->unitPrices as $unitPrice) {
                // Create inventory record with 0 quantity if it doesn't already exist
                Inventory::firstOrCreate(
                    [
                        'product_id' => $product->id,
                        'unit_id' => $unitPrice->unit_id,
                    ],
                    [
                        'quantity' => 0, // Default quantity
                    ]
                );
            }
        }
    }
}
