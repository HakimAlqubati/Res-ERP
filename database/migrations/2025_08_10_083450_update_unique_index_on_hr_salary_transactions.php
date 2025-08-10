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
            // 1) حذف الإندكس الفريد القديم
            // الاسم الظاهر في الصورة: hr_salary_transactions_employee_year_month_unique
            $table->dropUnique('hr_salary_transactions_employee_year_month_unique');

            // 2) إضافة إندكس فريد جديد يتضمن sub_type
            $table->unique(
                ['employee_id', 'year', 'month', 'type', 'sub_type', 'payroll_id'],
                'hr_salary_transactions_emp_ym_type_sub_payroll_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_salary_transactions', function (Blueprint $table) {
                 // الرجوع: حذف الإندكس الجديد
                 $table->dropUnique('hr_salary_transactions_emp_ym_type_sub_payroll_unique');

                 // إعادة إنشاء الإندكس القديم بدون sub_type
                 $table->unique(
                     ['employee_id', 'year', 'month', 'type', 'payroll_id'],
                     'hr_salary_transactions_employee_year_month_unique'
                 );
        });
    }
};
