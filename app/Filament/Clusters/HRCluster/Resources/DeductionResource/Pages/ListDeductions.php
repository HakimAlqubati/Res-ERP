<?php

namespace App\Filament\Clusters\HRCluster\Resources\DeductionResource\Pages;

use App\Filament\Clusters\HRCluster\Resources\DeductionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDeductions extends ListRecords
{
    protected static string $resource = DeductionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
