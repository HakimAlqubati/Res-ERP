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
        Schema::create('hr_employee_file_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Name of the file type
            $table->text('description')->nullable(); // Description of the file type
            $table->boolean('active')->default(true); // Active status (default is true)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_employee_file_types');
    }
};
