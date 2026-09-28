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
        Schema::create('app_versions', function (Blueprint $table) {
            $table->id();
            $table->string('platform')->default('all'); // android, ios, all
            $table->string('version_name'); // e.g. 1.0.5
            $table->unsignedInteger('version_code'); // e.g. 105
            $table->string('min_supported_version')->nullable(); // e.g. 1.0.0
            $table->unsignedInteger('min_version_code')->nullable(); // e.g. 100
            $table->boolean('is_force_update')->default(false);
            $table->string('download_url', 500)->nullable();
            $table->text('release_notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['platform', 'is_active']);
            $table->index('version_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_versions');
    }
};
