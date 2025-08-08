<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('hr_salary_transactions', function (Blueprint $table) {
            // غيّر اسم الفهرس لو حاب
            $table->unique(['employee_id', 'year', 'month', 'type', 'payroll_id'], 'hr_salary_transactions_employee_year_month_unique');
        });
    }

    public function down(): void
    {
        Schema::table('hr_salary_transactions', function (Blueprint $table) {
            $table->dropUnique('hr_salary_transactions_employee_year_month_unique');
        });
    }
};
