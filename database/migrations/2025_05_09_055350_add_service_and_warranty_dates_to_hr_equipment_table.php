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
        Schema::table('hr_equipment', function (Blueprint $table) {
            $table->date('operation_start_date')->nullable()->after('last_serviced');
            $table->date('warranty_end_date')->nullable()->after('operation_start_date');
            $table->date('next_service_date')->nullable()->after('warranty_end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_equipment', function (Blueprint $table) {
            $table->dropColumn(['operation_start_date', 'warranty_end_date', 'next_service_date']);
        });
    }
};
