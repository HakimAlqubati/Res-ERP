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
        Schema::create('test_search_by_images', function (Blueprint $table) {
            $table->id();
            $table->string('image_url'); // Store the URL of the image
            $table->string('rekognition_id'); // Unique identifier for Rekognition
            $table->string('name')->nullable(); // Name associated with the image
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_search_by_images');
    }
};
