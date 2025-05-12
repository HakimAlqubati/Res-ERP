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
        Schema::create('returned_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('original_order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches');
            $table->text('reason')->nullable();
            $table->date('returned_date')->nullable();
            $table->enum('status', ['created', 'approved', 'rejected'])->default('created');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('returned_order_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('returned_order_id')->constrained('returned_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('unit_id')->constrained('units');
            $table->decimal('quantity', 15, 4)->default(0);
            $table->decimal('price', 15, 4)->default(0);
            $table->decimal('package_size', 15, 4)->default(1);
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('returned_order_details');
        Schema::dropIfExists('returned_orders');
    }
};
