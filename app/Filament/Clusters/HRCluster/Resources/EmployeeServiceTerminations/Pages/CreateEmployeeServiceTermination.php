<?php

namespace App\Filament\Clusters\HRCluster\Resources\EmployeeServiceTerminations\Pages;

use App\Filament\Clusters\HRCluster\Resources\EmployeeServiceTerminations\EmployeeServiceTerminationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployeeServiceTermination extends CreateRecord
{
    protected static string $resource = EmployeeServiceTerminationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
