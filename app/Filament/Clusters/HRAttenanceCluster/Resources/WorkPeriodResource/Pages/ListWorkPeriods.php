<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\WorkPeriodResource\Pages;

use App\Filament\Clusters\HRAttenanceCluster\Resources\WorkPeriodResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWorkPeriods extends ListRecords
{
    protected static string $resource = WorkPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
