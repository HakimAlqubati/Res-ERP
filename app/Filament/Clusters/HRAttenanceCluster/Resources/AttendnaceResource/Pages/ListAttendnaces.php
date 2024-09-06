<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\AttendnaceResource\Pages;

use App\Filament\Clusters\HRAttenanceCluster\Resources\AttendnaceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAttendnaces extends ListRecords
{
    protected static string $resource = AttendnaceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
