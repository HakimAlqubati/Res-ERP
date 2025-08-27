<?php

namespace App\Filament\Clusters\HRApplicationsCluster\Resources\EmployeeApplicationResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Clusters\HRApplicationsCluster\Resources\EmployeeApplicationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmployeeApplication extends EditRecord
{
    protected static string $resource = EmployeeApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
