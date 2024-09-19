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
        Schema::create('hr_service_requests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->bigInteger('branch_id');
            $table->bigInteger('branch_area_id');
            $table->bigInteger('assigned_to')->nullable()->nullable();
            $table->enum('urgency', ['High', 'Medium', 'Low']);
            $table->enum('impact', ['High', 'Medium', 'Low']);
            $table->enum('status', ['New', 'Pending', 'In progress', 'Closed'])->default('New');
            $table->bigInteger('created_by');
            $table->bigInteger('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_service_requests');
    }
};
