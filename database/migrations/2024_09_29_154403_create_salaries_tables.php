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
        Schema::create('hr_month_salaries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('month'); // The month of the salary (YYYY-MM format)
            $table->date('start_month'); // The starting date of the salary month
            $table->date('end_month'); // The ending date of the salary month
            $table->text('notes')->nullable(); // Additional notes
            $table->date('payment_date'); // The date when payment will be made
            $table->boolean('approved')->default(false); // Whether the salary is approved
            $table->timestamps();
        });

        Schema::create('hr_month_salary_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('month_salary_id')->constrained('hr_month_salaries')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('hr_employees')->onDelete('cascade');
            $table->decimal('basic_salary', 10, 2); // Employee’s basic salary
            $table->decimal('total_deductions', 10, 2)->default(0); // Total deductions
            $table->decimal('total_allowances', 10, 2)->default(0); // Total allowances
            $table->decimal('total_incentives', 10, 2)->default(0); // Total incentives
            $table->decimal('overtime_hours', 5, 2)->default(0); // Overtime hours worked
            $table->decimal('overtime_pay', 10, 2)->default(0); // Pay for overtime hours
            $table->decimal('net_salary', 10, 2); // Final net salary
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_month_salaries');
        Schema::dropIfExists('hr_month_salary_details');
    }
};
