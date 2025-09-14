<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $table  = 'hr_salary_transactions';
        $schema = DB::getDatabaseName();

        // 0) Ensure payroll_run_id exists BEFORE creating the new unique
        if (!Schema::hasColumn($table, 'payroll_run_id')) {
            Schema::table($table, function (Blueprint $tbl) {
                $tbl->unsignedBigInteger('payroll_run_id')->default(0)->after('month');
                // optional helper index
                $tbl->index('payroll_run_id', 'hst_payroll_run_id_idx');
            });
        }

        // 1) Collect FK(s) on employee_id to drop & recreate safely
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

        // 2) Drop FK(s) first (required to change indexes they rely on)
        foreach ($fkRows as $fk) {
            DB::statement("ALTER TABLE `$table` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
        }

        // 3) Find the actual UNIQUE index on (employee_id, year, month) — drop it ONLY if it exists
        $targetColumns = ['employee_id', 'year', 'month'];

        $uniqueGroups = DB::table('information_schema.statistics')
            ->select('INDEX_NAME', 'COLUMN_NAME', 'SEQ_IN_INDEX')
            ->where('TABLE_SCHEMA', $schema)
            ->where('TABLE_NAME', $table)
            ->where('NON_UNIQUE', 0)
            ->where('INDEX_NAME', '!=', 'PRIMARY')
            ->orderBy('INDEX_NAME')
            ->orderBy('SEQ_IN_INDEX')
            ->get()
            ->groupBy('INDEX_NAME');

        foreach ($uniqueGroups as $indexName => $rows) {
            $cols = $rows->sortBy('SEQ_IN_INDEX')->pluck('COLUMN_NAME')->values()->all();
            if ($cols === $targetColumns) {
                DB::statement("ALTER TABLE `$table` DROP INDEX `{$indexName}`");
            }
        }

        // 4) Create the new desired unique (skip if it already exists)
        $newIndexName = 'hst_emp_year_month_run_unique';
        $newIndexExists = DB::table('information_schema.statistics')
            ->where('TABLE_SCHEMA', $schema)
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $newIndexName)
            ->exists();

        if (!$newIndexExists) {
            Schema::table($table, function (Blueprint $tbl) use ($newIndexName) {
                $tbl->unique(
                    ['employee_id', 'year', 'month', 'payroll_run_id'],
                    $newIndexName
                );
            });
        }

        // 5) Re-create FK(s) with original names and rules
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

        // Drop FKs to allow index changes
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

        // Drop the new unique if present
        $newIndexName = 'hst_emp_year_month_run_unique';
        $exists = DB::table('information_schema.statistics')
            ->where('TABLE_SCHEMA', $schema)
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $newIndexName)
            ->exists();

        if ($exists) {
            Schema::table($table, function (Blueprint $tbl) use ($newIndexName) {
                $tbl->dropUnique($newIndexName);
            });
        }

        // Re-create the old unique (canonical name)
        Schema::table($table, function (Blueprint $tbl) {
            $tbl->unique(
                ['employee_id', 'year', 'month'],
                'hr_salary_transactions_employee_year_month_unique'
            );
        });

        // (optional) remove helper index/column if you want full rollback
        if (Schema::hasColumn($table, 'payroll_run_id')) {
            Schema::table($table, function (Blueprint $tbl) {
                // drop helper idx if exists
                try { $tbl->dropIndex('hst_payroll_run_id_idx'); } catch (\Throwable $e) {}
                $tbl->dropColumn('payroll_run_id');
            });
        }

        // Recreate FKs back
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
