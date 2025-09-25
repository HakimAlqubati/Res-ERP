<?php
// File: app/Services/Inventory/Domain/UnitConversionService.php
namespace App\Services\Inventory\Domain;

use App\Services\Inventory\Contracts\UnitConversionInterface;

final class UnitConversionService implements UnitConversionInterface
{
    public function toBase(int $productId, int $unitId, float $qty, float $packageSize): float
    {
        return $qty * $packageSize;
    }

    public function fromBase(int $productId, int $unitId, float $baseQty): float
    {
        // assume package_size fetched elsewhere; simplified here:
        return $baseQty; // placeholder
    }

    public function baseUnitOf(int $productId): ?array
    {
        return ['unit_id' => 1, 'name' => 'BASE']; // placeholder
    }
}
