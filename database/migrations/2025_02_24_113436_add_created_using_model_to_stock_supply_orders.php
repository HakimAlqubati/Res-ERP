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
        Schema::table('stock_issue_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('created_using_model_id')->nullable();
            $table->string('created_using_model_type')->nullable();
        });

        Schema::table('stock_supply_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('created_using_model_id')->nullable();
            $table->string('created_using_model_type')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_supply_orders', function (Blueprint $table) {
            $table->dropColumn(['created_using_model_id', 'created_using_model_type']);
        });

        Schema::table('stock_issue_orders', function (Blueprint $table) {
            $table->dropColumn(['created_using_model_id', 'created_using_model_type']);
        });
    }
};
