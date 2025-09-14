<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        $schema = DB::getDatabaseName();
        $childTable = 'hr_salary_transactions';
        $parentTable = 'hr_payroll_runs';
        $fkName = 'hr_salary_transactions_payroll_run_id_foreign';

        // 1) Detect parent id type (int unsigned OR bigint unsigned)
        $ref = DB::table('information_schema.columns')
            ->select('COLUMN_TYPE')
            ->where('TABLE_SCHEMA', $schema)
            ->where('TABLE_NAME', $parentTable)
            ->where('COLUMN_NAME', 'id')
            ->first();

        if (!$ref) {
            throw new \RuntimeException("Table `$parentTable` or column `id` not found.");
        }

        $colType = strtolower($ref->COLUMN_TYPE); // e.g. "int(10) unsigned" or "bigint(20) unsigned"
        $targetSql = str_contains($colType, 'bigint') ? 'BIGINT UNSIGNED' : 'INT UNSIGNED';

        // 2) Make sure both tables are InnoDB (optional but recommended)
        foreach ([$childTable, $parentTable] as $t) {
            $engine = DB::table('information_schema.tables')
                ->where('TABLE_SCHEMA', $schema)
                ->where('TABLE_NAME', $t)
                ->value('ENGINE');
            if (strtolower((string)$engine) !== 'innodb') {
                DB::statement("ALTER TABLE `$t` ENGINE=InnoDB");
            }
        }

        // 3) Align child column type to parent
        //    Ensure column exists first
        if (!Schema::hasColumn($childTable, 'payroll_run_id')) {
            // If missing (unlikely per رسالتك)، أنشئه بالصيغة الصحيحة
            DB::statement("ALTER TABLE `$childTable` ADD `payroll_run_id` $targetSql NULL AFTER `payroll_id`");
        } else {
            // Otherwise modify to match EXACT integer family and unsigned
            DB::statement("ALTER TABLE `$childTable` MODIFY `payroll_run_id` $targetSql NULL");
        }

        // 4) Drop existing FK (if any) to avoid duplicates
        $fkExists = DB::table('information_schema.REFERENTIAL_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', $schema)
            ->where('CONSTRAINT_NAME', $fkName)
            ->exists();
        if ($fkExists) {
            DB::statement("ALTER TABLE `$childTable` DROP FOREIGN KEY `$fkName`");
        }

        // 5) Add index for the FK column (if not exists)
        $idxName = 'hst_payroll_run_id_idx';
        $idxExists = DB::table('information_schema.statistics')
            ->where('TABLE_SCHEMA', $schema)
            ->where('TABLE_NAME', $childTable)
            ->where('INDEX_NAME', $idxName)
            ->exists();
        if (!$idxExists) {
            DB::statement("ALTER TABLE `$childTable` ADD INDEX `$idxName` (`payroll_run_id`)");
        }

        // 6) Create the FK with proper rules
        DB::statement("
            ALTER TABLE `$childTable`
            ADD CONSTRAINT `$fkName`
            FOREIGN KEY (`payroll_run_id`) REFERENCES `$parentTable`(`id`)
            ON DELETE SET NULL ON UPDATE CASCADE
        ");
    }

    public function down(): void
    {
        $schema = DB::getDatabaseName();
        $childTable = 'hr_salary_transactions';
        $fkName = 'hr_salary_transactions_payroll_run_id_foreign';
        $idxName = 'hst_payroll_run_id_idx';

        // Drop FK if exists
        $fkExists = DB::table('information_schema.REFERENTIAL_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', $schema)
            ->where('CONSTRAINT_NAME', $fkName)
            ->exists();
        if ($fkExists) {
            DB::statement("ALTER TABLE `$childTable` DROP FOREIGN KEY `$fkName`");
        }

        // Drop index if exists
        $idxExists = DB::table('information_schema.statistics')
            ->where('TABLE_SCHEMA', $schema)
            ->where('TABLE_NAME', $childTable)
            ->where('INDEX_NAME', $idxName)
            ->exists();
        if ($idxExists) {
            DB::statement("ALTER TABLE `$childTable` DROP INDEX `$idxName`");
        }

        // لا نحذف العمود تلقائيًا (قد يكون مستخدمًا في مكان آخر)
        // لو تريد حذفه، أضف التالي:
        // if (Schema::hasColumn($childTable, 'payroll_run_id')) {
        //     DB::statement("ALTER TABLE `$childTable` DROP COLUMN `payroll_run_id`");
        // }
    }
};
