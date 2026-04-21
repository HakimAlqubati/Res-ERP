<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources\EmployeeRewards\Pages;

use App\Filament\Clusters\HRSalaryCluster\Resources\EmployeeRewards\EmployeeRewardResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployeeReward extends CreateRecord
{
    protected static string $resource = EmployeeRewardResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
