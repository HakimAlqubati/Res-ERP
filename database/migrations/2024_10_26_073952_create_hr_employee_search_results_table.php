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
        Schema::create('hr_employee_search_results', function (Blueprint $table) {
            $table->id();
            $table->string('image'); // Path to the uploaded image used for searching
            $table->unsignedBigInteger('employee_id')->nullable(); // ID of the matched employee, if found
            $table->float('similarity')->nullable(); // Similarity score
            $table->timestamps();

     
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_employee_search_results');
    }
};
