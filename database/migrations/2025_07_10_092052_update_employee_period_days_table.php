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
        // حذف الجدول إذا كان موجود
        Schema::dropIfExists('employee_period_days');

        // إنشاء الجدول الجديد
        Schema::create('employee_period_days', function (Blueprint $table) {
            $table->id();

            // علاقة مع جدول employee_periods
            $table->foreignId('employee_period_id')
                ->constrained('hr_employee_periods')
                ->onDelete('cascade');

            // يوم الأسبوع
            $table->string('day_of_week'); // أمثلة: Monday, Tuesday...

            // نطاق صلاحية اليوم
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->timestamps();

            // ضمان عدم تكرار اليوم لنفس الفترة
            $table->unique(['employee_period_id', 'day_of_week'], 'unique_period_day');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_period_days');
    }
};