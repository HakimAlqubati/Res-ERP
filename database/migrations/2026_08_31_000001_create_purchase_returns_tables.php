<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_no', 100)->unique();
            $table->date('return_date');
            $table->foreignId('purchase_invoice_id')->nullable()->constrained('purchase_invoices')->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->foreignId('store_id')->constrained('stores')->restrictOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->enum('status', ['draft', 'approved', 'rejected', 'cancelled'])->default('draft');
            $table->decimal('total_amount', 15, 4)->default(0.0000);
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->string('attachment')->nullable();
            
            // Audit and approval tracking
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->boolean('cancelled')->default(false);
            $table->text('cancel_reason')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();

            $table->softDeletes();
            $table->timestamps();

            // Performance indexes
            $table->index(['return_date', 'status']);
            $table->index(['supplier_id', 'store_id']);
        });

        Schema::create('purchase_return_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_return_id')->constrained('purchase_returns')->cascadeOnDelete();
            $table->foreignId('purchase_invoice_detail_id')->nullable()->constrained('purchase_invoice_details')->nullOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            $table->decimal('package_size', 12, 4)->default(1.0000);
            $table->decimal('quantity', 12, 4);
            $table->decimal('unit_price', 15, 4);
            $table->decimal('total_price', 15, 4);
            $table->string('notes')->nullable();
            
            $table->softDeletes();
            $table->timestamps();

            $table->index(['product_id', 'unit_id']);
            $table->index('purchase_invoice_detail_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_return_details');
        Schema::dropIfExists('purchase_returns');
    }
};
