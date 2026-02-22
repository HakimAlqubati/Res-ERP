<?php

namespace App\Filament\Clusters\SupplierCluster\Resources\GoodsReceivedNoteResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Clusters\SupplierCluster\Resources\GoodsReceivedNoteResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewGoodsReceivedNote extends ViewRecord
{
    protected static string $resource = GoodsReceivedNoteResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $purchaseInvoiceId   = $this->record?->purchase_invoice_id;
        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
