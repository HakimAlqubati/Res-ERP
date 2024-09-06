<?php

namespace App\Filament\Clusters\HRCluster\Resources\EmployeeFileTypeResource\Pages;

use App\Filament\Clusters\HRCluster\Resources\EmployeeFileTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmployeeFileType extends EditRecord
{
    protected static string $resource = EmployeeFileTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
