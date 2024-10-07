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
        Schema::table('hr_allowances', function (Blueprint $table) {
            $table->boolean('is_percentage')->default(true)->after('active');
        });

        Schema::table('hr_deductions', function (Blueprint $table) {
            $table->boolean('is_percentage')->default(true)->after('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_allowances', function (Blueprint $table) {
            $table->dropColumn('is_percentage');
        });

        Schema::table('hr_deductions', function (Blueprint $table) {
            $table->dropColumn('is_percentage');
        });
    }
};
