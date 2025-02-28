<?php

namespace App\Services\Products\Manufacturing;

use App\Models\Product;

class ProductManufacturingService
{
    public function getProductItems($id)
    {
        $product = Product::with('productItems')->find($id);
        return $product;
    }
}