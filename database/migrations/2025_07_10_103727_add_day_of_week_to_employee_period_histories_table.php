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
            $table->string('day_of_week')->nullable()->after('period_id');
        });
    }

    public function down(): void
    {
        Schema::table('hr_employee_period_histories', function (Blueprint $table) {
            $table->dropColumn('day_of_week');
        });
    }
};