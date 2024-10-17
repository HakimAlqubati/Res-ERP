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
            $table->unique(['employee_id', 'date'], 'employee_date_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_employee_overtime', function (Blueprint $table) {
            $table->dropUnique('employee_date_unique');
        });
    }
};
