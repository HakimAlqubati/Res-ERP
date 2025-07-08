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
        Schema::create('employee_period_days', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')
                ->constrained('hr_employees')
                ->onDelete('cascade');

            $table->foreignId('period_id')
                ->constrained('hr_work_periods')
                ->onDelete('cascade');

            $table->string('day_of_week', 10); // sun, mon, tue, ...

            $table->date('start_date')->nullable(); // تاريخ بداية صلاحية اليوم (اختياري)
            $table->date('end_date')->nullable();   // تاريخ نهاية الصلاحية (اختياري)

            $table->timestamps();

            $table->unique(['employee_id', 'period_id', 'day_of_week'], 'unique_employee_period_day');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_period_days');
    }
};