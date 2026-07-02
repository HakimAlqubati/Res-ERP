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
        // Update existing records
        DB::statement("UPDATE orders SET status = 'cancelled' WHERE cancelled = 1");

        // Drop the boolean column
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('cancelled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->tinyInteger('cancelled')->default(false);
        });
    }
};
