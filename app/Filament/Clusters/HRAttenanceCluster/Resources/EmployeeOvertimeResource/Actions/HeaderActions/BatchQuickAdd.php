<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\EmployeeOvertimeResource\Actions\HeaderActions;

use App\Enums\HR\Attendance\AttendanceReportStatus;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeOvertime;
use App\Modules\HR\AttendanceReports\Services\EmployeesAttendanceOnDateService;
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
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class BatchQuickAdd
{
    public static function action()
    {
        return Action::make('quick_add')
            ->label('Batch Quick Add')
            ->color('success')
            ->icon('heroicon-o-users')
            ->closeModalByClickingAway(false)
            ->closeModalByEscaping(false)
            ->modalHeading('Batch Add Overtime')
            ->modalDescription('Add Overtime For Multiple Employees')
            ->modalAutofocus()
            ->modalIcon(Heroicon::Clock)
            ->modalAlignment()
            ->modalWidth(Width::ScreenExtraLarge)
            ->modalCloseButton(true)
            // ->modalSubmitActionLabel('')
            ->schema([
                Fieldset::make()
                    ->columns(1)
                    ->columnSpanFull()->schema([
                        Grid::make(4)->columnSpanFull()->schema([
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
                            DatePicker::make('date')
                                ->label('Date')
                                ->required()
                                ->default(now())
                                ->live()
                                ->afterStateUpdated(function ($set, $state, $get) {
                                    self::updateStaffList($set, $get('branch_id'), $state, $get('show_all'));
                                    $set('select_all', true);
                                }),

                            Toggle::make('show_all')
                                ->label('Show All Employees (Present & Absent)')
                                ->default(false)
                                ->live()
                                ->inline(false)
                                ->afterStateUpdated(function ($set, $state, $get) {
                                    self::updateStaffList($set, $get('branch_id'), $get('date'), $state);
                                    $set('select_all', true);
                                }),
                            Toggle::make('select_all')
                                ->label('Toggle All')
                                ->default(true)
                                ->inline(false)
                                ->live()
                                ->afterStateUpdated(function ($state, $set, $get) {
                                    $items = $get('items');
                                    if (! is_array($items)) {
                                        return;
                                    }
                                    $updated = array_map(function ($item) use ($state) {
                                        $item['is_selected'] = (bool) $state;

                                        return $item;
                                    }, $items);
                                    $set('items', $updated);
                                }),
                            Select::make('branch_id')
                                ->label('Branch')
                                ->options(Branch::normal()
                                ->forBranchManager()
                                ->pluck('name', 'id'))
                                ->required()
                                ->multiple()->columnSpanFull()
                                ->preload(false)
                                ->live()
                                ->searchable()
                                ->afterStateUpdated(function ($set, $state, $get) {
                                    self::updateStaffList($set, $state, $get('date'), $get('show_all'));
                                    $set('select_all', true);
                                }),

                        ]),

                        Textarea::make('reason')
                            ->label('Reason/Notes')
                            ->rows(2)
                            ->required()
                            ->placeholder('Reason for overall batch...'),

                        Repeater::make('items')
                            ->label('Staff List')
                            ->table([
                                TableColumn::make('Select')
                                    // ->alignCenter()
                                    ->width('10%'),
                                TableColumn::make('Employee')
                                    // ->alignCenter()
                                    ->width('45%'),
                                TableColumn::make('Branch')
                                    // ->alignCenter()
                                    ->width('25%'),
                                TableColumn::make('Hours')
                                    ->alignCenter()
                                    ->width('20%'),
                            ])
                            ->schema([

                                Checkbox::make('is_selected')
                                    ->label('Select')
                                    ->extraAttributes([
                                        'class' => 'text-center',
                                    ])
                                    // ->disabled(fn ($get) => (bool) $get('is_absent'))
                                    ->default(true),

                                Placeholder::make('employee_name_label')
                                    ->label('')
                                    ->hiddenLabel()
                                    ->content(function ($get) {
                                        $name = $get('employee_name');
                                        if ($get('is_absent')) {
                                            return new HtmlString(
                                                '<span style="display:inline-flex;align-items:center;gap:6px;">'.
                                                '<span style="background:#ef4444;color:#fff;font-size:10px;font-weight:700;padding:2px 7px;border-radius:999px;letter-spacing:.5px;">ABSENT</span>'.
                                                '<span style="color:#6b7280;">'.e($name).'</span>'.
                                                '</span>'
                                            );
                                        }

                                        return $name;
                                    }),
                                Placeholder::make('branch_name_label')
                                    ->label('')
                                    ->hiddenLabel()
                                    ->content(fn ($get) => $get('branch_name') ?? '—'),
                                TextInput::make('hours')
                                    ->label('Hours')
                                    ->extraInputAttributes([
                                        'class' => 'text-center',
                                    ])
                                    ->numeric()
                                    ->default(1)
                                    ->hint(fn ($get) => $get('is_absent') ? '(default)' : null)
                                    ->hintColor('gray')
                                    ->required(fn ($get) => $get('../../type') !== EmployeeOvertime::TYPE_BASED_ON_MONTH)
                                    ->hidden(fn ($get) => $get('../../type') === EmployeeOvertime::TYPE_BASED_ON_MONTH),

                                Hidden::make('employee_id'),
                                Hidden::make('employee_name'),
                                Hidden::make('branch_id'),
                                Hidden::make('branch_name'),
                                Hidden::make('is_absent'),
                            ])
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->columnSpanFull()
                            ->itemLabel(fn (array $state): ?string => $state['employee_name'] ?? null),
                    ]),
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
                            'branch_id' => $item['branch_id'],
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

    protected static function updateStaffList($set, $branchIds, $date, $showAll = '0'): void
    {
        $branchIds = is_array($branchIds) ? array_filter($branchIds) : [];

        if (empty($branchIds) || ! $date) {
            $set('items', []);

            return;
        }

        // Load branch names for display
        $branchNames = Branch::whereIn('id', $branchIds)->pluck('name', 'id');

        $employees = Employee::select('id', 'name', 'working_hours', 'branch_id')
            ->whereIn('branch_id', $branchIds)->active()->get();

        if ($employees->isEmpty()) {
            $set('items', []);

            return;
        }

        /** @var EmployeesAttendanceOnDateService $attendanceService */
        $attendanceService = app(EmployeesAttendanceOnDateService::class);
        $attendanceReport = $attendanceService->fetchAttendances($employees, $date);

        $presentItems = [];
        $absentItems = [];
        $dateString = is_string($date) ? substr($date, 0, 10) : $date->toDateString();

        foreach ($employees as $employee) {
            $branchName = $branchNames[$employee->branch_id] ?? '—';
            $report = $attendanceReport->get($employee->id);

            if (! isset($report['attendance_report'])) {
                if ($showAll) {
                    $absentItems[] = [
                        'employee_id' => $employee->id,
                        'employee_name' => $employee->name,
                        'branch_id' => $employee->branch_id,
                        'branch_name' => $branchName,
                        'hours' => 1,
                        'is_selected' => true,
                        'is_absent' => true,
                    ];
                }

                continue;
            }

            $attendanceData = $report['attendance_report'];
            $dayData = $attendanceData->get($dateString);

            if (! $dayData) {
                if ($showAll) {
                    $absentItems[] = [
                        'employee_id' => $employee->id,
                        'employee_name' => $employee->name,
                        'branch_id' => $employee->branch_id,
                        'branch_name' => $branchName,
                        'hours' => 1,
                        'is_selected' => true,
                        'is_absent' => true,
                    ];
                }

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

                $presentItems[] = [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->name,
                    'branch_id' => $employee->branch_id,
                    'branch_name' => $branchName,
                    'hours' => $otHours > 0 ? $otHours : 0,
                    'is_selected' => true,
                    'is_absent' => false,
                ];
            } elseif ($showAll) {
                $absentItems[] = [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->name,
                    'branch_id' => $employee->branch_id,
                    'branch_name' => $branchName,
                    'hours' => 1,
                    'is_selected' => true,
                    'is_absent' => true,
                ];
            }
        }

        // Present employees first, absent employees at the bottom
        $set('items', array_merge($presentItems, $absentItems));
    }
}
