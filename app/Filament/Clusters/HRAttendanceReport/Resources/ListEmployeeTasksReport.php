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

    public function getTableRecordKey(Model $record): string
    {
        $attributes = $record->getAttributes();
        return $attributes['employee_id'];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')->label('Print')->icon('heroicon-o-printer')
                ->action(function () {
                    $records = EmployeeTaskReportResource::getEloquentQuery()->get();
                    // Fetch data for the report and add progress percentage
                    $data = $records->map(function($record) {
                        $task = Task::find($record->task_id);
                        $record->employee_name = $record->employee_name;
                        $record->progress_percentage = ($task ? $task->progress_percentage : 0).'%';
                        return $record;
                    });

                    // Generate the PDF using a view
                    $pdf = PDF::loadView('export.reports.hr.tasks.employee-task-report-print-all', ['data' => $data]);

                    return response()->streamDownload(
                        function () use ($pdf) {
                            echo $pdf->output();
                        },
                        'employee_tasks_report.pdf',
                        [
                            'Content-Type' => 'application/pdf',
                            'Charset' => 'UTF-8',
                            'Content-Disposition' => 'inline; filename="employee_tasks_report.pdf"',
                            'Content-Language' => 'ar',
                            'Accept-Charset' => 'UTF-8',
                            'Content-Encoding' => 'UTF-8',
                            'direction' => 'rtl'
                        ]
                    );
                }),
            // CreateAction::make(),
        ];
    }
}
