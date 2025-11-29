<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\FinancialCategory;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        return;
        $categories = [
           
            [
                'name' => 'Rent',
                'type' => 'expense',
                'description' => 'Monthly branch rent',
            ],
            [
                'name' => 'Bills & Suppliers Invoices',
                'type' => 'expense',
                'description' => 'Utility bills and supplier payments',
            ],
            [
                'name' => 'Branch Sales',
                'type' => 'income',
                'description' => 'Revenue from branch sales',
            ],
            [
                'name' => 'Other Income',
                'type' => 'income',
                'description' => 'Miscellaneous income sources',
            ],
        ];

        foreach ($categories as $categoryData) {
            $category = FinancialCategory::firstOrCreate(
                ['name' => $categoryData['name']],
                ['type' => $categoryData['type']]
            );

            $category->update(['description' => $categoryData['description']]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        return; 
        // We don't necessarily want to delete the categories or clear descriptions on rollback 
        // as they might have been edited by the user.
        // But strictly speaking, we could set descriptions to null.
        FinancialCategory::whereIn('name', [
            'Salaries',
            'Rent',
            'Bills & Suppliers Invoices',
            'Branch Sales',
            'Other Income'
        ])->update(['description' => null]);
    }
};
