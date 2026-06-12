<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Remove soft-deleted duplicates with an active counterpart.
        // Confirmed via diagnostics: 13 pairs / 26 rows match this pattern.
        DB::statement(<<<SQL
            DELETE old FROM unit_prices old
            INNER JOIN unit_prices active
                ON  old.product_id   = active.product_id
                AND old.unit_id      = active.unit_id
                AND old.package_size = active.package_size
                AND active.deleted_at IS NULL
            WHERE old.deleted_at IS NOT NULL
        SQL);

        // Step 2: Normalize NULL package_size before enforcing NOT NULL.
        // Assumption: NULL == base unit (package_size = 1).
        DB::statement('UPDATE unit_prices SET package_size = 1 WHERE package_size IS NULL');

        // Step 3: Align types with inventory_transactions for FK compatibility.
        DB::statement('
            ALTER TABLE unit_prices
                MODIFY product_id   BIGINT UNSIGNED NOT NULL,
                MODIFY unit_id      BIGINT UNSIGNED NOT NULL,
                MODIFY package_size DECIMAL(10,2)   NOT NULL DEFAULT 1
        ');

        // Step 4: Composite unique index — prerequisite for the FK.
        DB::statement('
            ALTER TABLE unit_prices
                ADD UNIQUE INDEX uq_unit_prices_composite (product_id, unit_id, package_size)
        ');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE unit_prices DROP INDEX uq_unit_prices_composite');

        DB::statement('
            ALTER TABLE unit_prices
                MODIFY product_id   INT NOT NULL,
                MODIFY unit_id      INT NOT NULL,
                MODIFY package_size FLOAT NULL
        ');

        // NOTE: rows deleted in Step 1 are NOT restorable.
        // Backup `unit_prices` before running this migration if recovery is required.
    }
};