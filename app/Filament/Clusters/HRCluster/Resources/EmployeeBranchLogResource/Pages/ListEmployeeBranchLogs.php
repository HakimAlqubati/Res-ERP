<?php

namespace App\Filament\Clusters\HRCluster\Resources\EmployeeBranchLogResource\Pages;

use App\Filament\Clusters\HRCluster\Resources\EmployeeBranchLogResource;
use Filament\Resources\Pages\ListRecords;

class ListEmployeeBranchLogs extends ListRecords
{
    protected static string $resource = EmployeeBranchLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
