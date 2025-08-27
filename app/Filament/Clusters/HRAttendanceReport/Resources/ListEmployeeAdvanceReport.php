<?php

namespace App\Filament\Clusters\HRAttendanceReport\Resources;


use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;

class ListEmployeeAdvanceReport extends ListRecords
{
    protected static string $resource = EmployeeAdvanceReportResource::class;

    // protected static string $view = 'filament.pages.hr-reports.attendance.pages.attendance-employees';
    
    public function getTableRecordKey(Model|array $record): string
    {
        $attributes = $record->getAttributes();
        return $attributes['employee_id'];
    }

    
}
