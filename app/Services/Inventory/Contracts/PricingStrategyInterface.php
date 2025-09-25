<?php
namespace App\Services\Inventory\Contracts;

interface PricingStrategyInterface
{
    /** @param array $rows grouped per product/unit with sums
     *  @return array same rows with price & price_source keys
     */
    public function apply(array $rows, int $storeId): array;
}