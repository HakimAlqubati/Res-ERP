<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\EmployeeOvertimeResource\Pages;

use App\Filament\Clusters\HRAttenanceCluster\Resources\EmployeeOvertimeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEmployeeOvertimes extends ListRecords
{
    protected static string $resource = EmployeeOvertimeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
            ->label('Manage Staff Overtime')
            ,
        ];
    }
    // public function getModelLabel(): ?string
    // {
    //     return 'Manage Staff Overtime';
    // }
}
