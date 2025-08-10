<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('hr_salary_transactions', function (Blueprint $table) {
            $table->foreignId('payroll_run_id')
                ->nullable()
                ->after('payroll_id')
                ->constrained('hr_payroll_runs');

            $table->index(['payroll_run_id', 'type']);
            $table->index(['employee_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::table('hr_salary_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payroll_run_id');
            $table->dropIndex(['payroll_run_id', 'type']);
            $table->dropIndex(['employee_id', 'year', 'month']);
        });
    }
};
