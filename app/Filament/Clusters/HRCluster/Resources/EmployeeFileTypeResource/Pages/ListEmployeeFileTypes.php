<?php

namespace App\Filament\Clusters\HRCluster\Resources\EmployeeFileTypeResource\Pages;

use App\Filament\Clusters\HRCluster\Resources\EmployeeFileTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEmployeeFileTypes extends ListRecords
{
    protected static string $resource = EmployeeFileTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
