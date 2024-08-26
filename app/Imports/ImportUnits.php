<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Unit;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;

class ImportUnits implements ToModel
{
    /**
     * @param Collection $collection
     */
    public function model(array $row)
    {
        if (!Unit::where('name', $row[0])->first()) {
            return new Unit([
                'name' => $row[0],
                'code' => $row[0],
                'active' => 1,
            ]);
        } else {
            return null;
        }
    }
}
