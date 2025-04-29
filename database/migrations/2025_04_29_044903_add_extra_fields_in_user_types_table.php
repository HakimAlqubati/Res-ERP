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
            $table->boolean('can_manage_stores')->default(false)->after('active');
            $table->boolean('can_manage_branches')->default(false)->after('can_manage_stores');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_types', function (Blueprint $table) {
            $table->dropColumn(['can_manage_stores', 'can_manage_branches']);
        });
    }
};
