<?php

namespace App\Services\Inventory\Contracts;

interface ThresholdPolicyInterface
{
    /** @return array rows with minimum_qty & below_minimum flags */
    public function decorate(array $rows): array;
}
