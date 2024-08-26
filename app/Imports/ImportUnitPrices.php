<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Unit;
use App\Models\UnitPrice;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;

class ImportUnitPrices implements ToModel
{
    /**
     * @param Collection $collection
     */
    public function model(array $row)
    {
        // dd($row);
        if (isset($row[10])) {
            return new UnitPrice([
                'product_id' =>  Product::where('code', $row[0])->first()->id,
                // 'product_id' => 1,
                'unit_id' =>  Unit::where('name', $row[10])->first()->id,
                // 'unit_id' =>  1,
                'price' => $row[11],
            ]);
        } else {
            return null;
        }
    }
}
