<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class UnitPriceSyncService
{
    public static function syncPackageSizeForProduct(int $productId): void
    {
        // Update inventory_transactions
        DB::update("
            UPDATE inventory_transactions AS it
            JOIN unit_prices AS up
              ON it.product_id = up.product_id AND it.unit_id = up.unit_id
            SET it.package_size = up.package_size
            WHERE it.product_id = ?
        ", [$productId]);

        // Update orders_details
        DB::update("
            UPDATE orders_details AS od
            JOIN unit_prices AS up
              ON od.product_id = up.product_id AND od.unit_id = up.unit_id
            SET od.package_size = up.package_size
            WHERE od.product_id = ?
        ", [$productId]);

        // Update purchase_invoice_details
        DB::update("
            UPDATE purchase_invoice_details AS pid
            JOIN unit_prices AS up
              ON pid.product_id = up.product_id AND pid.unit_id = up.unit_id
            SET pid.package_size = up.package_size
            WHERE pid.product_id = ?
        ", [$productId]);

        // Update stock_supply_order_details
        DB::update("
            UPDATE stock_supply_order_details AS ssod
            JOIN unit_prices AS up
              ON ssod.product_id = up.product_id AND ssod.unit_id = up.unit_id
            SET ssod.package_size = up.package_size
            WHERE ssod.product_id = ?
        ", [$productId]);
    }
}
