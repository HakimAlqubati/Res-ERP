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
        Schema::create('hr_holidays', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Unique holiday name
            $table->date('from_date'); // Start date of the holiday
            $table->date('to_date'); // End date of the holiday
            $table->integer('count_days'); // Total number of days of the holiday
            $table->boolean('active')->default(1); // Active status
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
        Schema::dropIfExists('hr_holidays');
    }
};
