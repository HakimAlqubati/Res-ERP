<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\EmployeeOvertimeResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Clusters\HRAttenanceCluster\Resources\EmployeeOvertimeResource;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Grid;

class ListEmployeeOvertimes extends ListRecords
{
    protected static string $resource = EmployeeOvertimeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('quick_add')
                ->label('Batch Quick Add')
                ->color('success')
                ->icon('heroicon-o-users')
                ->schema([
                    Grid::make(3)->schema([
                        \Filament\Forms\Components\Select::make('branch_id')
                            ->label('Branch')
                            ->options(\App\Models\Branch::pluck('name', 'id'))
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn($set, $state, $get) => $this->updateStaffList($set, $state, $get('date'))),

                        \Filament\Forms\Components\DatePicker::make('date')
                            ->label('Date')
                            ->required()
                            ->default(now())
                            ->live()
                            ->afterStateUpdated(fn($set, $state, $get) => $this->updateStaffList($set, $get('branch_id'), $state)),

                        \Filament\Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options(\App\Models\EmployeeOvertime::getTypes())
                            ->default(\App\Models\EmployeeOvertime::TYPE_BASED_ON_MONTH)
                            ->required()
                            ->live()
                            ->rules([
                                fn($get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                    $items = $get('items');
                                    $date = $get('date');

                                    if (!is_array($items) || !$date || !$value) {
                                        return;
                                    }

                                    $selectedEmployees = collect($items)
                                        ->where('is_selected', true)
                                        ->mapWithKeys(fn($item) => [$item['employee_id'] => $item['employee_name'] ?? 'Unknown']);

                                    if ($selectedEmployees->isEmpty()) {
                                        return;
                                    }

                                    $existingIds = \App\Models\EmployeeOvertime::query()
                                        ->where('date', $date)
                                        ->where('type', $value)
                                        ->whereIn('employee_id', $selectedEmployees->keys()->toArray())
                                        ->pluck('employee_id')
                                        ->toArray();

                                    if (!empty($existingIds)) {
                                        $names = collect($existingIds)->map(fn($id) => $selectedEmployees[$id] ?? $id)->implode(' - ');
                                        $fail(__("Duplicate entry: The following employees already have an overtime record for this date and type: :names", ['names' => $names]));
                                    }
                                },
                            ]),
                    ]),

                    \Filament\Forms\Components\Textarea::make('reason')
                        ->label('Reason/Notes')
                        ->rows(2)
                        ->required()
                        ->placeholder('Reason for overall batch...'),

                    \Filament\Forms\Components\Repeater::make('items')
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

                            \Filament\Forms\Components\Checkbox::make('is_selected')
                                ->label('Select')
                                ->extraAttributes([
                                    'class' => 'text-center',
                                ])
                                ->default(true),

                            \Filament\Forms\Components\Placeholder::make('employee_name_label')
                                ->label('')
                                ->hiddenLabel()
                                ->content(fn($get) => $get('employee_name')),

                            \Filament\Forms\Components\TextInput::make('hours')
                                ->label('Hours')

                                ->extraInputAttributes([
                                    'class' => 'text-center',
                                ])
                                ->numeric()
                                ->required(fn($get) => $get('../../type') !== \App\Models\EmployeeOvertime::TYPE_BASED_ON_MONTH)
                                ->hidden(fn($get) => $get('../../type') === \App\Models\EmployeeOvertime::TYPE_BASED_ON_MONTH),


                            \Filament\Forms\Components\Hidden::make('employee_id'),
                            \Filament\Forms\Components\Hidden::make('employee_name'),
                        ])
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->columnSpanFull()
                        ->itemLabel(fn(array $state): ?string => $state['employee_name'] ?? null),
                ])
                ->action(function (array $data) {
                    $createdCount = 0;

                    \Illuminate\Support\Facades\DB::beginTransaction();

                    try {
                        foreach ($data['items'] as $item) {
                            if (!$item['is_selected']) continue;

                            $employee = \App\Models\Employee::find($item['employee_id']);
                            $hours = $item['hours'] ?? 0;

                            if ($data['type'] === \App\Models\EmployeeOvertime::TYPE_BASED_ON_MONTH) {
                                $hours = $employee?->working_hours ?? 8;
                            }

                            \App\Models\EmployeeOvertime::create([
                                'employee_id' => $item['employee_id'],
                                'branch_id'   => $data['branch_id'],
                                'type'        => $data['type'],
                                'date'        => $data['date'],
                                'hours'       => $hours,
                                'reason'      => $data['reason'],
                                'status'      => \App\Models\EmployeeOvertime::STATUS_PENDING,
                                'created_by'  => \Illuminate\Support\Facades\Auth::id(),
                            ]);

                            $createdCount++;
                        }

                        \Illuminate\Support\Facades\DB::commit();

                        \Filament\Notifications\Notification::make()
                            ->title("Success: Created {$createdCount} overtime records.")
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\DB::rollBack();

                        \Filament\Notifications\Notification::make()
                            ->title("Error: Failed to create overtime records.")
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->visible(
                    fn() => isSuperAdmin()
                        || isSystemManager()
                        || isBranchManager()
                ),
            CreateAction::make()
                ->label('Manage Staff Overtime')
                ->hidden(fn() => isBranchUser()),
        ];
    }

    protected function updateStaffList($set, $branchId, $date): void
    {
        if (!$branchId || !$date) {
            $set('items', []);
            return;
        }

        $employees = \App\Models\Employee::select('id', 'name', 'working_hours')
            ->where('branch_id', $branchId)->active()->get();

        if ($employees->isEmpty()) {
            $set('items', []);
            return;
        }
        /** @var \App\Services\HR\AttendanceHelpers\Reports\EmployeesAttendanceOnDateService $attendanceService */
        $attendanceService = app(\App\Services\HR\AttendanceHelpers\Reports\EmployeesAttendanceOnDateService::class);
        $attendanceReport = $attendanceService->fetchAttendances($employees, $date);

        $items = [];
        $dateString = is_string($date) ? substr($date, 0, 10) : $date->toDateString();

        foreach ($employees as $employee) {
            $report = $attendanceReport->get($employee->id);

            if (!isset($report['attendance_report'])) {
                continue;
            }

            $attendanceData = $report['attendance_report'];
            $dayData = $attendanceData->get($dateString);

            if (!$dayData) {
                continue;
            }

            $dayStatus = $dayData['day_status'] ?? null;
            $isPresent = in_array($dayStatus, [
                \App\Enums\HR\Attendance\AttendanceReportStatus::Present->value,
                \App\Enums\HR\Attendance\AttendanceReportStatus::IncompleteCheckinOnly->value,
                \App\Enums\HR\Attendance\AttendanceReportStatus::IncompleteCheckoutOnly->value,
                \App\Enums\HR\Attendance\AttendanceReportStatus::Partial->value,
            ]);

            if ($isPresent) {
                $otHours = 0;
                $totalApprovedOvertime = $attendanceData->get('total_approved_overtime');
                if ($totalApprovedOvertime && preg_match('/^(\d+):(\d+):(\d+)$/', $totalApprovedOvertime, $matches)) {
                    $otHours = round($matches[1] + ($matches[2] / 60) + ($matches[3] / 3600), 2);
                }

                $items[] = [
                    'employee_id'   => $employee->id,
                    'employee_name' => $employee->name,
                    'hours'         => $otHours > 0 ? $otHours : 0,
                    'is_selected'   => true,
                ];
            }
        }

        $set('items', $items);
    }
    // public function getModelLabel(): ?string
    // {
    //     return 'Manage Staff Overtime';
    // }
}
