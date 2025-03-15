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
        Schema::table('hr_employee_periods', function (Blueprint $table) {
            $table->json('days')->nullable()->after('period_id'); // Adding 'days' column as JSON
        });

        Schema::table('hr_employee_period_histories', function (Blueprint $table) {
            $table->json('days')->nullable()->after('period_id'); // Adding 'days' column as JSON
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_employee_periods', function (Blueprint $table) {
            $table->dropColumn('days'); // Remove 'days' column
        });

        Schema::table('hr_employee_period_histories', function (Blueprint $table) {
            $table->dropColumn('days'); // Remove 'days' column
        });
    }
};
