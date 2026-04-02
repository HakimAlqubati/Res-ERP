<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\EmployeeOvertimeResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Clusters\HRAttenanceCluster\Resources\EmployeeOvertimeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEmployeeOvertimes extends ListRecords
{
    protected static string $resource = EmployeeOvertimeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('quick_add')
                ->label('Quick Add')
                ->color('success')
                ->icon('heroicon-o-plus-circle')
                ->form([
                    \Filament\Forms\Components\Placeholder::make('info')
                        ->content('Easily add manual overtime (Hourly or Daily) for employees.')
                        ->label(''),
                    \Filament\Forms\Components\Select::make('employee_id')
                        ->label('Employee')
                        ->relationship('employee', 'name')
                        ->searchable()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, $set) {
                            $employee = \App\Models\Employee::find($state);
                            if ($employee) {
                                $set('branch_id', $employee->branch_id);
                            }
                        }),
                    \Filament\Forms\Components\Hidden::make('branch_id'),
                    \Filament\Forms\Components\Select::make('type')
                        ->label('Type')
                        ->options(\App\Models\EmployeeOvertime::getTypes())
                        ->default(\App\Models\EmployeeOvertime::TYPE_BASED_ON_DAY)
                        ->required(),
                    \Filament\Forms\Components\DatePicker::make('date')
                        ->label('Date')
                        ->default(now())
                        ->required(),
                    \Filament\Forms\Components\TextInput::make('hours')
                        ->label('Value (Hours or Days)')
                        ->helperText('Enter hours if Hourly, or number of days if Daily (e.g. 1 for Eid).')
                        ->numeric()
                        ->step(0.1)
                        ->minValue(0.1)
                        ->required(),
                    \Filament\Forms\Components\Textarea::make('reason')
                        ->label('Reason/Notes')
                        ->rows(2),
                ])
                ->action(function (array $data) {
                    \App\Models\EmployeeOvertime::create([
                        'employee_id' => $data['employee_id'],
                        'branch_id'   => $data['branch_id'] ?? \App\Models\Employee::find($data['employee_id'])?->branch_id,
                        'type'        => $data['type'],
                        'date'        => $data['date'],
                        'hours'       => $data['hours'],
                        'reason'      => $data['reason'],
                        'status'      => \App\Models\EmployeeOvertime::STATUS_PENDING,
                        'created_by'  => \Illuminate\Support\Facades\Auth::id(),
                    ]);

                    \Filament\Notifications\Notification::make()
                        ->title('Overtime added successfully')
                        ->success()
                        ->send();
                }),
            CreateAction::make()
                ->label('Manage Staff Overtime')
                ->hidden(fn() => isBranchUser()),
        ];
    }
    // public function getModelLabel(): ?string
    // {
    //     return 'Manage Staff Overtime';
    // }
}
