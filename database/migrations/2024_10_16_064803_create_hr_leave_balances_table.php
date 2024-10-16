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
        Schema::create('hr_leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->onDelete('cascade');
            $table->foreignId('leave_type_id')->constrained('hr_leave_types')->onDelete('cascade');
            $table->integer('year'); 
            $table->integer('branch_id'); 
            $table->integer('balance')->default(0); 
            $table->bigInteger('created_by'); 
            $table->timestamps();
            
            // Optional: Add a unique constraint to prevent duplicate balances for the same employee, leave type, and year
            $table->unique(['employee_id', 'leave_type_id', 'year'], 'unique_leave_balance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_leave_balances');
    }
};
