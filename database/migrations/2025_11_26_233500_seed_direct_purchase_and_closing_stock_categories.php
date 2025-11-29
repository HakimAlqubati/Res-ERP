<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\FinancialCategory;
use App\Enums\FinancialCategoryCode;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create Direct Purchase category
        FinancialCategory::firstOrCreate(
            ['code' => FinancialCategoryCode::TRANSFERS],
            [
                'name' => 'Transfers',
                'type' => FinancialCategory::TYPE_EXPENSE,
                'description' => 'Transfers ofbranches',
                'is_system' => true,
                'is_visible' => true,
            ]
        );
        FinancialCategory::firstOrCreate(
            ['code' => FinancialCategoryCode::DIRECT_PURCHASE],
            [
                'name' => 'Direct Purchase',
                'type' => FinancialCategory::TYPE_EXPENSE,
                'description' => 'Direct purchases from suppliers',
                'is_system' => true,
                'is_visible' => true,
            ]
        );
        FinancialCategory::firstOrCreate(
            ['code' => FinancialCategoryCode::SALES],
            [
                'name' => 'Branch Sales',
                'type' => 'income',
                'description' => 'Revenue from branch sales',
                'is_system' => true,
                'is_visible' => true,
            ],
        );

        // Create Closing Stock category
        FinancialCategory::firstOrCreate(
            ['code' => FinancialCategoryCode::CLOSING_STOCK],
            [
                'name' => 'Closing Stock',
                'type' => FinancialCategory::TYPE_EXPENSE,
                'description' => '',
                'is_system' => true,
                'is_visible' => true,
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        FinancialCategory::where('code', FinancialCategoryCode::DIRECT_PURCHASE)->delete();
        FinancialCategory::where('code', FinancialCategoryCode::CLOSING_STOCK)->delete();
    }
};
