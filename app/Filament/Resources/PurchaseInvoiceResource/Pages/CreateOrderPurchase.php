<?php

namespace App\Filament\Resources\OrderPurchaseResource\Pages;

use App\Filament\Resources\OrderPurchaseResource;
use App\Models\Branch;
use App\Models\Order;
use Filament\Resources\Pages\CreateRecord;

class CreateOrderPurchase extends CreateRecord
{
    protected static string $resource = OrderPurchaseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {

        if (in_array(getCurrentRole(), [1, 3])) {
            $data['customer_id'] = Branch::find($data['branch_id'])->manager_id;
        } else {
            $data['branch_id'] = auth()->user()->branch->id;
        }
        $data['customer_id'] = Branch::find($data['branch_id'])->manager_id;

        $data['status'] = Order::DELEVIRED;
        $data['is_purchased'] = 1;
        // dd($data['purchaseInvoiceDetails']);
        return $data;
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
