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
        Schema::create('stock_transfer_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_store_id')->constrained('stores');
            $table->foreignId('to_store_id')->constrained('stores');
            $table->date('date')->default(now());
            $table->enum('status', ['created', 'approved', 'rejected'])->default('created');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('rejected_by')->nullable()->constrained('users');
            $table->text('rejected_reason')->nullable();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });


        Schema::create('stock_transfer_order_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_order_id')->constrained('stock_transfer_orders')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('unit_id')->constrained('units');
            $table->decimal('quantity', 10, 2);
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('package_size', 10, 2)->default(1);
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_order_details');
        Schema::dropIfExists('stock_transfer_orders');
    }
};
