<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_approval_policy_steps', function (Blueprint $table) {
            if (! Schema::hasColumn('hr_approval_policy_steps', 'approver_role_id')) {
                $table->foreignId('approver_role_id')
                    ->nullable()
                    ->after('approver_user_id')
                    ->constrained('roles')
                    ->nullOnDelete();
            }
        });

        Schema::table('hr_approval_steps', function (Blueprint $table) {
            if (! Schema::hasColumn('hr_approval_steps', 'approver_role_id')) {
                $table->foreignId('approver_role_id')
                    ->nullable()
                    ->after('approver_user_id')
                    ->constrained('roles')
                    ->nullOnDelete();
            }
        });

        DB::statement('ALTER TABLE hr_approval_steps MODIFY approver_user_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        Schema::table('hr_approval_steps', function (Blueprint $table) {
            if (Schema::hasColumn('hr_approval_steps', 'approver_role_id')) {
                $table->dropForeign(['approver_role_id']);
                $table->dropColumn('approver_role_id');
            }
        });

        Schema::table('hr_approval_policy_steps', function (Blueprint $table) {
            if (Schema::hasColumn('hr_approval_policy_steps', 'approver_role_id')) {
                $table->dropForeign(['approver_role_id']);
                $table->dropColumn('approver_role_id');
            }
        });
    }
};
