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
        Schema::table('hr_month_salary_details', function (Blueprint $table) {
            $table->float('total_absent_days')->nullable()->after('net_salary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_month_salary_details', function (Blueprint $table) {
            $table->dropColumn('total_absent_days');
        });
    }
};
