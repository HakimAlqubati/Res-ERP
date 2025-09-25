<?php
namespace App\Services\Inventory\Contracts; 

use App\Services\Inventory\Dto\InventoryFiltersDTO;

interface InventoryRepositoryInterface
{
    /** @return array<int, array{product_id:int, unit_id:int, movement:string, qty:float, package_size:float, price:float|null, store_id:int, moved_at:string}> */
    public function fetchMovements(InventoryFiltersDTO $filters): array;

    /** @return mixed|null */
    public function pagination();
}