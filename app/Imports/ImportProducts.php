<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;

class ImportProducts implements ToModel
{
    /**
     * @param Collection $collection
     */
    public function model(array $row)
    {

        if (isset($row[0]) && !Product::where('code', $row[0])->first()) {
            // Create a new product instance
            $product = new Product();

            // Set the translations
            $product->setTranslations('name', [
                'ar' => $row[2],
                'en' => $row[1],
            ]);
            $product->active = 1;
            $product->category_id =  Category::where('category_code', $row[3])->first()->id;
            $product->category_code = $row[3];
            $product->product_code = $row[0];
            $product->code = $row[0];

            // Save the product
            $product->save();

            return $product;
        } else {
            return null;
        }
    }
}
