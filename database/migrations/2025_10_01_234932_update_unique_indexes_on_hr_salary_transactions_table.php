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
        Schema::table('hr_salary_transactions', function (Blueprint $table) {
            $table->dropUnique('hst_emp_year_month_run_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_salary_transactions', function (Blueprint $table) {
            $table->unique(
                ['employee_id', 'year', 'month', 'payroll_run_id'],
                'hst_emp_year_month_run_unique'
            );
        });
    }
};
