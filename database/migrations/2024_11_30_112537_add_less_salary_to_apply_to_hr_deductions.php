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
        Schema::table('hr_deductions', function (Blueprint $table) {
            $table->float('less_salary_to_apply')->nullable()->after('nationalities_applied');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_deductions', function (Blueprint $table) {
            $table->dropColumn('less_salary_to_apply');
        });
    }
};
