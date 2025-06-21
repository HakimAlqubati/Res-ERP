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
        Schema::table('product_items', function (Blueprint $table) {
            $table->decimal('qty_waste_percentage', 10, 8)->change();
            $table->decimal('quantity_after_waste', 10, 8)->change();
            $table->decimal('total_price_after_waste', 10, 8)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_items', function (Blueprint $table) {
            $table->float('qty_waste_percentage')->change();
            $table->decimal('quantity_after_waste', 10, 2)->change();
            $table->float('total_price_after_waste')->change();
        });
    }
};
