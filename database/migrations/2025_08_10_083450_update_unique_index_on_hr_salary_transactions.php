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
        $table  = 'hr_salary_transactions';
        $schema = DB::getDatabaseName();

        // 0) Ensure payroll_run_id column exists (add it first!)
        if (!Schema::hasColumn($table, 'payroll_run_id')) {
            Schema::table($table, function (Blueprint $tbl) {
                // default(0) to allow NOT NULL on existing rows safely
                $tbl->unsignedBigInteger('payroll_run_id')->default(0)->after('month');
                $tbl->index('payroll_run_id', 'hst_payroll_run_id_idx'); // optional helper index
            });
        }

        // 1) Capture FK(s) on employee_id so we can drop & recreate them
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

        // 2) Drop FK(s) first (required to change indexes they depend on)
        foreach ($fkRows as $fk) {
            DB::statement("ALTER TABLE `$table` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
        }

        // 3) Drop old unique (exact name from your error)
        DB::statement("ALTER TABLE `$table` DROP INDEX `hr_salary_transactions_employee_year_month_unique`");

        // 4) Create the new unique with payroll_run_id included
        Schema::table($table, function (Blueprint $tbl) {
            $tbl->unique(
                ['employee_id', 'year', 'month', 'payroll_run_id'],
                'hst_emp_year_month_run_unique'
            );
        });

        // 5) Re-create FK(s) on employee_id with same rules as before
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

    public function down(): void
    {
        $table  = 'hr_salary_transactions';
        $schema = DB::getDatabaseName();

        // find & drop FKs again
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

        // drop new unique
        Schema::table($table, function (Blueprint $tbl) {
            $tbl->dropUnique('hst_emp_year_month_run_unique');
        });

        // restore old unique
        Schema::table($table, function (Blueprint $tbl) {
            $tbl->unique(
                ['employee_id', 'year', 'month'],
                'hr_salary_transactions_employee_year_month_unique'
            );
        });

        // (optional) drop helper index & column if you want to fully revert
        Schema::table($table, function (Blueprint $tbl) {
            if (Schema::hasColumn($tbl->getTable(), 'payroll_run_id')) {
                $tbl->dropIndex('hst_payroll_run_id_idx');
                $tbl->dropColumn('payroll_run_id');
            }
        });

        // recreate FKs
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
