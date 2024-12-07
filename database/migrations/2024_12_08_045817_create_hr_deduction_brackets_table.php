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
        Schema::create('hr_deduction_brackets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deduction_id')->constrained('hr_deductions')->onDelete('cascade');  
            $table->decimal('min_amount', 15, 2);  
            $table->decimal('max_amount', 15, 2);  
            $table->decimal('percentage', 5, 2);  
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_deduction_brackets');
    }
};
