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
        Schema::create('hr_service_request_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_request_id');
            $table->bigInteger('created_by');
            $table->text('description');
            $table->enum('log_type', [
                'created',
                'updated',
                'reassign_to_user',
                'status_changed',
                'comment_added',
                'images_added',
                'removed'
            ]);
            $table->timestamps();

            // Add foreign key constraints if needed
            $table->foreign('service_request_id')->references('id')->on('hr_service_requests')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_service_request_logs');
    }
};
