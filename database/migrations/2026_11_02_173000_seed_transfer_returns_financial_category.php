<?php

use App\Enums\FinancialCategoryCode;
use App\Models\FinancialCategory;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        FinancialCategory::updateOrCreate(
            ['code' => FinancialCategoryCode::TRANSFER_RETURNS],
            [
                'name' => 'Transfer Returns',
                'type' => FinancialCategory::TYPE_INCOME,
                'profit_type' => FinancialCategory::PROFIT_TYPE_GROSS,
                'description' => 'Returns from branch transfers',
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
        FinancialCategory::where('code', FinancialCategoryCode::TRANSFER_RETURNS)->delete();
    }
};
