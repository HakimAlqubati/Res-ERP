<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\FinancialCategory;
use App\Models\FinancialTransaction;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Disable foreign key checks to allow truncation
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Truncate tables
        FinancialTransaction::truncate();
        FinancialCategory::truncate();

        // Enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Seed new categories
        $categories = [
            [
                'name' => 'Salaries',
                'type' => 'expense', // Hardcoded to avoid class loading issues if constants change, but constants are safer. Using string for simplicity here matching the model.
                'is_system' => true,
                'is_visible' => true,
            ],
            [
                'name' => 'Rent',
                'type' => 'expense',
                'is_system' => true,
                'is_visible' => true,
            ],
            [
                'name' => 'Bills & Suppliers Invoices',
                'type' => 'expense',
                'is_system' => true,
                'is_visible' => true,
            ],
            [
                'name' => 'Branch Sales',
                'type' => 'income',
                'is_system' => true,
                'is_visible' => true,
            ],
        ];

        foreach ($categories as $category) {
            FinancialCategory::create($category);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
