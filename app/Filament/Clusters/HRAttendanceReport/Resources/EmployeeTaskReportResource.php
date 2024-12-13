<?php

namespace App\Filament\Clusters\HRAttendanceReport\Resources;

use App\Filament\Clusters\HRAttendanceReport;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Task;
use App\Models\TaskLog;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;
use Filament\Forms\Get;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextColumn\TextColumnSize;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class EmployeeTaskReportResource extends Resource
{
    protected static ?string $model = Task::class;
    protected static ?string $slug = 'employee-task-report';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = HRAttendanceReport::class;
    protected static ?string $label = 'Employee tasks';

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 4;

    public static function table(Table $table): Table
    {

        return $table
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(50)
            ->emptyStateHeading('No data')->striped()
            ->columns([
                TextColumn::make('employee_id')->label('Employee id')->searchable(isGlobal: true)->alignCenter(true)->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('employee_no')->label('NO.')->searchable(isGlobal: true)->alignCenter(true),

                TextColumn::make('employee_name')->label('Employee name')
                    ->searchable(isGlobal: true)->wrap(),

                TextColumn::make('task_id')
                    ->tooltip(fn($record): string => $record->title . '  Task no #' . $record->id)
                    ->size(TextColumnSize::Small)
                    ->color(Color::Green)
                    ->weight(FontWeight::ExtraBold)
                    // ->description('Click')
                    ->searchable()->icon('heroicon-o-eye')
                    ->label('Task id')->searchable(isGlobal: true)->alignCenter(true)

                    ->url(fn($record): string => url('admin/h-r-tasks-system/tasks/' . $record->task_id . '/edit'))->openUrlInNewTab(),
                TextColumn::make('task_title')->label('Task title')
                    ->searchable(isGlobal: true)->wrap(),
                TextColumn::make('task_status')->label('Status')->searchable(isGlobal: true)->alignCenter(true)
                    ->badge()->alignCenter(true)
                    ->icon(fn(string $state): string => match ($state) {
                        Task::STATUS_NEW => Task::ICON_NEW,
                        // Task::STATUS_PENDING =>  Task::ICON_PENDING,
                        Task::STATUS_IN_PROGRESS => Task::ICON_IN_PROGRESS,
                        Task::STATUS_CLOSED => Task::ICON_CLOSED,
                        Task::STATUS_REJECTED => Task::ICON_REJECTED,
                    })
                    ->color(fn(string $state): string => match ($state) {
                        Task::STATUS_NEW => Task::STATUS_NEW,
                        // Task::STATUS_PENDING => Task::COLOR_PENDING,
                        Task::STATUS_IN_PROGRESS => Task::COLOR_IN_PROGRESS,

                        Task::STATUS_CLOSED => Task::COLOR_CLOSED,
                        Task::STATUS_REJECTED => Task::COLOR_REJECTED,
                        // default => 'gray', // Fallback color in case of unknown status
                    }),
                TextColumn::make('total_spent_seconds')->label('Time spent')->alignCenter(true)->formatStateUsing(function ($state) {
                    if ($state === null) {
                        return '-';
                    }

                    $days = intdiv($state, 86400);
                    $state %= 86400;
                    $hours = intdiv($state, 3600);
                    $state %= 3600;
                    $minutes = intdiv($state, 60);
                    $seconds = $state % 60;

                    // Format as d h m s
                    $formattedTime = '';
                    if ($days > 0) {
                        $formattedTime .= sprintf("%dd ", $days);
                    }
                    if ($hours > 0 || $days > 0) {
                        $formattedTime .= sprintf("%dh ", $hours);
                    }
                    if ($minutes > 0 || $hours > 0 || $days > 0) {
                        $formattedTime .= sprintf("%dm ", $minutes);
                    }
                    $formattedTime .= sprintf("%ds", $seconds);

                    return trim($formattedTime);
                }),

            ])
            ->filters([
                SelectFilter::make('hr_employees.branch_id')->placeholder('Branch')
                    ->label('Branch')
                    ->options(Branch::where('active', 1)
                        ->select('name', 'id')->get()->pluck('name', 'id'))->searchable(),
                SelectFilter::make('hr_employees.id')
                    ->placeholder('Employee')
                    ->label('Employee')
                    ->getSearchResultsUsing(fn(string $search): array => Employee::where('name', 'like', "%{$search}%")->limit(5)->pluck('name', 'id')->toArray())
                    ->getOptionLabelUsing(fn($value): ?string => Employee::find($value)?->name)
                    ->searchable(),
                SelectFilter::make('hr_tasks.id')->placeholder('Task id')
                    ->label('Task id')->searchable()
                    ->getSearchResultsUsing(fn(string $search): array => Task::where('id', 'like', "%{$search}%")->limit(5)->pluck('id', 'id')->toArray())
                    ->getOptionLabelUsing(fn($value): ?string => Task::find($value)?->id),
            ], FiltersLayout::AboveContent)
            ->actions([
                Action::make('pdf')->label('PDF')
                    ->button()
                    ->action(function ($record) {
                        // Fetch data for the report
                        // $data = static::getEloquentQuery()->get();
                        $task = Task::with('steps')->find($record->task_id);
                        $employee = $task->assigned;
                        $branch = $record->branch;
                        // return view('export.reports.hr.tasks.employee-task-report2', compact('employee','branch','task'));
                        // Generate the PDF using a view
                        $pdf = Pdf::loadView('export.reports.hr.tasks.employee-task-report2', ['task' => $task, 'employee' => $employee, 'branch' => $branch]);

                        return response()->streamDownload(
                            function () use ($pdf) {
                                echo $pdf->output();
                            },
                            $employee->name . ' Task #' . $task->id . '.pdf',
                            [
                                'Content-Type' => 'application/pdf',
                                // 'Content-Disposition' => 'inline; filename="employee_task_report.pdf"',
                                'Charset' => 'UTF-8',
                                'Content-Disposition' => 'inline; filename="' . $employee->name . ' Task #' . $task->id . '.pdf"',
                                'Content-Language' => 'ar',
                                'Accept-Charset' => 'UTF-8',
                                'Content-Encoding' => 'UTF-8',
                                'direction' => 'rtl'
                            ]
                        );
                    }),
            ])
        ;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployeeTasksReport::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = Employee::select(
            'hr_employees.branch_id as branch_id',
            'hr_employees.id as employee_id',
            'hr_employees.employee_no as employee_no',
            'hr_employees.name as employee_name',
            'hr_tasks.id as task_id',
            'hr_tasks.title as task_title',
            'hr_tasks.task_status as task_status',
            DB::raw('SUM(TIME_TO_SEC(hr_task_logs.total_hours_taken)) as total_spent_seconds')

        )->join('hr_tasks', 'hr_employees.id', '=', 'hr_tasks.assigned_to')
            ->leftJoin('hr_task_logs', 'hr_tasks.id', '=', 'hr_task_logs.task_id')
            ->where('hr_task_logs.log_type', TaskLog::TYPE_MOVED)
            ->whereJsonContains('hr_task_logs.details->to', Task::STATUS_CLOSED, '!=');

        $query = $query->groupBy('hr_employees.id', 'hr_tasks.title', 'hr_employees.branch_id', 'hr_employees.employee_no', 'hr_employees.name', 'hr_tasks.id', 'hr_tasks.task_status');

        // dd($query->toSql());
        return $query->orderBy('hr_tasks.id', 'desc');
    }

    public static function canViewAny(): bool
    {
        if (isSuperAdmin() || isSystemManager()) {
            return true;
        }
        return false;
    }
}
