<?php

namespace App\Services\Products\Manufacturing;

use App\Models\Product;

class ProductManufacturingService
{
    public function getProductItems($id)
    {
        $data = Product::with(['productItems', 'unitPrices'])->find($id)->toArray();
        return $data;
    }
}
