<?php

namespace App\Filament\Clusters\HRCluster\Resources\DepartmentResource\Pages;

use App\Filament\Clusters\HRCluster\Resources\DepartmentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDepartment extends CreateRecord
{
    protected static string $resource = DepartmentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
