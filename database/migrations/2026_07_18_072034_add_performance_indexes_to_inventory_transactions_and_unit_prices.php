<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * فهارس مركّبة لتسريع استعلامات تقرير FIFO (InventoryStockRepository).
     *
     * المبدأ: ترتيب الأعمدة في الفهرس يتبع قاعدة "المساواة أولاً، النطاق أخيراً"
     * (Equality columns first, range/sort columns last).
     */
    public function up(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            // 1. يخدم stockBatchesSubquery:
            //    WHERE store_id = ? AND movement_type = 'in' AND deleted_at IS NULL
            //    + ORDER BY id (ضمنياً عبر window functions)
            //    + whereIn product_id (اختياري)
            $table->index(
                ['store_id', 'movement_type', 'deleted_at', 'product_id'],
                'idx_inv_trx_store_movement_deleted_product'
            );

            // 2. يخدم outAggregatesSubquery:
            //    WHERE movement_type = 'out' AND store_id = ? AND deleted_at IS NULL
            //    GROUP BY source_transaction_id
            //    + SUM(quantity * package_size)
            $table->index(
                ['movement_type', 'store_id', 'deleted_at', 'source_transaction_id'],
                'idx_inv_trx_out_aggregates'
            );
        });

        Schema::table('unit_prices', function (Blueprint $table) {
            // 3. يخدم baseUnitsSubquery:
            //    PARTITION BY product_id ORDER BY package_size ASC
            //    + WHERE usage_scope IN (...)
            $table->index(
                ['product_id', 'usage_scope', 'package_size'],
                'idx_unit_prices_product_scope_pkg'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_inv_trx_store_movement_deleted_product');
            $table->dropIndex('idx_inv_trx_out_aggregates');
        });

        Schema::table('unit_prices', function (Blueprint $table) {
            $table->dropIndex('idx_unit_prices_product_scope_pkg');
        });
    }
};
