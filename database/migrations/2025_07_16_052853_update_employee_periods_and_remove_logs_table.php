<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
      public function up()
    {
        // حذف عمود period_days من جدول hr_employee_periods
        if (Schema::hasColumn('hr_employee_periods', 'period_days')) {
            Schema::table('hr_employee_periods', function (Blueprint $table) {
                $table->dropColumn('period_days');
            });
        }

        // حذف عمود period_days من جدول hr_employee_period_histories
        if (Schema::hasColumn('hr_employee_period_histories', 'period_days')) {
            Schema::table('hr_employee_period_histories', function (Blueprint $table) {
                $table->dropColumn('period_days');
            });
        }

        // حذف جدول hr_employee_period_logs
        Schema::dropIfExists('hr_employee_period_logs');
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // إعادة عمود period_days إلى جدول hr_employee_periods
        Schema::table('hr_employee_periods', function (Blueprint $table) {
            $table->json('period_days')->nullable();
        });

        // إعادة عمود period_days إلى جدول hr_employee_period_histories
        Schema::table('hr_employee_period_histories', function (Blueprint $table) {
            $table->json('period_days')->nullable();
        });

        // إعادة إنشاء جدول hr_employee_period_logs (عدّل الأعمدة بحسب ما كان لديك)
        Schema::create('hr_employee_period_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_period_id');
            $table->string('action');
            $table->json('data')->nullable();
            $table->timestamps();
        });
    }
};