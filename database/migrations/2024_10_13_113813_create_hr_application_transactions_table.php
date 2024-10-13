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
        Schema::create('hr_application_transactions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('application_id')->nullable();
            $table->integer('transaction_type_id')->nullable();
            $table->string('transaction_type_name')->nullable();
            $table->text('transaction_description')->nullable();
            $table->date('submitted_on')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->decimal('remaining', 10, 2)->nullable();
            $table->date('from_date')->nullable();
            $table->date('to_date')->nullable();
            $table->bigInteger('created_by')->nullable();
            $table->bigInteger('employee_id')->nullable();
            $table->softDeletes();
            $table->boolean('is_canceled')->default(false);
            $table->timestamp('canceled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->json('details')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_application_transactions');
    }
};
