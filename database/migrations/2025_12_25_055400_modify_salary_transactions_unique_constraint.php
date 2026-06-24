<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Modify the unique constraint on hr_salary_transactions to include reference_id.
     * This allows multiple installment transactions for the same employee/month/type.
     */
    public function up(): void
    {
        // Add index for employee_id to satisfy foreign key constraint before dropping the unique index
        Schema::table('hr_salary_transactions', function (Blueprint $table) {
            $table->index('employee_id');
        });

        // Now drop the unique index
        Schema::table('hr_salary_transactions', function (Blueprint $table) {
            $table->dropUnique('hr_salary_transactions_emp_ym_type_sub_operation_payroll_unique');
        });

        // Create new unique index that includes reference_id
        Schema::table('hr_salary_transactions', function (Blueprint $table) {
            $table->unique(
                ['employee_id', 'year', 'month', 'type', 'sub_type', 'payroll_id', 'operation', 'reference_id'],
                'hr_salary_transactions_emp_ym_type_sub_op_payroll_ref_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_salary_transactions', function (Blueprint $table) {
            $table->dropUnique('hr_salary_transactions_emp_ym_type_sub_op_payroll_ref_unique');
        });

        Schema::table('hr_salary_transactions', function (Blueprint $table) {
            $table->unique(
                ['employee_id', 'year', 'month', 'type', 'sub_type', 'payroll_id', 'operation'],
                'hr_salary_transactions_emp_ym_type_sub_operation_payroll_unique'
            );
        });

        Schema::table('hr_salary_transactions', function (Blueprint $table) {
            $table->dropIndex(['employee_id']);
        });
    }
};
