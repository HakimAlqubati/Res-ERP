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
        Schema::create('hr_ewallet_payment_report_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hr_ewallet_payment_report_id')->constrained('hr_ewallet_payment_reports', 'id', 'fk_ewallet_report_id')->cascadeOnDelete();
            $table->foreignId('payroll_id')->constrained('hr_payrolls')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->string('account_number')->nullable();
            $table->decimal('net_salary', 15, 2)->default(0);
            $table->string('reward_name')->nullable();
            $table->string('reward_description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_ewallet_payment_report_items');
    }
};
