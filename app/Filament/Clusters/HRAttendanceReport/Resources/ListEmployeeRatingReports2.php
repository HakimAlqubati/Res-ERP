<?php

namespace App\Filament\Clusters\HRAttendanceReport\Resources;

use App\Filament\Clusters\HRAttendanceReport\Resources\EmployeeRatingReportResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;

class ListEmployeeRatingReports2 extends ListRecords
{
    protected static string $resource = EmployeeRatingReportResource::class;

    // protected static string $view = 'filament.pages.hr-reports.attendance.pages.attendance-employees';
    
    public function getTableRecordKey(Model $record): string
    {
        $attributes = $record->getAttributes();

        return $attributes['employee_id'];
    }
}
