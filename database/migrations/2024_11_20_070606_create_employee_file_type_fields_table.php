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
        Schema::create('hr_employee_file_type_fields', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('file_type_id'); // Foreign key to hr_employee_file_types
            $table->string('field_name'); // Dynamic field name (e.g., start_date, end_date)
            $table->string('field_type')->default('text'); // Field type (e.g., text, date, etc.)
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('file_type_id')->references('id')->on('hr_employee_file_types')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_file_type_fields');
    }
};
