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
        Schema::create('hr_salary_transactions', function (Blueprint $table) {
            $table->id();
              $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('payroll_id')->nullable();
            $table->date('date');
            $table->decimal('amount', 15, 4);
            $table->string('currency', 6)->default('RM');
            $table->enum('type', [
                'salary', 'allowance', 'deduction', 'advance', 'installment', 'bonus', 'overtime', 'penalty', 'other'
            ]);
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_type')->nullable();
            $table->string('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('approved');
            $table->enum('operation', ['+', '-']);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('employee_id')->references('id')->on('hr_employees')->onDelete('cascade');
            $table->foreign('payroll_id')->references('id')->on('hr_month_salaries')->onDelete('set null');
     
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_salary_transactions');
    }
};