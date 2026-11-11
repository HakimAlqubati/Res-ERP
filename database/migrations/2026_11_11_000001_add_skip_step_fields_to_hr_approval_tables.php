<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_approval_policies', function (Blueprint $table) {
            if (! Schema::hasColumn('hr_approval_policies', 'allow_skip_steps')) {
                $table->boolean('allow_skip_steps')
                    ->default(false)
                    ->after('active');
            }
        });

        Schema::table('hr_approval_steps', function (Blueprint $table) {
            if (! Schema::hasColumn('hr_approval_steps', 'can_be_skipped')) {
                $table->boolean('can_be_skipped')
                    ->default(false)
                    ->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hr_approval_steps', function (Blueprint $table) {
            if (Schema::hasColumn('hr_approval_steps', 'can_be_skipped')) {
                $table->dropColumn('can_be_skipped');
            }
        });

        Schema::table('hr_approval_policies', function (Blueprint $table) {
            if (Schema::hasColumn('hr_approval_policies', 'allow_skip_steps')) {
                $table->dropColumn('allow_skip_steps');
            }
        });
    }
};
