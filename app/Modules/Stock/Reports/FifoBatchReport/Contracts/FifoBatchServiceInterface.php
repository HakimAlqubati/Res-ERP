<?php

namespace App\Modules\Stock\Reports\FifoBatchReport\Contracts;

use App\Modules\Stock\Reports\FifoBatchReport\DTOs\FifoBatchDTO;
use App\Modules\Stock\Reports\FifoBatchReport\DTOs\FifoBatchFilterDTO;
use App\Modules\Stock\Reports\FifoBatchReport\DTOs\FifoBatchReportDTO;
use Illuminate\Support\Collection;

interface FifoBatchServiceInterface
{
    /**
     * @return Collection<int, FifoBatchReportDTO>
     */
    public function getReport(FifoBatchFilterDTO $filter): Collection;

    public function getCurrentBatch(int $productId, int $unitId, ?int $storeId = null): ?FifoBatchDTO;

    public function getCurrentPrice(int $productId, int $unitId, ?int $storeId = null): ?float;
}
