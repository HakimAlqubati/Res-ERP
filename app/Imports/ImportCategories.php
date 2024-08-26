<?php

namespace App\Imports;

use App\Models\Category;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;

class ImportCategories implements ToModel
{
    /**
     * @param Collection $collection
     */
    public function model(array $row)
    {

        if (isset($row[4]) && !Category::where('name', $row[4])->first()) {
            // Create a new category instance
            $category = new Category();

            $category->name = $row[4];
            $category->active = 1;
            $category->category_code = $row[3];
            // Save the category
            $category->save();
            return $category;
        }
    }
}
