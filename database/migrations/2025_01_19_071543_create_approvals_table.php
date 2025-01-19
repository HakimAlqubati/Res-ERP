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
        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->string('route_name')->nullable(); // e.g., 'workbench_webcam'
            $table->string('date')->nullable();
            $table->string('time')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->foreignId('approved_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approvals');
    }
};
