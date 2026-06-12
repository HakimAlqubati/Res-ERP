<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            DB::statement('
                ALTER TABLE inventory_transactions
                    ADD CONSTRAINT fk_inv_trans_unit_price
                    FOREIGN KEY (product_id, unit_id, package_size)
                    REFERENCES unit_prices (product_id, unit_id, package_size)
                    ON UPDATE CASCADE
                    ON DELETE RESTRICT
            ');
        } finally {
            // يُعاد التفعيل دائماً، حتى لو فشل ALTER لسبب آخر (type mismatch
            // متبقٍّ)، لتجنب ترك الجلسة بدون فحص FK لباقي عمليات الـ migration.
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE inventory_transactions DROP FOREIGN KEY fk_inv_trans_unit_price');
    }
};