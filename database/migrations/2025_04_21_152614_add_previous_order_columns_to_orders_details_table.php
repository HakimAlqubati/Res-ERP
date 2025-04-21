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
        Schema::table('orders_details', function (Blueprint $table) {
            $table->boolean('is_created_due_to_qty_preivous_order')->default(false)->after('available_quantity');
            $table->bigInteger('previous_order_id')->nullable()->after('is_created_due_to_qty_preivous_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders_details', function (Blueprint $table) {
            $table->dropColumn(['is_created_due_to_qty_preivous_order', 'previous_order_id']);
        });
    }
};
