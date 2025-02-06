<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\AttendanceImagesUploadedResource\Pages;

use App\Filament\Clusters\HRAttenanceCluster\Resources\AttendanceImagesUploadedResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAttendanceImagesUploadeds extends ListRecords
{
    protected static string $resource = AttendanceImagesUploadedResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
