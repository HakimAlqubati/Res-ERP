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
        Schema::table('hr_employee_period_histories', function (Blueprint $table) {
            $table->renameColumn('days', 'period_days');
        });

        Schema::table('hr_employee_periods', function (Blueprint $table) {
            $table->renameColumn('days', 'period_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_employee_period_histories', function (Blueprint $table) {
            $table->renameColumn('period_days', 'days');
        });

        Schema::table('hr_employee_periods', function (Blueprint $table) {
            $table->renameColumn('period_days', 'days');
        });
    }
};
