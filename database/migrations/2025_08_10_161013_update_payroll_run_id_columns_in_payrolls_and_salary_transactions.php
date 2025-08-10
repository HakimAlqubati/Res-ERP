<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema         = DB::getDatabaseName();
        $runsTable      = 'hr_payroll_runs';
        $payrollsTable  = 'hr_payrolls';
        $txTable        = 'hr_salary_transactions';

        $txFkName       = 'hr_salary_transactions_payroll_run_id_foreign';
        $payrollsFkName = 'hr_payrolls_payroll_run_id_foreign';

        // --- 0) Detect parent id type (INT UNSIGNED vs BIGINT UNSIGNED) ---
        $parentType = DB::table('information_schema.columns')
            ->where('TABLE_SCHEMA', $schema)
            ->where('TABLE_NAME', $runsTable)
            ->where('COLUMN_NAME', 'id')
            ->value('COLUMN_TYPE');

        if (!$parentType) {
            throw new \RuntimeException("Table `$runsTable` or column `id` not found.");
        }

        $parentType = strtolower((string) $parentType);
        $targetSql  = str_contains($parentType, 'bigint') ? 'BIGINT UNSIGNED' : 'INT UNSIGNED';

        // --- 1) Ensure columns exist and match parent integer family ---
        // hr_payrolls.payroll_run_id
        if (!Schema::hasColumn($payrollsTable, 'payroll_run_id')) {
            DB::statement("ALTER TABLE `$payrollsTable` ADD `payroll_run_id` $targetSql NULL");
        } else {
            DB::statement("ALTER TABLE `$payrollsTable` MODIFY `payroll_run_id` $targetSql NULL");
        }

        // hr_salary_transactions.payroll_run_id
        if (!Schema::hasColumn($txTable, 'payroll_run_id')) {
            DB::statement("ALTER TABLE `$txTable` ADD `payroll_run_id` $targetSql NULL");
        } else {
            DB::statement("ALTER TABLE `$txTable` MODIFY `payroll_run_id` $targetSql NULL");
        }

        // --- 2) Drop existing FKs that might block changes ---
        $dropFkIfExists = function (string $table, string $fkName) use ($schema) {
            $exists = DB::table('information_schema.REFERENTIAL_CONSTRAINTS')
                ->where('CONSTRAINT_SCHEMA', $schema)
                ->where('CONSTRAINT_NAME', $fkName)
                ->exists();
            if ($exists) {
                DB::statement("ALTER TABLE `$table` DROP FOREIGN KEY `$fkName`");
            }
        };

        $dropFkIfExists($txTable, $txFkName);
        $dropFkIfExists($payrollsTable, $payrollsFkName);

        // --- 3) Backfill transactions.payroll_run_id from payrolls.payroll_run_id via payroll_id ---
        // Make sure payrolls has values first (if your data model requires it, fill it separately)
        DB::statement("
            UPDATE `$txTable` tx
            JOIN `$payrollsTable` p ON p.id = tx.payroll_id
            SET tx.payroll_run_id = p.payroll_run_id
            WHERE tx.payroll_run_id IS NULL
        ");

        // You may also backfill payrolls.payroll_run_id here if you have a rule to derive it.
        // For example, set to a default run or infer from another relation.

        // --- 4) Guard: abort if NULLs remain before enforcing NOT NULL ---
        $txNulls  = (int) DB::table($txTable)->whereNull('payroll_run_id')->count();
        $pNulls   = (int) DB::table($payrollsTable)->whereNull('payroll_run_id')->count();

        if ($txNulls > 0) {
            throw new \RuntimeException("Backfill failed: $txNulls rows in `$txTable` still have NULL `payroll_run_id`.");
        }
        if ($pNulls > 0) {
            throw new \RuntimeException("Backfill failed: $pNulls rows in `$payrollsTable` still have NULL `payroll_run_id`.");
        }

        // --- 5) Enforce NOT NULL after successful backfill ---
        DB::statement("ALTER TABLE `$payrollsTable` MODIFY `payroll_run_id` $targetSql NOT NULL");
        DB::statement("ALTER TABLE `$txTable`       MODIFY `payroll_run_id` $targetSql NOT NULL");

        // --- 6) Recreate FKs with CASCADE rules ---
        DB::statement("
            ALTER TABLE `$payrollsTable`
            ADD CONSTRAINT `$payrollsFkName`
            FOREIGN KEY (`payroll_run_id`) REFERENCES `$runsTable`(`id`)
            ON DELETE CASCADE ON UPDATE CASCADE
        ");

        DB::statement("
            ALTER TABLE `$txTable`
            ADD CONSTRAINT `$txFkName`
            FOREIGN KEY (`payroll_run_id`) REFERENCES `$runsTable`(`id`)
            ON DELETE CASCADE ON UPDATE CASCADE
        ");

        // --- 7) Optional indexes (safe to add if missing) ---
        $addIndexIfMissing = function (string $table, string $index, array $cols) use ($schema) {
            $exists = DB::table('information_schema.statistics')
                ->where('TABLE_SCHEMA', $schema)
                ->where('TABLE_NAME', $table)
                ->where('INDEX_NAME', $index)
                ->exists();
            if (!$exists) {
                DB::statement("ALTER TABLE `$table` ADD INDEX `$index` (`" . implode('`,`', $cols) . "`)");
            }
        };

        $addIndexIfMissing($txTable, 'hst_payroll_run_id_idx', ['payroll_run_id']);
        $addIndexIfMissing($payrollsTable, 'hp_payroll_run_id_idx', ['payroll_run_id']);
    }

    public function down(): void
    {
        $schema         = DB::getDatabaseName();
        $runsTable      = 'hr_payroll_runs';
        $payrollsTable  = 'hr_payrolls';
        $txTable        = 'hr_salary_transactions';

        $txFkName       = 'hr_salary_transactions_payroll_run_id_foreign';
        $payrollsFkName = 'hr_payrolls_payroll_run_id_foreign';

        // Drop FKs if exist
        foreach ([[$txTable, $txFkName], [$payrollsTable, $payrollsFkName]] as [$table, $fk]) {
            $exists = DB::table('information_schema.REFERENTIAL_CONSTRAINTS')
                ->where('CONSTRAINT_SCHEMA', $schema)
                ->where('CONSTRAINT_NAME', $fk)
                ->exists();
            if ($exists) {
                DB::statement("ALTER TABLE `$table` DROP FOREIGN KEY `$fk`");
            }
        }

        // Make columns nullable again (reverse)
        // Use BIGINT UNSIGNED here; adjust if your parent is INT UNSIGNED.
        DB::statement("ALTER TABLE `$txTable`       MODIFY `payroll_run_id` BIGINT UNSIGNED NULL");
        DB::statement("ALTER TABLE `$payrollsTable` MODIFY `payroll_run_id` BIGINT UNSIGNED NULL");

        // Recreate a SET NULL FK on transactions (optional, matches your previous behavior)
        DB::statement("
            ALTER TABLE `$txTable`
            ADD CONSTRAINT `$txFkName`
            FOREIGN KEY (`payroll_run_id`) REFERENCES `$runsTable`(`id`)
            ON DELETE SET NULL ON UPDATE CASCADE
        ");

        // Drop optional indexes (optional in down)
        $dropIndexIfExists = function (string $table, string $index) use ($schema) {
            $exists = DB::table('information_schema.statistics')
                ->where('TABLE_SCHEMA', $schema)
                ->where('TABLE_NAME', $table)
                ->where('INDEX_NAME', $index)
                ->exists();
            if ($exists) {
                DB::statement("ALTER TABLE `$table` DROP INDEX `$index`");
            }
        };
        $dropIndexIfExists($txTable, 'hst_payroll_run_id_idx');
        $dropIndexIfExists($payrollsTable, 'hp_payroll_run_id_idx');

        // Note: We do not drop the columns in down() to avoid data loss.
        // If you need to drop them, uncomment:
        // DB::statement("ALTER TABLE `$txTable` DROP COLUMN `payroll_run_id`");
        // DB::statement("ALTER TABLE `$payrollsTable` DROP COLUMN `payroll_run_id`");
    }
};
