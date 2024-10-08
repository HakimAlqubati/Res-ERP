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
        Schema::table('hr_month_salaries', function (Blueprint $table) {
            $table->string('month', 7)->after('name')->comment('Format: YYYY-MM');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_month_salaries', function (Blueprint $table) {
            $table->dropColumn('month');
        });
    }
};