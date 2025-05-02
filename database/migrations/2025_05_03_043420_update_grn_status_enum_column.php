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
        Schema::table('goods_received_notes', function (Blueprint $table) {
            $table->dropColumn(['is_approved', 'is_cancelled']);
            $table->enum('status', ['created', 'approved', 'cancelled', 'rejected'])->default('created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('goods_received_notes', function (Blueprint $table) {
            $table->dropColumn('status');

            $table->boolean('is_approved')->default(false);
            $table->boolean('is_cancelled')->default(false);
        });
    }
};
