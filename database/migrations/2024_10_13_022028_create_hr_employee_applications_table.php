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
        Schema::create('hr_employee_applications', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('employee_id'); 
            $table->integer('branch_id'); 
            $table->date('application_date');
            $table->enum('status', ['pending', 'approved', 'rejected']);
            $table->text('notes')->nullable();
            $table->bigInteger('application_type_id'); 
            $table->string('application_type_name'); 
            $table->bigInteger('created_by'); 
            $table->bigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->bigInteger('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->json('details')->nullable();
            $table->softDeletes(); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_employee_applications');
    }
};
