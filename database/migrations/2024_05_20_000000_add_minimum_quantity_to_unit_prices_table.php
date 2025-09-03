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
            $table->decimal('minimum_quantity', 10, 2)->nullable()->after('package_size');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('unit_prices', function (Blueprint $table) {
            $table->dropColumn('minimum_quantity');
        });
    }
};