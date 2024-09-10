<?php

namespace App\Filament\Clusters\HRAttendanceReport\Resources\EmployeeAttednaceReportResource\Pages;

use App\Filament\Clusters\HRAttendanceReport\Resources\EmployeeAttednaceReportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmployeeAttednaceReport extends EditRecord
{
    protected static string $resource = EmployeeAttednaceReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
