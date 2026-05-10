<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_approval_steps', function (Blueprint $table) {
            $table->id();
            $table->morphs('approvable', 'hr_approval_steps_approvable_idx');
            $table->foreignId('approval_policy_id')->nullable()->constrained('hr_approval_policies')->nullOnDelete();
            $table->unsignedSmallInteger('step_order');
            $table->foreignId('approver_employee_id')->nullable()->constrained('hr_employees')->nullOnDelete();
            $table->foreignId('approver_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['approvable_type', 'approvable_id', 'step_order'], 'hr_approval_steps_order_unique');
            $table->index(['approvable_type', 'approvable_id', 'status', 'step_order'], 'hr_approval_steps_current_idx');
            $table->index(['approver_user_id', 'status'], 'hr_approval_steps_user_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_approval_steps');
    }
};
