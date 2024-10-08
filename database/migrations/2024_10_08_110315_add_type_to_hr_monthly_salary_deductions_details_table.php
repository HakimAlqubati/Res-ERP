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
        Schema::table('hr_monthly_salary_deductions_details', function (Blueprint $table) {
            $table->string('type')->nullable()->after('deduction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_monthly_salary_deductions_details', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
