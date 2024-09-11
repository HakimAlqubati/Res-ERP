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
        Schema::table('hr_tasks', function (Blueprint $table) {
            $table->enum('schedule_type',['daily','weekly','monthly'])->nullable()->after('is_daily');
        });
        Schema::table('daily_tasks_setting_up', function (Blueprint $table) {
            $table->enum('schedule_type',['daily','weekly','monthly'])->after('assigned_to')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_tasks', function (Blueprint $table) {
            $table->dropColumn('schedule_type');
        });
        Schema::table('daily_tasks_setting_up', function (Blueprint $table) {
            $table->dropColumn('schedule_type');
        });
    }
};
