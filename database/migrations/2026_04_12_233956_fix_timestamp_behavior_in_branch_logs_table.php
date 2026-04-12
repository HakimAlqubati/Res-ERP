<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration changes column types from 'timestamp' to 'dateTime' for 
     * the hr_employee_branch_logs table to avoid MySQL's automatic 
     * 'ON UPDATE CURRENT_TIMESTAMP' behavior which was altering start_at 
     * when end_at was updated.
     */
    public function up(): void
    {
        Schema::table('hr_employee_branch_logs', function (Blueprint $table) {
            $table->dateTime('start_at')->change();
            $table->dateTime('end_at')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_employee_branch_logs', function (Blueprint $table) {
            $table->timestamp('start_at')->change();
            $table->timestamp('end_at')->nullable()->change();
        });
    }
};
