<?php

namespace App\Filament\Clusters\SupplierCluster\Resources\GoodsReceivedNoteResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use App\Filament\Clusters\SupplierCluster\Resources\GoodsReceivedNoteResource;
use App\Models\GoodsReceivedNote;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewGoodsReceivedNote extends ViewRecord
{
    protected static string $resource = GoodsReceivedNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            GoodsReceivedNoteResource::getExportExcelAction(\Filament\Actions\Action::class),
            EditAction::make()
                ->visible(fn($record) => in_array($record->status, [
                    GoodsReceivedNote::STATUS_CREATED,
                    GoodsReceivedNote::STATUS_REJECTED,
                ])),
            DeleteAction::make(),
            GoodsReceivedNoteResource::previewAndApproveAction(),
        ];
    }
    
   
}
