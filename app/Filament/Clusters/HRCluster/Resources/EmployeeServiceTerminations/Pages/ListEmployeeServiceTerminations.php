<?php

namespace App\Filament\Clusters\HRCluster\Resources\EmployeeServiceTerminations\Pages;

use App\Filament\Clusters\HRCluster\Resources\EmployeeServiceTerminations\EmployeeServiceTerminationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEmployeeServiceTerminations extends ListRecords
{
    protected static string $resource = EmployeeServiceTerminationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
