<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_approval_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('approvable_type');
            $table->unsignedBigInteger('application_type_id')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('mode');
            $table->unsignedSmallInteger('levels')->nullable();
            $table->json('custom_approver_user_ids')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['approvable_type', 'application_type_id', 'branch_id', 'active'], 'hr_approval_policies_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_approval_policies');
    }
};
