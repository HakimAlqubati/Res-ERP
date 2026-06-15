<?php

namespace App\Modules\Stock\Reports\FifoBatchReports\Contracts;

use App\DataTransferObjects\StockBatchData;
use Illuminate\Support\Collection;

interface InventoryStockRepositoryInterface
{
    /**
     * @return Collection<int, StockBatchData>
     */
    public function getAvailableStockBatches(?int $productId, int $storeId): Collection;
}