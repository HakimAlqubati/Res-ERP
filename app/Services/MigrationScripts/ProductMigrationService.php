<?php

namespace App\Services\MigrationScripts;

use App\Models\Product;
use App\Models\UnitPrice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductMigrationService
{

    /**
     * Updates package_size and order dynamically for all products.
     *
     * - Order field is sorted based on price (asc).
     * - Package size is calculated relative to the first (lowest price) unit price.
     */
    public static function updatePackageSizeAndOrder()
    {
        Log::info('Starting package_size and order update for all products...');

        // Retrieve all products that have unit prices
        $products = Product::whereHas('unitPrices')->with('unitPrices')->get();
       
        // Log::info('DDDD', [$products]);
        // dd(count($products));
        foreach ($products as $product) {
            self::updatePackageSizeAndOrderForProduct($product);
        }

        Log::info('Completed package_size and order update for all products.');
    }

    /**
     * Updates package_size and order dynamically for a single product.
     *
     * @param Product $product
     */
    public static function updatePackageSizeAndOrderForProduct(Product $product)
    {
        DB::beginTransaction();

        try {
            // Fetch all unit prices for the product, sorted by price (ascending)
            $unitPrices = $product->unitPrices()->orderBy('price', 'asc')->get();

            if ($unitPrices->isEmpty()) {
                Log::warning("No unit prices found for Product ID: {$product->id}");
                DB::rollBack();
                return;
            }

            // Get the first (smallest price) unit price
            $firstUnitPrice = $unitPrices->first()->price;

            // Assign order dynamically and calculate package_size
            foreach ($unitPrices as $index => $unitPrice) {
                $packageSize = ($firstUnitPrice > 0) ? round($unitPrice->price / $firstUnitPrice, 2) : 1;

                $unitPrice->update([
                    'order' => $index + 1, // Order starts from 1
                    'package_size' => $packageSize,
                ]);
            }

            DB::commit();
            Log::info("Updated package_size and order for Product ID: {$product->id}");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating package_size and order for Product ID: {$product->id}. Error: {$e->getMessage()}");
        }
    }
}
