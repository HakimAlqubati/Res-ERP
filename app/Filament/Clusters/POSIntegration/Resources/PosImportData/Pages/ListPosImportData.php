<?php

namespace App\Filament\Clusters\POSIntegration\Resources\PosImportData\Pages;

use App\Filament\Clusters\POSIntegration\Resources\PosImportData\PosImportDataResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPosImportData extends ListRecords
{
    protected static string $resource = PosImportDataResource::class;

    protected function getHeaderActions(): array
    {
        return [
         ];
    }
}
