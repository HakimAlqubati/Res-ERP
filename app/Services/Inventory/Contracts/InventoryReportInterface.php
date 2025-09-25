<?php

namespace App\Services\Inventory\Contracts;

use App\Services\Inventory\Dto\InventoryFiltersDTO;

interface InventoryReportInterface
{ 
    /** @return array{data: array, pagination: mixed|null} */
    public function run(InventoryFiltersDTO $filters): array;
}
