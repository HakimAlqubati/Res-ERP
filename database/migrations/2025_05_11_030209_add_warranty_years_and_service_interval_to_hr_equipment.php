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
            $table->unsignedInteger('warranty_years')->default(0)->after('purchase_price');
            $table->unsignedInteger('service_interval_days')->default(0)->after('periodic_service');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_equipment', function (Blueprint $table) {
            $table->dropColumn(['warranty_years', 'service_interval_days']);
        });
    }
};
