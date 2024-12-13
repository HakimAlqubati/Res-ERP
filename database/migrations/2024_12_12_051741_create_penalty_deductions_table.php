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
        Schema::create('hr_penalty_deductions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('employee_id');
            $table->integer('deduction_id');
            $table->enum('deduction_type', [
                'based_on_selected_deduction',
                'fixed_amount',
                'specific_percentage'
            ])->default('based_on_selected_deduction');
            $table->decimal('penalty_amount', 15, 2);
            $table->text('description');
            $table->string('month', 2);
            $table->year('year');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->bigInteger('created_by');
            $table->bigInteger('approved_by')->nullable();
            $table->bigInteger('rejected_by')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penalty_deductions');
    }
};
