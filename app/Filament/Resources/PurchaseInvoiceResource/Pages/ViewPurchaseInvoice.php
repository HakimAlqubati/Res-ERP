<?php

namespace App\Filament\Resources\PurchaseInvoiceResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\PurchaseInvoiceResource;
use App\Models\PurchaseInvoice;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ViewRecord;

class ViewPurchaseInvoice extends ViewRecord
{
    protected static string $resource = PurchaseInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $purchaseInvoice = PurchaseInvoice::with('details')->find($this->record->id);
        if ($purchaseInvoice) {
            foreach ($purchaseInvoice->details as $detail) {
                $detail->update([
                    'unit_total_price' => $detail->quantity * $detail->price,
                ]);
            }
        }
        return $data;
    }
}
