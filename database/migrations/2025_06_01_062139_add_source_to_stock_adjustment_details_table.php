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
            $table->unsignedBigInteger('source_id')->nullable()->after('notes');
            $table->string('source_type')->nullable()->after('source_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_adjustment_details', function (Blueprint $table) {
            $table->dropColumn(['source_id', 'source_type']);
        });
    }
};
