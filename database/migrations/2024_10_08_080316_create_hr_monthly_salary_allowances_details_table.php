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
        Schema::create('hr_monthly_salary_allowances_details', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('month_salary_id'); 
            $table->bigInteger('employee_id'); 
            $table->boolean('is_specific_employee')->default(false); 
            $table->bigInteger('allowance_id'); 
            $table->string('allowance_name'); 
            $table->decimal('allowance_amount', 10, 2); 
            $table->boolean('is_percentage')->default(false); 
            $table->decimal('amount_value', 10, 2)->nullable(); 
            $table->decimal('percentage_value', 5, 2)->nullable(); 
            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_monthly_salary_allowances_details');
    }
};
