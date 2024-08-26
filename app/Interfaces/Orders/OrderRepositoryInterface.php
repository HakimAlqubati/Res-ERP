<?php

namespace App\Interfaces\Orders;

interface OrderRepositoryInterface
{
    public function index($request);
    public function storeWithFifo($data);
    public function storeWithUnitPricing($data);
    public function update($request, $id);
    public function export($id);
    public function exportTransfer($id);
}
