<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // hr_payrolls -> drop & recreate FK with cascade
        Schema::table('hr_payrolls', function (Blueprint $table) {
            // إسقاط القيد القديم (الاسم الافتراضي من لاراڤيل)
            $table->dropForeign(['payroll_run_id']);
        });

        Schema::table('hr_payrolls', function (Blueprint $table) {
            // إعادة الإنشاء مع ON DELETE CASCADE
            $table->foreign('payroll_run_id')
                ->references('id')->on('hr_payroll_runs')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });

        // hr_salary_transactions -> drop & recreate FK with cascade
        Schema::table('hr_salary_transactions', function (Blueprint $table) {
            $table->dropForeign(['payroll_run_id']);
        });

        Schema::table('hr_salary_transactions', function (Blueprint $table) {
            $table->foreign('payroll_run_id')
                ->references('id')->on('hr_payroll_runs')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        // رجّعها بدون CASCADE (سلوك تقييدي)
        Schema::table('hr_payrolls', function (Blueprint $table) {
            $table->dropForeign(['payroll_run_id']);
        });
        Schema::table('hr_payrolls', function (Blueprint $table) {
            $table->foreign('payroll_run_id')
                ->references('id')->on('hr_payroll_runs');
        });

        Schema::table('hr_salary_transactions', function (Blueprint $table) {
            $table->dropForeign(['payroll_run_id']);
        });
        Schema::table('hr_salary_transactions', function (Blueprint $table) {
            $table->foreign('payroll_run_id')
                ->references('id')->on('hr_payroll_runs');
        });
    }
};
