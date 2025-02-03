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
        Schema::create('hr_equipment', function (Blueprint $table) {
            $table->id();
            $table->string('asset_tag')->unique();
            $table->string('qr_code')->unique();
            $table->string('make')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->unique();
            $table->bigInteger('branch_id')->nullable();
            $table->decimal('purchase_price', 10, 2)->nullable();
            $table->string('warranty_file')->nullable();  // attachment link
            $table->string('profile_picture')->nullable(); // profile picture link
            $table->string('size')->nullable();
            $table->integer('periodic_service')->default(0); // days
            $table->date('last_serviced')->nullable();
            $table->bigInteger('creatd_by');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_equipment');
    }
};
