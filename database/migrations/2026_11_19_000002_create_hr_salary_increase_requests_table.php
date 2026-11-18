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
        Schema::create('hr_salary_increase_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();

            $table->decimal('old_salary', 12, 2);
            $table->string('increase_type', 20)->default('fixed'); // fixed, percentage, new_salary
            $table->decimal('increase_amount', 12, 2);
            $table->decimal('increase_percentage', 7, 2)->nullable();
            $table->decimal('new_salary', 12, 2);

            $table->date('effective_date')->nullable();
            $table->text('notes')->nullable();

            $table->string('status', 20)->default('pending'); // pending, approved, rejected
            $table->text('rejection_reason')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['employee_id', 'status'], 'hr_sir_emp_status_idx');
            $table->index(['branch_id', 'status'], 'hr_sir_branch_status_idx');
            $table->index(['status', 'created_at'], 'hr_sir_status_created_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_salary_increase_requests');
    }
};
