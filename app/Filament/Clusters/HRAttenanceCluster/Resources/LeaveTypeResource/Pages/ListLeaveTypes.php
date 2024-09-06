<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\LeaveTypeResource\Pages;

use App\Filament\Clusters\HRAttenanceCluster\Resources\LeaveTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLeaveTypes extends ListRecords
{
    protected static string $resource = LeaveTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
