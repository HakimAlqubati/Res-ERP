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
        Schema::table('hr_administrations', function (Blueprint $table) {
            $table->boolean('is_global')->default(true)->after('description');
            $table->dropColumn('status');
            $table->boolean('active')->default(true)->after('is_global')->default(true);
        });


        Schema::table('hr_departments', function (Blueprint $table) {
            $table->boolean('is_global')->default(true)->after('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_administrations', function (Blueprint $table) {
            $table->dropColumn('is_global');
        });

        Schema::table('hr_departments', function (Blueprint $table) {
            $table->dropColumn('is_global');
        });
    }
};
