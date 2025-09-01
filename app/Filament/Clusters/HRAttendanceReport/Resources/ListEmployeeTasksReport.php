<?php

namespace App\Filament\Clusters\HRAttendanceReport\Resources;

use App\Filament\Clusters\HRAttendanceReport\Resources\EmployeeRatingReportResource;
use App\Models\Task;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;

class ListEmployeeTasksReport extends ListRecords
{
    protected static string $resource = EmployeeTaskReportResource::class;

    // protected static string $view = 'filament.pages.hr-reports.attendance.pages.attendance-employees';

    public function getTableRecordKey(Model|array $record): string
    {
        $attributes = $record->getAttributes();
        return $attributes['employee_id'];
    }

    protected function getHeaderActions(): array
    {
        return [
         

        ];
    }
}
