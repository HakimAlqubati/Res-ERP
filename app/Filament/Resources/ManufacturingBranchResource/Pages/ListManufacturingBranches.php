<?php

namespace App\Filament\Resources\ManufacturingBranchResource\Pages;

use App\Filament\Resources\ManufacturingBranchResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListManufacturingBranches extends ListRecords
{
    protected static string $resource = ManufacturingBranchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
