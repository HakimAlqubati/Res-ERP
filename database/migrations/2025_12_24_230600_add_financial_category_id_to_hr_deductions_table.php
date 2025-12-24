<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds financial_category_id to hr_deductions table for dynamic linking
     * with financial system. Deductions with a financial category will
     * create separate financial transactions when payroll is processed.
     */
    public function up(): void
    {
        Schema::table('hr_deductions', function (Blueprint $table) {
            $table->foreignId('financial_category_id')
                ->nullable()
                ->after('employer_amount')
                ->constrained('financial_categories')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_deductions', function (Blueprint $table) {
            $table->dropForeign(['financial_category_id']);
            $table->dropColumn('financial_category_id');
        });
    }
};
