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
        Schema::table('stock_adjustment_details', function (Blueprint $table) {
            $table->decimal('quantity', 15, 4)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_adjustment_details', function (Blueprint $table) {
            $table->decimal('quantity', 15, 2)->change();
        });
    }
};