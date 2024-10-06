<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\AttendnaceResource\Pages;

use App\Filament\Clusters\HRAttenanceCluster\Resources\AttendnaceResource;
use Filament\Resources\Pages\ViewRecord;

class ViewAttendnace extends ViewRecord
{
    protected static string $resource = AttendnaceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }

}
