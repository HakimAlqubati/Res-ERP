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
        Schema::create('hr_employee_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id'); // Foreign key to employees table
            $table->bigInteger('file_type_id'); // Foreign key to hr_employee_file_types table
            $table->string('attachment')->nullable(); // File attachment
            $table->boolean('active')->default(true); // Active status (default is true)
            $table->text('description')->nullable(); // Description of the file
          

            // Foreign key constraints
            $table->foreign('employee_id')->references('id')->on('hr_employees')->onDelete('cascade'); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_employee_files');
    }
};
