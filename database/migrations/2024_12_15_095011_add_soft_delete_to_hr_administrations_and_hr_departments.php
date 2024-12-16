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
            $table->softDeletes(); // إضافة عمود deleted_at
        });

        // إضافة عمود deleted_at إلى جدول hr_departments
        Schema::table('hr_departments', function (Blueprint $table) {
            $table->softDeletes(); // إضافة عمود deleted_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // حذف عمود deleted_at من جدول hr_administrations
        Schema::table('hr_administrations', function (Blueprint $table) {
            $table->dropSoftDeletes(); // إزالة عمود deleted_at
        });

        // حذف عمود deleted_at من جدول hr_departments
        Schema::table('hr_departments', function (Blueprint $table) {
            $table->dropSoftDeletes(); // إزالة عمود deleted_at
        });
    }
};
