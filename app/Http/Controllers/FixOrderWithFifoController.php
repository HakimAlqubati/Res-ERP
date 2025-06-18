<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\FifoMethodService;
use App\Services\OrderInventoryFixService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FixOrderWithFifoController extends Controller
{

    public function fixInventoryForReadyOrder($orderId) {}

    public function getAllocationsPreview($orderId) {}
}
