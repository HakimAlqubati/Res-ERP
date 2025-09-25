<?php
// File: app/Services/Inventory/Domain/PricingStrategyService.php
namespace App\Services\Inventory\Domain;

use App\Services\Inventory\Contracts\PricingStrategyInterface;

final class PricingStrategyService implements PricingStrategyInterface
{
    public function apply(array $rows, int $storeId): array
    {
        return $rows;
        foreach ($rows as &$r) {
            $r['price'] = $r['price'] ?? null;
            $r['price_source'] = $r['price'] ? 'INVENTORY' : 'UNIT_PRICE';
        }
        return $rows;
    }
}
