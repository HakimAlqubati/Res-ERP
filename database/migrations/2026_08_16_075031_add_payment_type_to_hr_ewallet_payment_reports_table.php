<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('hr_ewallet_payment_reports', function (Blueprint $table) {
            $table->string('payment_type')->default('ewallet')->after('status');
        });

        // Update existing records to 'ewallet'
        DB::table('hr_ewallet_payment_reports')->whereNull('payment_type')->orWhere('payment_type', '')->update(['payment_type' => 'ewallet']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_ewallet_payment_reports', function (Blueprint $table) {
            $table->dropColumn('payment_type');
        });
    }
};
