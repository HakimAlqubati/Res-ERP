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
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id'); // Foreign Key to Products
            $table->unsignedBigInteger('unit_id'); // Foreign Key to Units

            $table->decimal('quantity', 15, 2); // Quantity of the product in the specific unit
            $table->timestamp('last_updated')->useCurrent(); // Timestamp for last update

            $table->timestamps(); // Created at & Updated at timestamps

            // Foreign key constraints
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
