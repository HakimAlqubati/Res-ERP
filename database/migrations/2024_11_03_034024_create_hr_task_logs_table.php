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
        Schema::create('hr_task_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('hr_tasks')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade'); // Assuming 'users' table
            $table->string('description');
            $table->enum('log_type', ['created', 'moved', 'edited', 'rejected', 'commented', 'images_added']);
            $table->json('details')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_task_logs');
    }
};
