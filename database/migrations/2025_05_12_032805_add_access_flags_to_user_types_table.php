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
        Schema::table('user_types', function (Blueprint $table) {
            $table->boolean('can_access_all_branches')->default(false);
            $table->boolean('can_access_all_stores')->default(false);
            $table->boolean('can_access_non_branch_data')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_types', function (Blueprint $table) {
            $table->dropColumn([
                'can_access_all_branches',
                'can_access_all_stores',
                'can_access_non_branch_data',
            ]);
        });
    }
};
