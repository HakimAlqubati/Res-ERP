<?php

namespace App\Filament\Clusters\SupplierCluster\Resources\GoodsReceivedNoteResource\Pages;

use App\Filament\Clusters\SupplierCluster\Resources\GoodsReceivedNoteResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateGoodsReceivedNote extends CreateRecord
{
    protected static string $resource = GoodsReceivedNoteResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
