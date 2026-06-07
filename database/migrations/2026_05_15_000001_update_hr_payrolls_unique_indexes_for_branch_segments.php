<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexIfMissing('hr_payrolls', 'hp_employee_fk_idx', ['employee_id']);
        $this->addIndexIfMissing('hr_payrolls', 'hp_branch_fk_idx', ['branch_id']);

        Schema::table('hr_payrolls', function (Blueprint $table) {
            if ($this->indexExists('hr_payrolls', 'uniq_run_employee')) {
                $table->dropUnique('uniq_run_employee');
            }

            if ($this->indexExists('hr_payrolls', 'unique_payroll_employee_month')) {
                $table->dropUnique('unique_payroll_employee_month');
            }

            if (! $this->indexExists('hr_payrolls', 'uniq_run_employee_branch_period')) {
                $table->unique(
                    ['payroll_run_id', 'employee_id', 'branch_id', 'period_start_date'],
                    'uniq_run_employee_branch_period'
                );
            }

            if (! $this->indexExists('hr_payrolls', 'uniq_payroll_employee_branch_month_period')) {
                $table->unique(
                    ['employee_id', 'branch_id', 'year', 'month', 'period_start_date'],
                    'uniq_payroll_employee_branch_month_period'
                );
            }
        });
    }

    public function down(): void
    {
        Schema::table('hr_payrolls', function (Blueprint $table) {
            if ($this->indexExists('hr_payrolls', 'uniq_run_employee_branch_period')) {
                $table->dropUnique('uniq_run_employee_branch_period');
            }

            if ($this->indexExists('hr_payrolls', 'uniq_payroll_employee_branch_month_period')) {
                $table->dropUnique('uniq_payroll_employee_branch_month_period');
            }

            if (! $this->indexExists('hr_payrolls', 'uniq_run_employee')) {
                $table->unique(['payroll_run_id', 'employee_id'], 'uniq_run_employee');
            }

            if (! $this->indexExists('hr_payrolls', 'unique_payroll_employee_month')) {
                $table->unique(
                    ['employee_id', 'branch_id', 'year', 'month'],
                    'unique_payroll_employee_month'
                );
            }
        });
    }

    private function addIndexIfMissing(string $table, string $index, array $columns): void
    {
        if ($this->indexExists($table, $index)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($index, $columns) {
            $blueprint->index($columns, $index);
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        return ! empty(DB::select(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? LIMIT 1',
            [$table, $index]
        ));
    }
};
