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
        Schema::table('hr_employee_overtime', function (Blueprint $table) {

            // Remove the existing unique constraint on employee_id and date
            $table->dropUnique('employee_date_unique');


            // Add the new unique constraint on employee_id, date, and type
            $table->unique(['employee_id', 'date', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_employee_overtime', function (Blueprint $table) {
            // Remove the new unique constraint on employee_id, date, and type
            $table->dropUnique(['employee_id', 'date', 'type']);

            // Re-add the original unique constraint on employee_id and date
            $table->unique(['employee_id', 'date']);
        });
    }
};
