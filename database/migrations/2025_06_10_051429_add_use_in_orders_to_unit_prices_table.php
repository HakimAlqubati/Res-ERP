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
        Schema::table('unit_prices', function (Blueprint $table) {
            $table->boolean('use_in_orders')->default(true)->after('show_in_invoices');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('unit_prices', function (Blueprint $table) {
            $table->dropColumn('use_in_orders');
        });
    }
};
