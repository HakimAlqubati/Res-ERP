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
        Schema::table('stores_inventory_transactions_tables', function (Blueprint $table) {
            Schema::table('inventory_transactions', function (Blueprint $table) {
                $table->bigInteger('store_id')->nullable()->after('package_size');
            });

            Schema::table('orders', function (Blueprint $table) {
                $table->bigInteger('store_id')->nullable()->after('order_date');
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropColumn('store_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('store_id');
        });
    }
};
