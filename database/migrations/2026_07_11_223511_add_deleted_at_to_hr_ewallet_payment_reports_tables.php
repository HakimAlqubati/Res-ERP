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
        Schema::table('hr_ewallet_payment_reports', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('hr_ewallet_payment_report_items', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_ewallet_payment_report_items', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('hr_ewallet_payment_reports', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
