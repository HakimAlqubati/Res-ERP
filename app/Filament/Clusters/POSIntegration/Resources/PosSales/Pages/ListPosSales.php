<?php

namespace App\Filament\Clusters\POSIntegration\Resources\PosSales\Pages;

use App\Filament\Clusters\POSIntegration\Resources\PosSales\PosSaleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPosSales extends ListRecords
{
    protected static string $resource = PosSaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
