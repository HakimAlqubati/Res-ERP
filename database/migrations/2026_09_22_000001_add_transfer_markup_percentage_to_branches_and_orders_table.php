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
        Schema::table('branches', function (Blueprint $table) {
            if (!Schema::hasColumn('branches', 'transfer_markup_percentage')) {
                $table->decimal('transfer_markup_percentage', 5, 2)
                    ->default(0.00)
                    ->nullable()
                    ->after('store_id');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'transfer_markup_percentage')) {
                $table->decimal('transfer_markup_percentage', 5, 2)
                    ->default(0.00)
                    ->nullable()
                    ->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (Schema::hasColumn('branches', 'transfer_markup_percentage')) {
                $table->dropColumn('transfer_markup_percentage');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'transfer_markup_percentage')) {
                $table->dropColumn('transfer_markup_percentage');
            }
        });
    }
};
