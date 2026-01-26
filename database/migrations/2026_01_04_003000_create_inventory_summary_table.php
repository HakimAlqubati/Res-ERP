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
        Schema::create('inventory_summary', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('unit_id');

            // Aggregated totals
            $table->decimal('total_in', 16, 4)->default(0);
            $table->decimal('total_out', 16, 4)->default(0);
            $table->decimal('remaining_qty', 16, 4)->default(0);

            // Last price tracking
            $table->decimal('last_in_price', 16, 6)->nullable();
            $table->unsignedBigInteger('last_in_transaction_id')->nullable();

            $table->timestamps();

            // Composite unique index for fast lookups
            $table->unique(['store_id', 'product_id', 'unit_id'], 'inventory_summary_unique');

            // Additional indexes for common queries
            $table->index(['store_id', 'product_id'], 'inventory_summary_store_product');
            $table->index('product_id', 'inventory_summary_product');

            // Foreign keys
            $table->foreign('store_id')->references('id')->on('stores')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_summary');
    }
};
