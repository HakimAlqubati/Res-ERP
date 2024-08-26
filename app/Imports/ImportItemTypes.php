<?php

namespace App\Imports;

use App\Models\ItemType;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;

class ImportItemTypes implements ToModel
{
    /**
     * @param Collection $collection
     */
    public function model(array $row)
    {
        // if (isset($row[5]) && !ItemType::find($row[5])) {
        if (!ItemType::where('name', 'LIKE', '%' . $row[6] . '%')->first()) {
            // Create a new item_type instance
            $item_type = new ItemType(); 
            $item_type->item_type_id = $row[5];
            // Set the translations
            $item_type->setTranslations('name', [
                'ar' => $row[7],
                'en' => $row[6],
            ]);
            $item_type->active = 1;
            // Save the item_type
            $item_type->save();

            return $item_type;
        } else {
            return null;
        }
    }
}
