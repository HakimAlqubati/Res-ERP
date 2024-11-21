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
        Schema::create('hr_task_cards', function (Blueprint $table) {
            $table->id();  $table->unsignedBigInteger('task_id');
            $table->enum('type', ['red', 'yellow']);
            $table->unsignedBigInteger('employee_id');
            $table->boolean('active')->default(1);
            $table->timestamps();

            // Foreign key constraints (if applicable)
            $table->foreign('task_id')->references('id')->on('hr_tasks')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('hr_employees')->onDelete('cascade');
    
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_task_cards');
    }
};
