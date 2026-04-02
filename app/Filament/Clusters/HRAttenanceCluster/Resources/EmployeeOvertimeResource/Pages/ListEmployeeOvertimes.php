<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\EmployeeOvertimeResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Clusters\HRAttenanceCluster\Resources\EmployeeOvertimeResource;
use Filament\Actions\Action;
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
                            ->default(\App\Models\EmployeeOvertime::TYPE_BASED_ON_DAY)
                            ->required()
                            ->live(),
                    ]),

                    \Filament\Forms\Components\Textarea::make('reason')
                        ->label('Reason/Notes')
                        ->rows(2)
                        ->required()
                        ->placeholder('Reason for overall batch...'),

                    \Filament\Forms\Components\Repeater::make('items')
                        ->label('Staff List (Present on Date)')
                        ->schema([
                            Grid::make(3)->schema([
                                \Filament\Forms\Components\Checkbox::make('is_selected')
                                    ->label('Select')
                                    ->default(true),

                                \Filament\Forms\Components\Placeholder::make('employee_name_label')
                                    ->label('Employee')
                                    ->content(fn($get) => $get('employee_name')),

                                \Filament\Forms\Components\TextInput::make('hours')
                                    ->label('Hours')
                                    ->numeric()
                                    ->step(0.1)
                                    ->required()
                                    ->hidden(fn($get) => $get('../../type') === \App\Models\EmployeeOvertime::TYPE_BASED_ON_MONTH),
                            ]),

                            \Filament\Forms\Components\Hidden::make('employee_id'),
                            \Filament\Forms\Components\Hidden::make('employee_name'),
                        ])
                        // ->addable(false)
                        // ->deletable(false)
                        // ->reorderable(false)
                        ->columnSpanFull()
                        ->itemLabel(fn(array $state): ?string => $state['employee_name'] ?? null),
                ])
                ->action(function (array $data) {
                    $createdCount = 0;

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

                    \Filament\Notifications\Notification::make()
                        ->title("Success: Created {$createdCount} overtime records.")
                        ->success()
                        ->send();
                }),
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

        $employees = \App\Models\Employee::where('branch_id', $branchId)->active()->get();

        if ($employees->isEmpty()) {
            $set('items', []);
            return;
        }

        /** @var \App\Services\HR\AttendanceHelpers\Reports\EmployeesAttendanceOnDateService $attendanceService */
        $attendanceService = app(\App\Services\HR\AttendanceHelpers\Reports\EmployeesAttendanceOnDateService::class);
        $attendanceReport = $attendanceService->fetchAttendances($employees, $date);

        $items = [];
        foreach ($employees as $employee) {
            $report = $attendanceReport->get($employee->id);
            $isPresent = $report['attendance_report']['present'] ?? false;

            if ($isPresent) {
                // Get overtime hours from attendance if any
                $otHours = $report['attendance_report']['overtime_hours'] ?? 0;

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
