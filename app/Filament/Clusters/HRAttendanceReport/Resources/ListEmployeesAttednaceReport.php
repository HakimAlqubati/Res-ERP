<?php

namespace App\Filament\Clusters\HRAttendanceReport\Resources;


use App\Filament\Clusters\HRAttendanceReport\Resources\EmployeesAttednaceReportResource;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\WeeklyHoliday;
use App\Models\WorkPeriod;
use Carbon\Carbon;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ListEmployeesAttednaceReport extends ListRecords
{
    protected static string $resource = EmployeesAttednaceReportResource::class;

     

    public function getTableRecordKey(Model $record): string
    {
        $attributes = $record->getAttributes();

        return $attributes['employee_id'];
    }
   

}
