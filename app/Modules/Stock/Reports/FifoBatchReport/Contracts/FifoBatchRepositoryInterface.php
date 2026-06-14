<?php

namespace App\Modules\Stock\Reports\FifoBatchReport\Contracts;

use App\Modules\Stock\Reports\FifoBatchReport\DTOs\FifoBatchFilterDTO;
use Illuminate\Support\Collection;

interface FifoBatchRepositoryInterface
{
    /**
     * Get all IN batches with consumed/remaining quantities calculated in DB.
     *
     * Each row includes: id, product_id, unit_id, store_id, quantity,
     * package_size, price, movement_date, transactionable_type, transactionable_id,
     * product_name, product_code, unit_name, consumed_qty, remaining_qty
     */
    public function getBatchesWithConsumption(FifoBatchFilterDTO $filter): Collection;
}
