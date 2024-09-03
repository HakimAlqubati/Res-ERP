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
        Schema::create('hr_task_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('hr_tasks')->onDelete('cascade'); 
            $table->string('title'); 
            $table->boolean('done')->default(0); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_task_steps');
    }
};
