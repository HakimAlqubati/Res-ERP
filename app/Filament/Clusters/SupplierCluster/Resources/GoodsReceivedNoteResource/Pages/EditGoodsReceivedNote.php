<?php

namespace App\Filament\Clusters\SupplierCluster\Resources\GoodsReceivedNoteResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Clusters\SupplierCluster\Resources\GoodsReceivedNoteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Traits\HasPriceChangeWarning;

class EditGoodsReceivedNote extends EditRecord
{
    use HasPriceChangeWarning;
    protected static string $resource = GoodsReceivedNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    protected function beforeSave(): void
    {
        $this->checkPriceChanges();
    }
}
