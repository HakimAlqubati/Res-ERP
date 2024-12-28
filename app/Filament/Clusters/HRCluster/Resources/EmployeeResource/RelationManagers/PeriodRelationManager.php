<?php

namespace App\Filament\Clusters\HRCluster\Resources\EmployeeResource\RelationManagers;

use App\Models\Attendance;
use App\Models\EmployeePeriod;
use App\Models\EmployeePeriodHistory;
use App\Models\WorkPeriod;
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
                TextColumn::make('creater.name')->label('Created by'),

            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\Action::make('createOne')->label('Add shifts')
                    ->icon('heroicon-o-plus')
                    ->form(
                        [Grid::make()->columnSpanFull()->columns(1)->schema([
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
                        ])]
                    )
                    ->button()
                    ->databaseTransaction()
                    ->action(function ($data) {
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
                                ]);

                                // Also insert into hr_employee_period_histories
                                EmployeePeriodHistory::insert([
                                    'employee_id' => $this->ownerRecord->id, // Assuming the ownerRecord has the employee ID
                                    'period_id' => $value,
                                    'start_date' => now(),
                                    'end_date' => null,
                                    'start_time' => $periodStartAt,
                                    'end_time' => $periodEndAt,
                                ]);
                            }

                            // Send notification after the operation is complete
                            Notification::make()->title('Done')->success()->send();
                        } catch (\Exception $e) {
                            // Handle the exception
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
                    ->databaseTransaction()
                    ->icon('heroicon-o-x-mark')
                    ->action(function ($record) {

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
                                ->update(['end_date' => now()]); // Set end_date to the current timestamp

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
