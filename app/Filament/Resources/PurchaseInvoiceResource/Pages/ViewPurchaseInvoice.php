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
            \Filament\Actions\Action::make('create_return')
                ->label('Create Return')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->visible(fn() => ! $this->record->cancelled && $this->record->return_status !== 'fully_returned')
                ->url(fn() => \App\Filament\Clusters\SupplierCluster\Resources\PurchaseReturnResource::getUrl('create', [
                    'purchase_invoice_id' => $this->record->id,
                ])),
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
