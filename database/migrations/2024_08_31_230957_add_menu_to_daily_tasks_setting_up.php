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
        Schema::table('daily_tasks_setting_up', function (Blueprint $table) {
            $table->json('menu_tasks')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_tasks_setting_up', function (Blueprint $table) {
            $table->dropColumn('menu_tasks');
        });
    }
};
