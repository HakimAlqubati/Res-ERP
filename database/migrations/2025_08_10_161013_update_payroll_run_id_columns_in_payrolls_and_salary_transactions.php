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
        // جدول hr_payrolls
        Schema::table('hr_payrolls', function (Blueprint $table) {
            // تأكد أن العمود موجود
            if (!Schema::hasColumn('hr_payrolls', 'payroll_run_id')) {
                $table->unsignedBigInteger('payroll_run_id')->after('id');
                $table->foreign('payroll_run_id')
                    ->references('id')->on('hr_payroll_runs')
                    ->onDelete('cascade');
            } else {
                // جعل العمود NOT NULL
                $table->unsignedBigInteger('payroll_run_id')->nullable(false)->change();
            }
        });

        // جدول hr_salary_transactions
        Schema::table('hr_salary_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('hr_salary_transactions', 'payroll_run_id')) {
                $table->unsignedBigInteger('payroll_run_id')->after('id');
                $table->foreign('payroll_run_id')
                    ->references('id')->on('hr_payroll_runs')
                    ->onDelete('cascade');
            } else {
                $table->unsignedBigInteger('payroll_run_id')->nullable(false)->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('hr_payrolls', function (Blueprint $table) {
            $table->dropForeign(['payroll_run_id']);
            $table->dropColumn('payroll_run_id');
        });

        Schema::table('hr_salary_transactions', function (Blueprint $table) {
            $table->dropForeign(['payroll_run_id']);
            $table->dropColumn('payroll_run_id');
        });
    }
};
