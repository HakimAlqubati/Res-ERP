<?php

namespace App\Filament\Clusters\HRCluster\Resources\EmployeeResource\RelationManagers;

use App\Models\Attendance;
use App\Models\EmployeePeriod;
use App\Models\EmployeePeriodHistory;
use App\Models\WorkPeriod;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PeriodRelationManager extends RelationManager
{
    protected static string $relationship = 'periods';
    protected static ?string $title = 'Shifts';
    // protected static ?string $badge = count($this->ownerRecord->periods);
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Grid::make()->columnSpanFull()->columns(1)->schema([
                //     ToggleButtons::make('periods')
                //         ->label('Work Periods')
                //     // ->relationship('periods', 'name')
                //         ->columns(3)->multiple()
                //         ->options(
                //             WorkPeriod::select('name', 'id')->get()->pluck('name', 'id'),
                //         )->default(function () {
                //         return $this->ownerRecord?->periods?->plucK('id')?->toArray();
                //     })
                //         ->helperText('Select the employee\'s work periods.'),
                // ]),
            ]);
    }

    public function table(Table $table): Table
    {

        // $explodeHost = explode('.', request()->getHost());

        // $count = count($explodeHost);
        // dd($this->ownerRecord,$this->ownerRecord->branch_id,$count);
        return $table
            ->recordTitleAttribute('period_id')
            ->striped()
            ->columns([
                TextColumn::make('id')->label('Id'),

                TextColumn::make('name')->label('Work period'),
                // TextColumn::make('description')->label('description'),
                TextColumn::make('start_at')->label('Start time'),
                TextColumn::make('end_at')->label('End time'),
                // TextColumn::make('days')
                //     ->label('Days')
                // ->formatStateUsing(function($state){
                //     dd($state);
                // })
                // ,
                TextColumn::make('creator.name')->label('Created by'),

            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\Action::make('createOne')->label('Add shifts')
                    ->icon('heroicon-o-plus')
                    ->form(

                        [Grid::make()->columnSpanFull()->columns(1)->schema([
                            Fieldset::make()->columnSpanFull()
                                ->label('Choose the start period date')
                                ->schema([
                                    DatePicker::make('start_period')->label('Start period date')
                                        ->default(now())
                                        ->columnSpanFull()
                                        // ->minDate(function($record){
                                        //     dd($record);
                                        //     return $record->employee->join_date?? now()->toDateString();
                                        // })
                                        // ->maxDate(now())
                                        ->required(),
                                ]),
                            ToggleButtons::make('periods')
                                ->label('Work Periods')
                                // ->relationship('periods', 'name')
                                ->columns(3)->multiple()
                                ->options(
                                    WorkPeriod::select('name', 'id')
                                        ->where('branch_id', $this->ownerRecord->branch_id)
                                        ->get()->pluck('name', 'id'),
                                )->default(function () {
                                    return $this->ownerRecord?->periods?->plucK('id')?->toArray();
                                })
                                // ->disabled(function(){
                                //     return [1,2,3,4];
                                // })
                                ->helperText('Select the employee\'s work periods.'),
                            Fieldset::make()->schema([
                                CheckboxList::make('days')
                                    ->label('Days of Work')
                                    ->columns(3)
                                    ->options(getDays())
                                    ->required()->columnSpanFull()
                                    ->bulkToggleable()
                                    ->helperText('Select the days this period applies to.'),
                            ]),
                        ])]
                    )
                    ->button()
                    ->databaseTransaction()
                    ->action(function ($data) {
                        DB::beginTransaction();
                        $employee = $this->ownerRecord;
                        $employeeHistories = $employee->periodHistories->select('period_id', 'start_date', 'end_date') ?? null;
                        foreach ($employeeHistories as  $history) {
                            if (isset($history['end_date']) && $history['end_date'] == $data['start_period']) {
                                Notification::make()
                                    ->title('Validation Error')
                                    ->body('Cannot start period on ended period date')
                                    ->warning()
                                    ->send();
                                return;
                            }
                        }
                        try {
                            // Retrieve the existing periods associated with the owner record
                            $existPeriods = $this->ownerRecord?->periods?->pluck('id')->toArray();

                            // Retrieve the periods from the incoming data
                            $dataPeriods = $data['periods'];

                            // Find the periods that are not currently associated
                            $result = array_values(array_diff($dataPeriods, $existPeriods));

                            // Validate the employee's last attendance
                            $lastAttendance = $this->ownerRecord->attendances()->latest('id')->first();
                            if ($lastAttendance && $lastAttendance->check_type === Attendance::CHECKTYPE_CHECKIN) {
                                Notification::make()
                                    ->title('Validation Error')
                                    ->body('The employee has a pending check-out. You cannot add new work periods until the check-out is recorded.')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            // Insert new periods into hr_employee_periods table
                            foreach ($result as $value) {
                                $workPeriod = WorkPeriod::find($value);
                                $periodStartAt = $workPeriod?->start_at;
                                $periodEndAt = $workPeriod?->end_at;

                                // Check for overlapping periods
                                $overlap = EmployeePeriod::query()
                                    ->join('hr_work_periods', 'hr_employee_periods.period_id', '=', 'hr_work_periods.id')
                                    ->where('hr_employee_periods.employee_id', $this->ownerRecord->id)
                                    ->where(function ($query) use ($periodStartAt, $periodEndAt) {
                                        $query->whereBetween('hr_work_periods.start_at', [$periodStartAt, $periodEndAt])
                                            ->orWhereBetween('hr_work_periods.end_at', [$periodStartAt, $periodEndAt])
                                            ->orWhere(function ($query) use ($periodStartAt, $periodEndAt) {
                                                $query->where('hr_work_periods.start_at', '<=', $periodStartAt)
                                                    ->where('hr_work_periods.end_at', '>=', $periodEndAt);
                                            });
                                    })
                                    ->exists();

                                if ($overlap) {
                                    // If overlap exists, throw an exception
                                    Notification::make()->title('Error')->body('Overlapping periods are not allowed.')->warning()->send();
                                    return;
                                }


                                // Insert into hr_employee_periods
                                EmployeePeriod::insert([
                                    'employee_id' => $this->ownerRecord->id, // Assuming the ownerRecord has the employee ID
                                    'period_id' => $value,
                                    'days' => json_encode($data['days']),
                                ]);

                                // Also insert into hr_employee_period_histories
                                EmployeePeriodHistory::insert([
                                    'employee_id' => $this->ownerRecord->id, // Assuming the ownerRecord has the employee ID
                                    'period_id' => $value,
                                    'start_date' => $data['start_period'],
                                    'end_date' => null,
                                    'start_time' => $periodStartAt,
                                    'end_time' => $periodEndAt,
                                    'days' => json_encode($data['days']),
                                ]);
                            }

                            // Send notification after the operation is complete
                            Notification::make()->title('Done')->success()->send();
                            DB::commit();
                        } catch (\Exception $e) {
                            // Handle the exception
                            DB::rollBack();
                            Log::alert('Error adding new periods: ' . $e->getMessage());
                            Notification::make()->title('Error')->body($e->getMessage())->warning()->send();
                            // You can also log the error or take other actions as needed
                        }
                    }),
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
                Action::make('Delete')->requiresConfirmation()
                    ->color('warning')
                    ->button()
                    ->form([
                        Fieldset::make()
                            ->label('Choose the end period date')
                            ->columnSpanFull()
                            ->schema([
                                DatePicker::make('end_period')
                                    ->columnSpanFull()
                                    ->default(now())
                                    ->minDate(now()->subDay())
                                    ->maxDate(now())
                                    ->label('End period date')->required(),
                            ])
                    ])
                    ->databaseTransaction()
                    ->icon('heroicon-o-x-mark')
                    ->action(function ($record, $data) {

                        try {
                            // Update the end_date in hr_employee_period_histories
                            // Validate the employee's last attendance
                            $lastAttendance = $this->ownerRecord->attendances()->latest('id')->first();
                            if ($lastAttendance && $lastAttendance->check_type === Attendance::CHECKTYPE_CHECKIN) {
                                Notification::make()
                                    ->title('Validation Error')
                                    ->body('The employee has a pending check-out. You cannot add new work periods until the check-out is recorded.')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            EmployeePeriodHistory::where('employee_id', $record->employee_id) // Filter by the employee ID
                                ->where('period_id', $record->period_id) // Filter by the associated period ID
                                ->update(['end_date' => $data['end_period']]); // Set end_date to the current timestamp

                            // Now, delete the employee period
                            EmployeePeriod::where('employee_id', $record->employee_id) // Ensure to delete the correct employee period
                                ->where('period_id', $record->period_id) // Filter by the associated period ID
                                ->delete();

                            // Optionally, send a success notification
                            Notification::make()->title('Deleted')->success()->send();
                        } catch (\Exception $e) {
                            // Handle the exception
                            Notification::make()->title('Error')->message($e->getMessage())->danger()->send();
                            // You can also log the error if needed
                            // Log::error($e);
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected function canCreate(): bool
    {
        if (isSuperAdmin() || isBranchManager() || isSystemManager()) {
            return true;
        }
        return false;
    }
}
