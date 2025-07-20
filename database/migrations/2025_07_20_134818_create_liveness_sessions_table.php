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
        Schema::create('liveness_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->unique();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->string('employee_name')->nullable();
            $table->string('face_id')->nullable();
            $table->string('raw_name')->nullable();
            $table->boolean('is_live')->default(false);
            $table->float('confidence')->nullable();
            $table->string('status');
            $table->integer('audit_images_count')->nullable();
            $table->json('attendance_result')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('liveness_sessions');
    }
};