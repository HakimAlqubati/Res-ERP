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
        Schema::table('hr_attendances', function (Blueprint $table) {
            $table->boolean('is_from_previous_day')->default(0)->after('check_type'); // Boolean field, defaults to 0
            $table->time('total_actual_duration_hourly')->nullable()->after('actual_duration_hourly'); // Time field, nullable

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_attendances', function (Blueprint $table) {
            $table->dropColumn('is_from_previous_day');
            $table->dropColumn('total_actual_duration_hourly');
        });
    }
};
