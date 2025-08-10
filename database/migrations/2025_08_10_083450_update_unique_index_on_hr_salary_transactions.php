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
        $table = 'hr_salary_transactions';
        $schema = DB::getDatabaseName();

        // 1) Find FK(s) on employee_id with their rules and targets
        $fkRows = DB::table('information_schema.KEY_COLUMN_USAGE as kcu')
            ->join('information_schema.REFERENTIAL_CONSTRAINTS as rc', function ($join) {
                $join->on('kcu.CONSTRAINT_NAME', '=', 'rc.CONSTRAINT_NAME')
                     ->on('kcu.CONSTRAINT_SCHEMA', '=', 'rc.CONSTRAINT_SCHEMA');
            })
            ->select(
                'kcu.CONSTRAINT_NAME',
                'kcu.REFERENCED_TABLE_NAME as ref_table',
                'kcu.REFERENCED_COLUMN_NAME as ref_column',
                'rc.DELETE_RULE',
                'rc.UPDATE_RULE'
            )
            ->where('kcu.TABLE_SCHEMA', $schema)
            ->where('kcu.TABLE_NAME', $table)
            ->where('kcu.COLUMN_NAME', 'employee_id')
            ->whereNotNull('kcu.REFERENCED_TABLE_NAME')
            ->distinct()
            ->get();

        // 2) Drop FK(s) first
        foreach ($fkRows as $fk) {
            DB::statement("ALTER TABLE `$table` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
        }

        // 3) Drop the old unique index (use the exact name from the error)
        //    If name differs in your DB, add it here or query INFORMATION_SCHEMA.STATISTICS.
        DB::statement('ALTER TABLE `hr_salary_transactions` DROP INDEX `hr_salary_transactions_employee_year_month_unique`');

        // 4) Create the new desired unique (adjust columns as needed)
        Schema::table($table, function (Blueprint $tbl) {
            $tbl->unique(
                ['employee_id', 'year', 'month', 'payroll_run_id'],
                'hst_emp_year_month_run_unique'
            );
        });

        // 5) Re-create FK(s) with the same rules as before
        foreach ($fkRows as $fk) {
            $deleteRule = $fk->DELETE_RULE ?: 'RESTRICT';
            $updateRule = $fk->UPDATE_RULE ?: 'RESTRICT';
            $refTable   = $fk->ref_table ?: 'hr_employees';
            $refColumn  = $fk->ref_column ?: 'id';

            // Reuse the original FK name to avoid surprises
            DB::statement("
                ALTER TABLE `$table`
                ADD CONSTRAINT `{$fk->CONSTRAINT_NAME}`
                FOREIGN KEY (`employee_id`) REFERENCES `$refTable`(`$refColumn`)
                ON DELETE $deleteRule ON UPDATE $updateRule
            ");
        }
    }

    public function down(): void
    {
        $table = 'hr_salary_transactions';
        $schema = DB::getDatabaseName();

        // 1) Drop FK(s) again to be able to change indexes
        $fkRows = DB::table('information_schema.KEY_COLUMN_USAGE as kcu')
            ->join('information_schema.REFERENTIAL_CONSTRAINTS as rc', function ($join) {
                $join->on('kcu.CONSTRAINT_NAME', '=', 'rc.CONSTRAINT_NAME')
                     ->on('kcu.CONSTRAINT_SCHEMA', '=', 'rc.CONSTRAINT_SCHEMA');
            })
            ->select(
                'kcu.CONSTRAINT_NAME',
                'kcu.REFERENCED_TABLE_NAME as ref_table',
                'kcu.REFERENCED_COLUMN_NAME as ref_column',
                'rc.DELETE_RULE',
                'rc.UPDATE_RULE'
            )
            ->where('kcu.TABLE_SCHEMA', $schema)
            ->where('kcu.TABLE_NAME', $table)
            ->where('kcu.COLUMN_NAME', 'employee_id')
            ->whereNotNull('kcu.REFERENCED_TABLE_NAME')
            ->distinct()
            ->get();

        foreach ($fkRows as $fk) {
            DB::statement("ALTER TABLE `$table` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
        }

        // 2) Drop the new unique
        Schema::table($table, function (Blueprint $tbl) {
            $tbl->dropUnique('hst_emp_year_month_run_unique');
        });

        // 3) Restore the old unique
        Schema::table($table, function (Blueprint $tbl) {
            $tbl->unique(
                ['employee_id', 'year', 'month'],
                'hr_salary_transactions_employee_year_month_unique'
            );
        });

        // 4) Re-create FK(s) back
        foreach ($fkRows as $fk) {
            $deleteRule = $fk->DELETE_RULE ?: 'RESTRICT';
            $updateRule = $fk->UPDATE_RULE ?: 'RESTRICT';
            $refTable   = $fk->ref_table ?: 'hr_employees';
            $refColumn  = $fk->ref_column ?: 'id';

            DB::statement("
                ALTER TABLE `$table`
                ADD CONSTRAINT `{$fk->CONSTRAINT_NAME}`
                FOREIGN KEY (`employee_id`) REFERENCES `$refTable`(`$refColumn`)
                ON DELETE $deleteRule ON UPDATE $updateRule
            ");
        }
    }
};
