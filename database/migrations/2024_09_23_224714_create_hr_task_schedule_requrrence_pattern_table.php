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
        Schema::create('hr_task_schedule_requrrence_pattern', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('task_id');
            $table->string('schedule_type'); 
            $table->date('start_date')->nullable();
            $table->integer('recur_count'); 
            $table->date('end_date'); 
            $table->json('recurrence_pattern'); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_task_schedule_requrrence_pattern');
    }
};
