<?php

namespace App\Services\MigrationScripts;

use Exception;
use App\Models\Product;
use App\Models\UnitPrice;
use Illuminate\Support\Facades\DB;


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
        // Retrieve all products that have unit prices
        $products = Product::whereHas('unitPrices')->with('unitPrices')->get();

        foreach ($products as $product) {
            self::updatePackageSizeAndOrderForProduct($product);
        }
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
                DB::rollBack();
                return;
            }

            // Get the first (smallest price) unit price
            $firstUnitPrice = $unitPrices->first()->price;

            // Assign order dynamically and calculate package_size
            foreach ($unitPrices as $index => $unitPrice) {
                $packageSize = ($firstUnitPrice > 0) ? round($unitPrice->price / $firstUnitPrice, 2) : 1;

                $unitPrice->update([
                    'order' => $index + 1,
                    'package_size' => $packageSize,
                ]);
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
        }
    }
}
