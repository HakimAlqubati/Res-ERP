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
        Schema::create('reseller_sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reseller_sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->decimal('package_size', 10, 4)->default(1);
            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_price', 15, 4);
            $table->decimal('total_price', 15, 4);

            $table->foreignId('inventory_transaction_id')->nullable()->constrained('inventory_transactions')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reseller_sale_items');
    }
};