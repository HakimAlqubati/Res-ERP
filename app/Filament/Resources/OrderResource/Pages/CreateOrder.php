<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\OrderDetails;
use App\Models\UnitPrice;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {

        // dd($data);
        return $data;
    }

    protected function afterCreate(): void
    {

        foreach ($this->data['orderDetails'] as $key => $value) {
            OrderDetails::create([
                'product_id' => $value['product_id'],
                'unit_id' => $value['unit_id'],
                'quantity' => $value['quantity'],
                'available_quantity' => $value['quantity'],
                'order_id' => $this->record->id,
                'price' => rand(10, 50)
            ]);
        }
        // Runs after the form fields are saved to the database.
    }

    public function getUnitPrice($p_id, $u_id)
    {
        return UnitPrice::where('unit_id', $u_id)->where('product_id', $p_id)->first()->price;
    }
}
