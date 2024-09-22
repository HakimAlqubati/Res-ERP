<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\EmployeeOvertimeResource\Pages;

use App\Filament\Clusters\HRAttenanceCluster\Resources\EmployeeOvertimeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmployeeOvertime extends EditRecord
{
    protected static string $resource = EmployeeOvertimeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
