<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_approval_policy_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_policy_id')->constrained('hr_approval_policies')->cascadeOnDelete();
            $table->unsignedSmallInteger('step_order')->default(1);
            $table->string('approver_type');
            $table->foreignId('approver_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('manager_level')->nullable();
            $table->timestamps();

            $table->unique(['approval_policy_id', 'step_order'], 'hr_approval_policy_steps_order_unique');
            $table->index(['approval_policy_id', 'approver_type'], 'hr_approval_policy_steps_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_approval_policy_steps');
    }
};
