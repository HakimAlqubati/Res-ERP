<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\EmployeeOvertimeResource\Actions;

use App\Enums\HR\Attendance\AttendanceReportStatus;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeOvertime;
use App\Modules\HR\Overtime\OvertimeService;
use App\Services\HR\AttendanceHelpers\Reports\EmployeesAttendanceOnDateService;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HeaderActions
{
    public static function batchQuickAdd(): Action
    {
        return Action::make('quick_add')
            ->label('Batch Quick Add')
            ->color('success')
            ->icon('heroicon-o-users')
            ->schema([
                Grid::make(3)->schema([
                    DatePicker::make('date')
                        ->label('Date')
                        ->required()
                        ->default(now())
                        ->live()
                        ->afterStateUpdated(fn ($set, $state, $get) => self::updateStaffList($set, $get('branch_id'), $state)),

                    Select::make('branch_id')
                        ->label('Branch')
                        ->options(Branch::pluck('name', 'id'))
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn ($set, $state, $get) => self::updateStaffList($set, $state, $get('date'))),

                    Select::make('type')
                        ->label('Type')
                        ->options(EmployeeOvertime::getTypes())
                        ->default(EmployeeOvertime::TYPE_BASED_ON_MONTH)
                        ->required()
                        ->live()
                        ->rules([
                            fn ($get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                $items = $get('items');
                                $date = $get('date');

                                if (! is_array($items) || ! $date || ! $value) {
                                    return;
                                }

                                $selectedEmployees = collect($items)
                                    ->where('is_selected', true)
                                    ->mapWithKeys(fn ($item) => [$item['employee_id'] => $item['employee_name'] ?? 'Unknown']);

                                if ($selectedEmployees->isEmpty()) {
                                    return;
                                }

                                $existingIds = EmployeeOvertime::query()
                                    ->where('date', $date)
                                    ->where('type', $value)
                                    ->whereIn('employee_id', $selectedEmployees->keys()->toArray())
                                    ->pluck('employee_id')
                                    ->toArray();

                                if (! empty($existingIds)) {
                                    $names = collect($existingIds)->map(fn ($id) => $selectedEmployees[$id] ?? $id)->implode(' - ');
                                    $fail(__('Duplicate entry: The following employees already have an overtime record for this date and type: :names', ['names' => $names]));
                                }
                            },
                        ]),
                ]),

                Textarea::make('reason')
                    ->label('Reason/Notes')
                    ->rows(2)
                    ->required()
                    ->placeholder('Reason for overall batch...'),

                Repeater::make('items')
                    ->label('Staff List (Present on Date)')
                    ->table([
                        TableColumn::make('Select')
                            ->alignCenter()
                            ->width('20%'),
                        TableColumn::make('Employee')
                            ->alignCenter()
                            ->width('50%'),
                        TableColumn::make('Hours')
                            ->alignCenter()
                            ->width('30%'),
                    ])
                    ->schema([

                        Checkbox::make('is_selected')
                            ->label('Select')
                            ->extraAttributes([
                                'class' => 'text-center',
                            ])
                            ->default(true),

                        Placeholder::make('employee_name_label')
                            ->label('')
                            ->hiddenLabel()
                            ->content(fn ($get) => $get('employee_name')),

                        TextInput::make('hours')
                            ->label('Hours')

                            ->extraInputAttributes([
                                'class' => 'text-center',
                            ])
                            ->numeric()
                            ->required(fn ($get) => $get('../../type') !== EmployeeOvertime::TYPE_BASED_ON_MONTH)
                            ->hidden(fn ($get) => $get('../../type') === EmployeeOvertime::TYPE_BASED_ON_MONTH),

                        Hidden::make('employee_id'),
                        Hidden::make('employee_name'),
                    ])
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(false)
                    ->columnSpanFull()
                    ->itemLabel(fn (array $state): ?string => $state['employee_name'] ?? null),
            ])
            ->action(function (array $data) {
                $createdCount = 0;

                DB::beginTransaction();

                try {
                    foreach ($data['items'] as $item) {
                        if (! $item['is_selected']) {
                            continue;
                        }

                        $employee = Employee::find($item['employee_id']);
                        $hours = $item['hours'] ?? 0;

                        if ($data['type'] === EmployeeOvertime::TYPE_BASED_ON_MONTH) {
                            $hours = $employee?->working_hours ?? 8;
                        }

                        EmployeeOvertime::create([
                            'employee_id' => $item['employee_id'],
                            'branch_id' => $data['branch_id'],
                            'type' => $data['type'],
                            'date' => $data['date'],
                            'hours' => $hours,
                            'reason' => $data['reason'],
                            'status' => EmployeeOvertime::STATUS_PENDING,
                            'created_by' => Auth::id(),
                        ]);

                        $createdCount++;
                    }

                    DB::commit();

                    Notification::make()
                        ->title("Success: Created {$createdCount} overtime records.")
                        ->success()
                        ->send();
                } catch (\Exception $e) {
                    DB::rollBack();

                    Notification::make()
                        ->title('Error: Failed to create overtime records.')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            })
            ->visible(
                fn () => isSuperAdmin()
                    || isSystemManager()
                    || isBranchManager()
            );
    }

    public static function autoProcess(): Action
    {
        return Action::make('auto_process')
            ->label('Auto Process Suggested Overtime')
            ->icon('heroicon-o-bolt')
            ->color('info')
            ->requiresConfirmation()
            ->modalHeading('Process Suggested Overtime')
            ->modalDescription('The system will automatically calculate and store suggested overtime for the selected date range and branch.')
            ->modalSubmitActionLabel('Process Now')
            ->schema([
                Grid::make(2)->schema([
                    DatePicker::make('from_date')
                        ->label('From Date')
                        ->required()
                        ->default(now()),
                    DatePicker::make('to_date')
                        ->label('To Date')
                        ->required()
                        ->default(now()),
                    Select::make('branch_id')
                        ->label('Branch')
                        ->options(Branch::active()->pluck('name', 'id'))
                        ->required()
                        ->searchable()
                        ->preload()
                        ->columnSpanFull(),
                ]),
            ])
            ->action(function (array $data) {
                try {
                    $service = app(OvertimeService::class);
                    $totalResults = [];

                    $startDate = Carbon::parse($data['from_date']);
                    $endDate = Carbon::parse($data['to_date']);

                    for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
                        $results = $service->autoProcessSuggestedOvertime($date->format('Y-m-d'), (int) $data['branch_id']);

                        foreach ($results as $branch => $result) {
                            if (is_numeric($result)) {
                                $current = isset($totalResults[$branch]) && is_numeric($totalResults[$branch]) ? $totalResults[$branch] : 0;
                                $totalResults[$branch] = $current + (int) $result;
                            } else {
                                $totalResults[$branch] = $result;
                            }
                        }
                    }

                    $summary = collect($totalResults)->map(function ($result, $branch) {
                        $status = is_numeric($result) ? "{$result} records created" : $result;

                        return "**{$branch}**: {$status}";
                    })->implode("\n");

                    Notification::make()
                        ->title('Overtime Processing Results')
                        ->body($summary ?: 'No records processed.')
                        ->success()
                        ->send();
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Processing Failed')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            })
            ->visible(fn () => isSuperAdmin() || isSystemManager() || isBranchManager());
    }

    protected static function updateStaffList($set, $branchId, $date): void
    {
        if (! $branchId || ! $date) {
            $set('items', []);

            return;
        }

        $employees = Employee::select('id', 'name', 'working_hours')
            ->where('branch_id', $branchId)->active()->get();

        if ($employees->isEmpty()) {
            $set('items', []);

            return;
        }
        /** @var EmployeesAttendanceOnDateService $attendanceService */
        $attendanceService = app(EmployeesAttendanceOnDateService::class);
        $attendanceReport = $attendanceService->fetchAttendances($employees, $date);

        $items = [];
        $dateString = is_string($date) ? substr($date, 0, 10) : $date->toDateString();

        foreach ($employees as $employee) {
            $report = $attendanceReport->get($employee->id);

            if (! isset($report['attendance_report'])) {
                continue;
            }

            $attendanceData = $report['attendance_report'];
            $dayData = $attendanceData->get($dateString);

            if (! $dayData) {
                continue;
            }

            $dayStatus = $dayData['day_status'] ?? null;
            $isPresent = in_array($dayStatus, [
                AttendanceReportStatus::Present->value,
                AttendanceReportStatus::IncompleteCheckinOnly->value,
                AttendanceReportStatus::IncompleteCheckoutOnly->value,
                AttendanceReportStatus::Partial->value,
            ]);

            if ($isPresent) {
                $otHours = 0;
                $totalApprovedOvertime = $attendanceData->get('total_approved_overtime');
                if ($totalApprovedOvertime && preg_match('/^(\d+):(\d+):(\d+)$/', $totalApprovedOvertime, $matches)) {
                    $otHours = round($matches[1] + ($matches[2] / 60) + ($matches[3] / 3600), 2);
                }

                $items[] = [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->name,
                    'hours' => $otHours > 0 ? $otHours : 0,
                    'is_selected' => true,
                ];
            }
        }

        $set('items', $items);
    }
}
