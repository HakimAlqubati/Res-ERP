<?php
// File: app/Services/Inventory/Domain/ReorderThresholdPolicy.php
namespace App\Services\Inventory\Domain;

use App\Services\Inventory\Contracts\ThresholdPolicyInterface;

final class ReorderThresholdPolicy implements ThresholdPolicyInterface
{
    public function decorate(array $rows): array
    {
        return $rows;
        foreach ($rows as &$r) {
            $min = $r['minimum_qty'] ?? 0.0;
            $r['below_minimum'] = ($r['remaining_qty'] ?? 0) <= $min;
        }
        return $rows;
    }
}
