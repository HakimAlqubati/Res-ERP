<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_payrolls', function (Blueprint $table) {
            // Add FK to payroll_runs
            $table->foreignId('payroll_run_id')
                ->nullable()
                ->after('id')
                ->constrained('hr_payroll_runs');

            // Indexes to speed up common filters
            $table->index(['payroll_run_id']);
            $table->index(['branch_id', 'year', 'month']);

            // Prevent duplicate employee within the same run
            $table->unique(['payroll_run_id', 'employee_id'], 'uniq_run_employee');

            // Drop the "name" column from hr_payrolls
            if (Schema::hasColumn('hr_payrolls', 'name')) {
                $table->dropColumn('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hr_payrolls', function (Blueprint $table) {
            // Re-add "name" column if you ever rollback
            if (! Schema::hasColumn('hr_payrolls', 'name')) {
                $table->string('name')->nullable()->after('paid_at');
            }

            // Drop constraints/indexes
            $table->dropUnique('uniq_run_employee');
            $table->dropConstrainedForeignId('payroll_run_id');
            $table->dropIndex(['payroll_run_id']);
            $table->dropIndex(['branch_id', 'year', 'month']);
        });
    }
};
