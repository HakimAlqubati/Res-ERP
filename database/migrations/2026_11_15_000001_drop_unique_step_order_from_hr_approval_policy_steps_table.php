<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_approval_policy_steps', function (Blueprint $table) {
            $table->index(['approval_policy_id', 'step_order'], 'hr_approval_policy_steps_policy_order_idx');
            $table->dropUnique('hr_approval_policy_steps_order_unique');
        });
    }

    public function down(): void
    {
        Schema::table('hr_approval_policy_steps', function (Blueprint $table) {
            $table->unique(['approval_policy_id', 'step_order'], 'hr_approval_policy_steps_order_unique');
            $table->dropIndex('hr_approval_policy_steps_policy_order_idx');
        });
    }
};
