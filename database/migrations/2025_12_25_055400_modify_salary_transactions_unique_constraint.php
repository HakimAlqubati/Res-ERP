<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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
        // First, find and drop any foreign key constraints that depend on this index
        $foreignKeys = $this->getForeignKeysOnIndex('hr_salary_transactions', 'hr_salary_transactions_emp_ym_type_sub_operation_payroll_unique');

        Schema::table('hr_salary_transactions', function (Blueprint $table) use ($foreignKeys) {
            // Drop foreign keys first
            foreach ($foreignKeys as $fk) {
                $table->dropForeign($fk);
            }
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
    }

    /**
     * Get foreign key constraint names that reference a specific index.
     */
    protected function getForeignKeysOnIndex(string $table, string $indexName): array
    {
        $database = config('database.connections.mysql.database');

        $results = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = ? 
            AND TABLE_NAME = ? 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$database, $table]);

        return array_map(fn($row) => $row->CONSTRAINT_NAME, $results);
    }
};
