<?php

namespace App\Filament\Clusters\HRAttendanceReport\Resources;

use App\Filament\Clusters\HRAttendanceReport\Resources\EmployeeRatingReportResource;
use Closure;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;

class ListEmployeeTasksReport extends ListRecords
{
    protected static string $resource = EmployeeTaskReportResource::class;

    // protected static string $view = 'filament.pages.hr-reports.attendance.pages.attendance-employees';
    
    public function getTableRecordKey(Model $record): string
    {
        $attributes = $record->getAttributes();
        return $attributes['employee_id'];
    }

    
}
