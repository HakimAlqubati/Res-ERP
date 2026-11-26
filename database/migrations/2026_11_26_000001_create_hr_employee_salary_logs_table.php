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
        if (! Schema::hasTable('hr_employee_salary_logs')) {
            Schema::create('hr_employee_salary_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();

                $table->decimal('old_salary', 12, 2)->nullable();
                $table->decimal('new_salary', 12, 2);
                $table->decimal('change_amount', 12, 2)->nullable();

                $table->date('effective_from');
                $table->date('effective_date')->nullable();

                $table->string('source', 32)->default('direct_edit');

                if (Schema::hasTable('hr_salary_increase_requests')) {
                    $table->foreignId('salary_increase_request_id')
                        ->nullable()
                        ->constrained('hr_salary_increase_requests')
                        ->nullOnDelete();
                } else {
                    $table->unsignedBigInteger('salary_increase_request_id')->nullable();
                }

                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['employee_id', 'effective_from', 'id'], 'hr_esl_emp_eff_id_idx');
                $table->unique(['salary_increase_request_id'], 'hr_esl_request_unique');
            });
        }

        // Companion improvement 1: widen hr_employees.salary from decimal(8,2) to decimal(12,2)
        if (Schema::hasTable('hr_employees') && Schema::hasColumn('hr_employees', 'salary')) {
            Schema::table('hr_employees', function (Blueprint $table) {
                $table->decimal('salary', 12, 2)->nullable()->change();
            });
        }

        // Companion improvement 2: add (status, effective_date) index for PendingApplicationQuery
        if (
            Schema::hasTable('hr_salary_increase_requests')
            && Schema::hasColumn('hr_salary_increase_requests', 'effective_date')
            && ! Schema::hasIndex('hr_salary_increase_requests', 'hr_sir_status_eff_idx')
        ) {
            Schema::table('hr_salary_increase_requests', function (Blueprint $table) {
                $table->index(['status', 'effective_date'], 'hr_sir_status_eff_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (
            Schema::hasTable('hr_salary_increase_requests')
            && Schema::hasIndex('hr_salary_increase_requests', 'hr_sir_status_eff_idx')
        ) {
            Schema::table('hr_salary_increase_requests', function (Blueprint $table) {
                $table->dropIndex('hr_sir_status_eff_idx');
            });
        }

        Schema::dropIfExists('hr_employee_salary_logs');
    }
};
