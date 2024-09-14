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
        Schema::create('hr_leave_applications', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('employee_id');
            $table->bigInteger('created_by');
            $table->bigInteger('updated_by')->nullable();
            $table->enum('status', ['pending', 'cancel', 'approved', 'rejected'])->default('pending');
            $table->text('leave_reason');
            $table->bigInteger('approved_by')->nullable();
            $table->bigInteger('rejected_by')->nullable();
            $table->text('reject_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_leave_applications');
    }
};
