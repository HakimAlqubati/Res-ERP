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
        Schema::create('hr_work_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();  // Unique work period name
            $table->text('description')->nullable();  // Optional description
            $table->boolean('active')->default(1);  // Boolean to check if active, default is true
            $table->time('start_at');  // Starting time of the work period
            $table->time('end_at');  // Ending time of the work period
            $table->integer('allowed_count_minutes_late')->nullable();  // Minutes allowed for late arrivals
            $table->json('days');  // Days as JSON to store the work days for the period
            $table->bigInteger('created_by');  // The user who created the period
            $table->bigInteger('updated_by')->nullable();  // The user who updated the period
            $table->softDeletes();  // Soft delete column to keep track of deleted periods
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_work_periods');
    }
};
