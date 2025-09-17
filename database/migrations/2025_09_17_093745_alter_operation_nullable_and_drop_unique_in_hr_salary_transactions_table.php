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
            // 🟢 حذف القديم
            $table->dropUnique('hr_salary_transactions_emp_ym_type_sub_payroll_unique');
        });

        Schema::table('hr_salary_transactions', function (Blueprint $table) {
            // 🟢 إعادة إنشاء UNIQUE جديد بنفس الأعمدة
            $table->unique(
                ['employee_id', 'year', 'month', 'type', 'sub_type', 'payroll_id', 'operation'],
                'hr_salary_transactions_emp_ym_type_sub_operation_payroll_unique'
            );
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_salary_transactions', function (Blueprint $table) {
            $table->dropUnique('hr_salary_transactions_emp_ym_type_sub_operation_payroll_unique');

            $table->unique(
                ['employee_id', 'year', 'month', 'type', 'sub_type', 'payroll_id'],
                'hr_salary_transactions_emp_ym_type_sub_payroll_unique'
            );
        });
    }
};
