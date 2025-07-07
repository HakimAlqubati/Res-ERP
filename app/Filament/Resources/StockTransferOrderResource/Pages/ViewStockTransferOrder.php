<?php

namespace App\Filament\Resources\StockTransferOrderResource\Pages;

use App\Filament\Resources\StockTransferOrderResource;
use Filament\Actions; 
use Filament\Resources\Pages\ViewRecord;

class ViewStockTransferOrder extends ViewRecord
{
    protected static string $resource = StockTransferOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }
    
}