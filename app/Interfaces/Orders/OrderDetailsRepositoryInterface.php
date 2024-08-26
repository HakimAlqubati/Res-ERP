<?php

namespace App\Interfaces\Orders;

interface OrderDetailsRepositoryInterface
{
    public function updateWithUnitPrices($request);
    public function updateWithFifo($request);
}
