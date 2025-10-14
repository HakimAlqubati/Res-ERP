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
        Schema::create('hr_user_devices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('device_hash');       // sha256 للـ device_id
            $table->boolean('active')->default(true);
            $table->timestamp('last_login')->nullable();
            $table->string('plat_form')->nullable(); // android / ios
            $table->string('notes')->nullable();

            $table->timestamps();

            // فهارسك المطلوبة:
            $table->index(['user_id', 'device_hash']);
            $table->unique(['user_id', 'device_hash']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_user_devices');
    }
};
