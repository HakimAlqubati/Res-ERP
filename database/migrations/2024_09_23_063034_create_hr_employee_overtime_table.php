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
        Schema::create('hr_employee_overtime', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('employee_id');
            
            $table->date('date'); 
            $table->time('start_time'); 
            $table->time('end_time'); 
            $table->decimal('hours', 5, 2); 
            $table->decimal('rate', 8, 2)->nullable(); 
            
            $table->string('reason')->nullable(); 
            $table->boolean('approved')->default(false); 
            $table->bigInteger('approved_by')->nullable();
            $table->text('notes')->nullable(); 
            
            $table->bigInteger('created_by');
            $table->bigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_employee_overtime');
    }
};
