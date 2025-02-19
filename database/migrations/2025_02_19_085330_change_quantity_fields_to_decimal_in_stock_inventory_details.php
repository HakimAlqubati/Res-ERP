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
        Schema::table('stock_inventory_details', function (Blueprint $table) {
            $table->decimal('system_quantity', 15, 2)->change();
            $table->decimal('difference', 15, 2)->change();
            $table->decimal('physical_quantity', 15, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_inventory_details', function (Blueprint $table) {
            $table->integer('system_quantity')->change();
            $table->integer('difference')->change();
            $table->integer('physical_quantity')->change();
        });
    }
};
