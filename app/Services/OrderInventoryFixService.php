<?php

namespace App\Services;

use App\Models\Order;
use App\Services\FifoMethodService;
use Illuminate\Support\Facades\DB;

class OrderInventoryFixService
{
    public function fixInventoryForOrder(Order $order): array
    {
        return [];
    }
}
