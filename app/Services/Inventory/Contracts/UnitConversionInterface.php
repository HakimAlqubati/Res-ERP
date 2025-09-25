<?php
namespace App\Services\Inventory\Contracts;

interface UnitConversionInterface
{
    public function toBase(int $productId, int $unitId, float $qty, float $packageSize): float;
    public function fromBase(int $productId, int $unitId, float $baseQty): float;
    public function baseUnitOf(int $productId): ?array; // ['unit_id'=>..,'name'=>..]
}