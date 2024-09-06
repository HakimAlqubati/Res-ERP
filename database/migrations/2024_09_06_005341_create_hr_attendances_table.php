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
        Schema::create('hr_attendances', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('employee_id');  // Employee who checked in/out
            $table->enum('check_type', ['checkin', 'checkout']);  // Whether it's a check-in or check-out
            $table->time('check_time');  // Time of check-in or check-out
            $table->date('check_date');  // Date of check-in or check-out
            $table->string('location')->nullable();  // Location of check-in or check-out
            $table->boolean('is_manual')->default(false);  // Indicates if attendance was recorded manually
            $table->string('notes')->nullable();  // Notes for special circumstances
            $table->bigInteger('created_by');  // The user who recorded the attendance
            $table->bigInteger('updated_by')->nullable();  // The user who updated the record
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_attendances');
    }
};
