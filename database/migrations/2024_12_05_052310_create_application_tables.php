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
        // Table for Leave Requests
        Schema::create('hr_leave_requests', function (Blueprint $table) {
            $table->id();
            $table->integer('application_type_id');
            $table->string('application_type_name');
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('employee_id');
            $table->string('leave_type');
            $table->date('start_date');
            $table->date('end_date');
            $table->text('reason')->nullable();
            $table->timestamps();
        });

        // Table for Missed Check-In
        Schema::create('hr_missed_check_in_requests', function (Blueprint $table) {
            $table->id();
            $table->integer('application_type_id');
            $table->string('application_type_name');
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('employee_id');

            // New fields for missed check-in
            $table->date('date');  // The date of missed check-in
            $table->time('time');  // The time of missed check-in

            $table->text('reason')->nullable(); // Reason for missed check-in
            $table->timestamps();
        });

        // Table for Advance Requests
        Schema::create('hr_advance_requests', function (Blueprint $table) {
            $table->id();
            $table->integer('application_type_id');
            $table->string('application_type_name');
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('employee_id');
            // New fields for advance request
            $table->decimal('advance_amount', 10, 2); // The amount requested for the advance
            $table->decimal('monthly_deduction_amount', 10, 2); // The amount deducted monthly
            $table->date('deduction_ends_at')->nullable(); // The date the deductions will end
            $table->integer('number_of_months_of_deduction')->nullable(); // The number of months for deduction
            $table->date('date')->nullable(); // The date of the request (if any)
            $table->date('deduction_starts_from')->nullable(); // The date deductions start from

            $table->text('reason')->nullable();
            $table->timestamps();
        });

        // Table for Missed Check-Out
        Schema::create('hr_missed_check_out_requests', function (Blueprint $table) {
            $table->id();
            $table->integer('application_type_id');
            $table->string('application_type_name');
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('employee_id');

            // New fields for missed check-out
            $table->date('date');  // The date of missed check-out
            $table->time('time');  // The time of missed check-out

            $table->text('reason')->nullable(); // Reason for missed check-out
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop individual tables
        Schema::dropIfExists('hr_leave_requests');
        Schema::dropIfExists('hr_missed_check_in_requests');
        Schema::dropIfExists('hr_advance_requests');
        Schema::dropIfExists('hr_missed_check_out_requests');
    }
};
