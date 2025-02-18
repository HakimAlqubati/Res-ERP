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
        Schema::table('stock_issue_order_details', function (Blueprint $table) {
            $table->float('package_size')->nullable()->after('unit_id');
        });

        Schema::table('stock_supply_order_details', function (Blueprint $table) {
            $table->float('package_size')->nullable()->after('unit_id');
        });

        Schema::table('stock_inventory_details', function (Blueprint $table) {
            $table->float('package_size')->nullable()->after('unit_id');
        });

        Schema::table('stock_adjustment_details', function (Blueprint $table) {
            $table->float('package_size')->nullable()->after('unit_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_issue_order_details', function (Blueprint $table) {
            $table->dropColumn('package_size');
        });

        Schema::table('stock_supply_order_details', function (Blueprint $table) {
            $table->dropColumn('package_size');
        });

        Schema::table('stock_inventory_details', function (Blueprint $table) {
            $table->dropColumn('package_size');
        });

        Schema::table('stock_adjustment_details', function (Blueprint $table) {
            $table->dropColumn('package_size');
        });
    }
};
