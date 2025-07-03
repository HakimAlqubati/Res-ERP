<?php

namespace App\Filament\Resources\DeliveredResellerOrdersResource\Pages;

use App\Filament\Resources\DeliveredResellerOrdersResource;
use App\Models\Order;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewDeliveredResellerOrders extends ViewRecord
{
    protected static string $resource = DeliveredResellerOrdersResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $order = Order::with('orderDetails')->find($this->record->id);
        if ($order) {
            foreach ($order->orderDetails as $detail) {
                $detail->update([
                    'total_unit_price' => $detail->available_quantity * $detail->price,
                ]);
            }
        }

        return $data;
    }
}