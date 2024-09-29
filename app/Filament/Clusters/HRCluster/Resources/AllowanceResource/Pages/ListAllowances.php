<?php

namespace App\Filament\Clusters\HRCluster\Resources\AllowanceResource\Pages;

use App\Filament\Clusters\HRCluster\Resources\AllowanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAllowances extends ListRecords
{
    protected static string $resource = AllowanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
