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
            $table->integer('delay_minutes')->nullable()->after('period_id');
            $table->integer('early_arrival_minutes')->nullable()->after('period_id');
            $table->integer('late_departure_minutes')->nullable()->after('period_id');
            $table->integer('early_departure_minutes')->nullable()->after('period_id');
            $table->enum('status', ['early_arrival', 'late_arrival', 'on_time', 'early_departure', 'late_departure'])->after('day')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_attendances', function (Blueprint $table) {
            $table->dropColumn(['delay_minutes', 'early_arrival_minutes',
                'late_departure_minutes', 'early_departure_minutes','status']);
        });
    }
};
