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
        Schema::create('stock_supply_orders', function (Blueprint $table) {
            $table->id();
            $table->date('order_date');
            $table->foreignId('store_id')->constrained('stores')->onDelete('cascade');
            $table->text('notes')->nullable();
            $table->boolean('cancelled')->default(false);
            $table->string('cancel_reason')->nullable();
            $table->bigInteger('created_by');
            $table->bigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_supply_orders');
    }
};
