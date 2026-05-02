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
        Schema::table('hr_employee_applications', function (Blueprint $table) {
            $table->boolean('is_auto_generated')->default(false)->after('status');
        });

        Schema::table('hr_missed_check_out_requests', function (Blueprint $table) {
            $table->boolean('is_auto_generated')->default(false)->after('reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_employee_applications', function (Blueprint $table) {
            $table->dropColumn('is_auto_generated');
        });

        Schema::table('hr_missed_check_out_requests', function (Blueprint $table) {
            $table->dropColumn('is_auto_generated');
        });
    }
};
