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
        Schema::table('stock_supply_order_details', function (Blueprint $table) {
            $table->decimal('price', 10, 0)->after('unit_id'); // Adjust the 10 if needed for the precision
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_supply_order_details', function (Blueprint $table) {
            $table->dropColumn('price');
        });
    }
};
