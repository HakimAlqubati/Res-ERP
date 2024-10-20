<?php

namespace App\Filament\Clusters\HRCluster\Resources\EmployeeResource\RelationManagers;

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
        return $table
            ->recordTitleAttribute('period_id')
            ->striped()
            ->columns([
                TextColumn::make('id')->label('Id'),

                TextColumn::make('name')->label('Work Period'),
                // TextColumn::make('description')->label('description'),
                TextColumn::make('start_at')->label('Start time'),
                TextColumn::make('end_at')->label('End time'),

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
                                    WorkPeriod::select('name', 'id')->get()->pluck('name', 'id'),
                                )->default(function () {
                                return $this->ownerRecord?->periods?->plucK('id')?->toArray();
                            })
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

                            // Insert new periods into hr_employee_periods table
                            foreach ($result as $value) {
                                // Insert into hr_employee_periods
                                DB::table('hr_employee_periods')->insert([
                                    'employee_id' => $this->ownerRecord->id, // Assuming the ownerRecord has the employee ID
                                    'period_id' => $value,
                                ]);

                                // Also insert into hr_employee_period_histories
                                DB::table('hr_employee_period_histories')->insert([
                                    'employee_id' => $this->ownerRecord->id, // Assuming the ownerRecord has the employee ID
                                    'period_id' => $value,
                                    'start_date' => now(), // You can set this to the current time or adjust as needed
                                    'end_date' => null, // Set to null if the period is currently active
                                ]);
                            }

                            // Send notification after the operation is complete
                            Notification::make()->title('Done')->success()->send();

                        } catch (\Exception $e) {
                            // Handle the exception
                            Notification::make()->title('Error')->body($e->getMessage())->warning()->send();
                            // You can also log the error or take other actions as needed
                        }

                    })
                ,
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
                            
                            DB::table('hr_employee_period_histories')
                                ->where('employee_id', $record->employee_id) // Filter by the employee ID
                                ->where('period_id', $record->period_id) // Filter by the associated period ID
                                ->update(['end_date' => now()]); // Set end_date to the current timestamp

                            // Now, delete the employee period
                            DB::table('hr_employee_periods')
                                ->where('employee_id', $record->employee_id) // Ensure to delete the correct employee period
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
