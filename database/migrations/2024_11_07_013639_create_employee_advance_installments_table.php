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
        Schema::create('hr_employee_advance_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->onDelete('cascade');
            $table->foreignId('application_id')->constrained('hr_employee_applications')->onDelete('cascade'); // Assuming the advances are stored in `hr_application_transactions`
            $table->foreignId('transaction_id')->constrained('hr_application_transactions')->onDelete('cascade'); // Assuming the advances are stored in `hr_application_transactions`
            $table->decimal('installment_amount', 10, 2); // Amount per installment
            $table->date('due_date'); // Date when each installment is due
            $table->boolean('is_paid')->default(false); // Status of the installment (paid or not)
            $table->date('paid_date')->nullable(); // Date when the installment was paid
       
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_advance_installments');
    }
};
