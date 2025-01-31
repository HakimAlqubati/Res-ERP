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
        return;
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->float('package_size')->nullable()->after('movement_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        return;
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropColumn('package_size');
        });
    }
};
