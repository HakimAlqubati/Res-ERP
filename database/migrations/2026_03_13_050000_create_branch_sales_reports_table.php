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
        Schema::create('branch_sales_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            
            // Financial extracted fields
            $table->decimal('service_charge', 15, 2)->default(0);
            $table->decimal('net_sale', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0); // service_charge + net_sale
            
            $table->string('attachment')->nullable();
            
            // Link to the AWS Textract analysis attempts
            $table->foreignId('document_analysis_attempt_id')->nullable()->constrained('document_analysis_attempts')->nullOnDelete();
            
            // Approval flow
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->text('notes')->nullable();
            
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            
            // Soft deletes to allow archiving
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_sales_reports');
    }
};
