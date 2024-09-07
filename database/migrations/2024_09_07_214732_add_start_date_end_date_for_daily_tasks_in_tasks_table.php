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
            $table->date('start_date')->nullable()->after('due_date');
            $table->date('end_date')->nullable()->after('due_date');
        });
        Schema::table('daily_tasks_setting_up', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('assigned_to');
            $table->date('end_date')->nullable()->after('assigned_to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_tasks', function (Blueprint $table) {
            $table->dropColumn('start_date');
            $table->dropColumn('end_date_date');
        });
        Schema::table('daily_tasks_setting_up', function (Blueprint $table) {
            $table->dropColumn('start_date');
            $table->dropColumn('end_date_date');
        });
    }
};
