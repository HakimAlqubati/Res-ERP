<?php

namespace App\Filament\Clusters\AreaManagementCluster\Resources\CountryResource\Pages;

use App\Filament\Clusters\AreaManagementCluster\Resources\CountryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCountries extends ListRecords
{
    protected static string $resource = CountryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
