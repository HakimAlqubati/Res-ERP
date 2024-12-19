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
            $table->unsignedBigInteger('parent_id')->nullable()->after('active');
        });


        Schema::table('hr_departments', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->nullable()->after('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_administrations', function (Blueprint $table) {
            $table->dropColumn('parent_id');
        });

        // حذف عمود parent_id من جدول hr_departments
        Schema::table('hr_departments', function (Blueprint $table) {
            $table->dropColumn('parent_id');
        });
    }
};
