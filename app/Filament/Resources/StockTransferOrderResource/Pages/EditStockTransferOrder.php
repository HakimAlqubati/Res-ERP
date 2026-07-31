<?php

namespace App\Filament\Resources\StockTransferOrderResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\StockTransferOrderResource;
use App\Models\StockTransferOrder;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStockTransferOrder extends EditRecord
{
    protected static string $resource = StockTransferOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->visible(fn()=>StockTransferOrderResource::canDeleteAny()),
        ];
    }
       /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($this->record->status == StockTransferOrder::STATUS_APPROVED) {
            abort(403, 'This stock transfer order cannot be edited because it is already approved.');
        }
        return $data;
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
