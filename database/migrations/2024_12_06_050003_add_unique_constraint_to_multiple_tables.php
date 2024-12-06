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
        // Add unique constraint to 'hr_missed_check_out_requests' table
        Schema::table('hr_missed_check_out_requests', function (Blueprint $table) {
            $table->unique(['application_id', 'employee_id']);
        });

        // Add unique constraint to 'hr_missed_check_in_requests' table
        Schema::table('hr_missed_check_in_requests', function (Blueprint $table) {
            $table->unique(['application_id', 'employee_id']);
        });

        // Add unique constraint to 'hr_leave_requests' table
        Schema::table('hr_leave_requests', function (Blueprint $table) {
            $table->unique(['application_id', 'employee_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop unique constraint from 'hr_missed_check_out_requests' table
        Schema::table('hr_missed_check_out_requests', function (Blueprint $table) {
            $table->dropUnique(['application_id', 'employee_id']);
        });

        // Drop unique constraint from 'hr_missed_check_in_requests' table
        Schema::table('hr_missed_check_in_requests', function (Blueprint $table) {
            $table->dropUnique(['application_id', 'employee_id']);
        });

        // Drop unique constraint from 'hr_leave_requests' table
        Schema::table('hr_leave_requests', function (Blueprint $table) {
            $table->dropUnique(['application_id', 'employee_id']);
        });
    }
};
