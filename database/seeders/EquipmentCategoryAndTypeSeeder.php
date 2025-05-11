<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EquipmentCategory;
use App\Models\EquipmentType;

class EquipmentCategoryAndTypeSeeder extends Seeder
{
    public function run(): void
    {
        // Define categories with types
        $categories = [
            [
                'name' => 'Computers',
                'equipment_code_start_with' => 'CMP',
                'description' => 'Laptops and Desktops',
                'types' => [
                    'Dell Latitude 5520',
                    'HP ProBook 450',
                    'MacBook Pro M2',
                ],
            ],
            [
                'name' => 'Printers',
                'equipment_code_start_with' => 'PRT',
                'description' => 'Office Printers',
                'types' => [
                    'HP LaserJet Pro',
                    'Canon Pixma 360',
                ],
            ],
            [
                'name' => 'Cameras',
                'equipment_code_start_with' => 'CAM',
                'description' => 'Surveillance cameras',
                'types' => [
                    'Hikvision Dome',
                    'Dahua Bullet 4MP',
                ],
            ],
        ];

        foreach ($categories as $catData) {
            $category = EquipmentCategory::create([
                'name' => $catData['name'],
                'equipment_code_start_with' => $catData['equipment_code_start_with'],
                'description' => $catData['description'],
                'active' => true,
            ]);

            foreach ($catData['types'] as $typeName) {
                EquipmentType::create([
                    'name' => $typeName,
                    'category_id' => $category->id,
                    'description' => null,
                    'active' => true,
                ]);
            }
        }
    }
}
