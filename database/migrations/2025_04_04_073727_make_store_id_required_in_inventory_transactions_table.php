<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            DB::statement("
            UPDATE inventory_transactions 
            SET store_id = 0 
            WHERE store_id IS NULL 
                OR store_id = '' 
        ");
            $table->bigInteger('store_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->bigInteger('store_id')->nullable()->change();
        });
    }
};
