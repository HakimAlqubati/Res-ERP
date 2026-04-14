<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources\EmployeeRewards\Pages;

use App\Filament\Clusters\HRSalaryCluster\Resources\EmployeeRewards\EmployeeRewardResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEmployeeRewards extends ListRecords
{
    protected static string $resource = EmployeeRewardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
