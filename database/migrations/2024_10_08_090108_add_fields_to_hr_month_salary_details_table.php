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
            $table->decimal('total_late_hours', 10, 2)->nullable()->after('total_absent_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_month_salary_details', function (Blueprint $table) {
            $table->dropColumn('total_late_hours');
        });
    }
};
