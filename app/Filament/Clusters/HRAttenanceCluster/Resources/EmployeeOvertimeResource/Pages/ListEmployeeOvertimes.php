<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\EmployeeOvertimeResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Clusters\HRAttenanceCluster\Resources\EmployeeOvertimeResource;
use App\Filament\Clusters\HRAttenanceCluster\Resources\EmployeeOvertimeResource\Actions\HeaderActions;
use App\Filament\Clusters\HRAttenanceCluster\Resources\EmployeeOvertimeResource\Actions\HeaderActions\AutoProcess;
use App\Filament\Clusters\HRAttenanceCluster\Resources\EmployeeOvertimeResource\Actions\HeaderActions\BatchQuickAdd;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Grid;

class ListEmployeeOvertimes extends ListRecords
{
    protected static string $resource = EmployeeOvertimeResource::class;

    protected function getHeaderActions(): array
    {
        return [
           BatchQuickAdd::action(),
           AutoProcess::action(),
            CreateAction::make()
                ->label('Manage Staff Overtime')
                ->hidden(fn() => isBranchUser()),
        ];
    }

  
    // public function getModelLabel(): ?string
    // {
    //     return 'Manage Staff Overtime';
    // }
}
