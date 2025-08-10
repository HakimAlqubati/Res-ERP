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

        // 0) تأكد أن العمود موجود (لا تنشئه هنا لأن الخطأ يقول إنه موجود)
        if (!Schema::hasColumn($table, 'payroll_run_id')) {
            // لو فعلاً مفقود عندك (نادر بعد الخطأ)، افتح هذا البلوك وأضِفه.
            Schema::table($table, function (Blueprint $t) {
                $t->foreignId('payroll_run_id')->nullable()->after('payroll_id');
            });
        }

        // 1) أضف FK فقط إذا غير موجود
        $fkName = 'hr_salary_transactions_payroll_run_id_foreign';
        $fkExists = DB::table('information_schema.REFERENTIAL_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', $schema)
            ->where('CONSTRAINT_NAME', $fkName)
            ->exists();

        if (!$fkExists) {
            Schema::table($table, function (Blueprint $t) {
                $t->foreign('payroll_run_id')
                  ->references('id')->on('hr_payroll_runs')
                  ->nullOnDelete()      // ON DELETE SET NULL
                  ->cascadeOnUpdate();  // ON UPDATE CASCADE
            });
        }

        // 2) أضف الفهارس فقط إذا غير موجودة
        $idxRunType = 'hst_run_type_idx';
        $idxEmpYM   = 'hst_emp_year_month_idx';

        $indexExists = fn(string $idx) => DB::table('information_schema.statistics')
            ->where('TABLE_SCHEMA', $schema)
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $idx)
            ->exists();

        if (!$indexExists($idxRunType)) {
            Schema::table($table, function (Blueprint $t) use ($idxRunType) {
                $t->index(['payroll_run_id', 'type'], $idxRunType);
            });
        }

        if (!$indexExists($idxEmpYM)) {
            Schema::table($table, function (Blueprint $t) use ($idxEmpYM) {
                $t->index(['employee_id', 'year', 'month'], $idxEmpYM);
            });
        }

        // (اختياري) إن أردت unique على employee_id,year,month,payroll_run_id:
        // $uniqueName = 'hst_emp_year_month_run_unique';
        // $hasUnique  = DB::table('information_schema.statistics')
        //     ->where('TABLE_SCHEMA', $schema)->where('TABLE_NAME', $table)
        //     ->where('INDEX_NAME', $uniqueName)->exists();
        // if (!$hasUnique) {
        //     Schema::table($table, function (Blueprint $t) use ($uniqueName) {
        //         $t->unique(['employee_id','year','month','payroll_run_id'], $uniqueName);
        //     });
        // }
    }

    public function down(): void
    {
        $table  = 'hr_salary_transactions';
        $schema = DB::getDatabaseName();

        $fkName      = 'hr_salary_transactions_payroll_run_id_foreign';
        $idxRunType  = 'hst_run_type_idx';
        $idxEmpYM    = 'hst_emp_year_month_idx';
        // $uniqueName  = 'hst_emp_year_month_run_unique';

        // Drop FK if exists
        $fkExists = DB::table('information_schema.REFERENTIAL_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', $schema)
            ->where('CONSTRAINT_NAME', $fkName)
            ->exists();
        if ($fkExists) {
            Schema::table($table, function (Blueprint $t) use ($fkName) {
                $t->dropForeign($fkName);
            });
        }

        // Drop indexes if exist
        $indexExists = fn(string $idx) => DB::table('information_schema.statistics')
            ->where('TABLE_SCHEMA', $schema)
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $idx)
            ->exists();

        if ($indexExists($idxRunType)) {
            Schema::table($table, function (Blueprint $t) use ($idxRunType) {
                $t->dropIndex($idxRunType);
            });
        }
        if ($indexExists($idxEmpYM)) {
            Schema::table($table, function (Blueprint $t) use ($idxEmpYM) {
                $t->dropIndex($idxEmpYM);
            });
        }

        // (اختياري) لو أنشأت الـ unique
        // if ($indexExists($uniqueName)) {
        //     Schema::table($table, function (Blueprint $t) use ($uniqueName) {
        //         $t->dropUnique($uniqueName);
        //     });
        // }

        // لا نحذف العمود لأنّه كان موجود مسبقًا.
        // لو تريد حذفه: تأكد أولًا أن هذا الميجريشن هو من أضافه عندك.
        // if (Schema::hasColumn($table, 'payroll_run_id')) {
        //     Schema::table($table, function (Blueprint $t) {
        //         $t->dropColumn('payroll_run_id');
        //     });
        // }
    }
};
