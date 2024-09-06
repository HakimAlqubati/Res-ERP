<?php

namespace App\Filament\Clusters\HRCluster\Resources\EmployeeFileTypeResource\Pages;

use App\Filament\Clusters\HRCluster\Resources\EmployeeFileTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployeeFileType extends CreateRecord
{
    protected static string $resource = EmployeeFileTypeResource::class;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
