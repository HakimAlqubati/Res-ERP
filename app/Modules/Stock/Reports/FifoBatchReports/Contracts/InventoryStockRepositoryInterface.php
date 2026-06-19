<?php

declare(strict_types=1);

namespace App\Modules\Stock\Reports\FifoBatchReports\Contracts;

use App\Modules\Stock\Reports\FifoBatchReports\DataTransferObjects\StockBatchFilterDTO;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface InventoryStockRepositoryInterface
{
    public function getAvailableStockBatches(StockBatchFilterDTO $filters): Collection|LengthAwarePaginator;
}