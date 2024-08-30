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
        Schema::create('employee_profile', function (Blueprint $table) {
            $table->id();
            $table->string('job_title')->nullable();
            $table->bigInteger('employee_id');
            $table->string('employee_no');
            $table->string('emp_id')->nullable();
            $table->bigInteger('department_id')->nullable();
            $table->integer('user_role_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_profile');
    }
};
