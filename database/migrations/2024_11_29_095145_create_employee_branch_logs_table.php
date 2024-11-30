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
        Schema::create('hr_employee_branch_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');  // Foreign key to the employee
            $table->unsignedBigInteger('branch_id');    // The branch the employee was assigned to
            $table->timestamp('start_at');               // When the employee started in the branch
            $table->timestamp('end_at')->nullable();    // When the employee left the branch (nullable)
            $table->integer('created_by');
            $table->timestamps();

            // Adding foreign keys (optional but recommended for referential integrity)
            $table->foreign('employee_id')->references('id')->on('hr_employees')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
      
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_employee_branch_logs');
    }
};
