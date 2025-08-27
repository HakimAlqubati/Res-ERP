<?php

namespace App\Filament\Resources\StockReportResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\StockReportResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStockReport extends EditRecord
{
    protected static string $resource = StockReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
