<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('hr_month_salary_details', function (Blueprint $table) {
            $table->decimal('total_other_adding', 10, 2)->nullable()->after('total_deductions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_month_salary_details', function (Blueprint $table) {
            $table->dropColumn('total_other_adding');
        });
    }
};
