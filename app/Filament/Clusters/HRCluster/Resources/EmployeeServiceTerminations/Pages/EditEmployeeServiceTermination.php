<?php

namespace App\Filament\Clusters\HRCluster\Resources\EmployeeServiceTerminations\Pages;

use App\Filament\Clusters\HRCluster\Resources\EmployeeServiceTerminations\EmployeeServiceTerminationResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditEmployeeServiceTermination extends EditRecord
{
    protected static string $resource = EmployeeServiceTerminationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
