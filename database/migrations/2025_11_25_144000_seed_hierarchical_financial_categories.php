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
        return;
        // Disable foreign key checks to allow truncation
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Truncate tables
        FinancialTransaction::truncate();
        FinancialCategory::truncate();

        // Enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 1. Branch Bills (Parent)
        $branchBills = FinancialCategory::create([
            'name' => 'Branch Bills',
            'type' => 'expense',
            'is_system' => true,
            'is_visible' => true,
        ]);

        // Children of Branch Bills
        FinancialCategory::create([
            'name' => 'Electricity',
            'type' => 'expense',
            'parent_id' => $branchBills->id,
            'is_system' => true,
            'is_visible' => true,
        ]);

        FinancialCategory::create([
            'name' => 'Water',
            'type' => 'expense',
            'parent_id' => $branchBills->id,
            'is_system' => true,
            'is_visible' => true,
        ]);

        FinancialCategory::create([
            'name' => 'Maintenance',
            'type' => 'expense',
            'parent_id' => $branchBills->id,
            'is_system' => true,
            'is_visible' => true,
        ]);

        // 2. Transfers
        FinancialCategory::create([
            'name' => 'Transfers',
            'type' => 'expense', // Assuming expense based on context, user can change later
            'is_system' => true,
            'is_visible' => true,
        ]);

        // 3. Salaries
        FinancialCategory::create([
            'name' => 'Salaries',
            'type' => 'expense',
            'is_system' => true,
            'is_visible' => true,
        ]);

        // 4. Rent
        FinancialCategory::create([
            'name' => 'Rent',
            'type' => 'expense',
            'is_system' => true,
            'is_visible' => true,
        ]);

        // 5. Branch Sales
        FinancialCategory::create([
            'name' => 'Branch Sales',
            'type' => 'income',
            'is_system' => true,
            'is_visible' => true,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No action needed for down as this is a data seeding migration
    }
};
