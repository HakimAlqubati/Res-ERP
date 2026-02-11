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
        Schema::create('hr_carry_forward', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->onDelete('cascade');
            $table->foreignId('from_payroll_run_id')->constrained('hr_payroll_runs')->onDelete('cascade');
            $table->integer('year');
            $table->integer('month');
            $table->decimal('total_amount', 15, 2);
            $table->decimal('settled_amount', 15, 2)->default(0);
            $table->decimal('remaining_balance', 15, 2);
            $table->string('status')->default('active'); // active, settled, cancelled
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_carry_forward');
    }
};
