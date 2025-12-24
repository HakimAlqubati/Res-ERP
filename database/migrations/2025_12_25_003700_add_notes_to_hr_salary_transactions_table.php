<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds notes field to hr_salary_transactions for storing detailed
     * calculation notes, especially for bracket-based deductions.
     */
    public function up(): void
    {
        Schema::table('hr_salary_transactions', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('description');
            $table->decimal('effective_percentage', 8, 4)->nullable()->after('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_salary_transactions', function (Blueprint $table) {
            $table->dropColumn(['notes', 'effective_percentage']);
        });
    }
};
