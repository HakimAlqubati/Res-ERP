<?php

namespace App\Filament\Resources\StockIssueOrderResource\Pages;

use App\Filament\Resources\StockIssueOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStockIssueOrder extends EditRecord
{
    protected static string $resource = StockIssueOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
