<?php

namespace App\Filament\Clusters\HRAttendanceReport\Resources;

use App\Exports\EmployeeAdvanceReportExport;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Facades\Excel;

class ListEmployeeAdvanceReport extends ListRecords
{
    protected static string $resource = EmployeeAdvanceReportResource::class;

    // protected static string $view = 'filament.pages.hr-reports.attendance.pages.attendance-employees';

    public function getTableRecordKey(Model|array $record): string
    {
        $attributes = $record->getAttributes();
        return $attributes['employee_id'] . '-' . $attributes['application_id'];
    }

    protected function getHeaderActions(): array
    {
        return [
            static::getExportExcelAction(),
        ];
    }

    public static function getExportExcelAction(string $actionClass = Action::class)
    {
        return $actionClass::make('export_excel')
            ->label('Export Excel')
            ->icon('heroicon-o-document-arrow-down')
            ->color('success')
            ->action(function ($livewire) {
                $query = method_exists($livewire, 'getFilteredTableQuery')
                    ? $livewire->getFilteredTableQuery()
                    : static::getResource()::getEloquentQuery();

                if ((clone $query)->count() === 0) {
                    Notification::make()
                        ->title('No records found')
                        ->body('There are no records to export for the current filters.')
                        ->warning()
                        ->send();

                    return null;
                }

                @ini_set('memory_limit', '512M');
                @set_time_limit(300);

                $columns = EmployeeAdvanceReportExport::resolveTableColumns($livewire);
                $fileName = 'employee_advance_report_' . now()->format('Y-m-d_His') . '.xlsx';

                return Excel::download(
                    new EmployeeAdvanceReportExport($query, $columns),
                    $fileName
                );
            });
    }
}
