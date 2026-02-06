<?php

namespace App\Filament\Clusters\HRCluster\Resources\EmployeeResource\RelationManagers;

use App\Enums\DayOfWeek;
use App\Models\Attendance;
use App\Models\EmployeePeriod;
use App\Models\EmployeePeriodHistory;
use App\Models\WorkPeriod;
use Carbon\Carbon;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PeriodRelationManager extends RelationManager
{
    protected static string $relationship = 'periods';
    protected static ?string $title       = 'Shifts';
    // protected static ?string $badge = count($this->ownerRecord->periods);


    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        // مثال: عدد الشفتات لهذا الموظف
        return $ownerRecord->periods()->count();
    }

    public static function getBadgeColor(Model $ownerRecord, string $pageClass): ?string
    {
        // تقدر ترجع لون حسب الحالة
        $count = $ownerRecord->periods()->count();

        if ($count === 0) {
            return 'gray';
        }

        if ($count < 3) {
            return 'warning';
        }

        return 'success';
    }

    public static function getBadgeTooltip(Model $ownerRecord, string $pageClass): ?string
    {
        return "Current Shifts Count: " . $ownerRecord->periods()->count();
    }

    public function table(Table $table): Table
    {

        // $explodeHost = explode('.', request()->getHost());

        // $count = count($explodeHost);
        return $table
            ->defaultSort('hr_employee_periods.id', 'desc')
            ->recordTitleAttribute('period_id')
            ->striped()
            ->columns([
                TextColumn::make('id')->label('Id'),

                TextColumn::make('name')->label('Work period'),
                // TextColumn::make('description')->label('description'),
                TextColumn::make('start_at')->label('Start time'),
                TextColumn::make('end_at')->label('End time'),
                TextColumn::make('days')->wrap()
                    ->label('Days')
                    ->getStateUsing(function ($record) {
                        $employee = $this->ownerRecord;

                        $employeePeriod = \App\Models\EmployeePeriod::with('days') // eager load للعلاقة
                            ->where('employee_id', $employee->id)
                            ->where('period_id', $record->id)
                            ->first();

                        if (! $employeePeriod || $employeePeriod->days->isEmpty()) {
                            return '-';
                        }

                        return $employeePeriod->days
                            ->pluck('day_of_week')
                            ->map(fn($d) => DayOfWeek::from($d)->english())
                            ->implode(', ');
                    })
                // ->getStateUsing(fn($state): string =>    implode(',', $state))
                ,
                TextColumn::make('start_date')
                    ->label('Start Date')
                    // ->date()
                    ->getStateUsing(function ($record) {

                        $employeePeriod = EmployeePeriod::find($record->pivot->id);


                        return $employeePeriod?->start_date;
                    }),

                TextColumn::make('end_date')
                    ->label('End Date')
                    ->date()
                    ->getStateUsing(function ($record) {
                        // dd($record);
                        $employeePeriod = \App\Models\EmployeePeriod::find($record->pivot->id);
                        return $employeePeriod?->end_date;
                    }),

                TextColumn::make('creator.name')->label('Created by')->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([
                //
            ])
            ->headerActions([
                Action::make('createOne')->label('Add shifts')
                    ->icon('heroicon-o-plus')
                    ->schema(

                        [Grid::make()->columnSpanFull()->columns(1)->schema([
                            Fieldset::make()->columns(2)->columnSpanFull()
                                ->label('Choose the period duration')
                                ->schema([
                                    DatePicker::make('start_date')->label('Start period date')
                                        ->default(fn() =>  now()->toDateString())
                                        ->minDate(fn() => $this->ownerRecord->join_date ?? now()->toDateString())
                                        ->required(),

                                    DatePicker::make('end_date')->label('End period date')

                                        // ->after('')
                                        ->nullable()
                                        ->helperText('Leave empty for unlimited (open) period'),
                                ]),
                            ToggleButtons::make('periods')
                                ->label('Work Periods')
                                // ->relationship('periods', 'name')
                                ->columns(3)->multiple()
                                // ->disableOptionWhen(function ($value) {
                                //     $employee = $this->ownerRecord;
                                //     return in_array($value, $employee->periods->pluck('id')->toArray()) ?? false;
                                // })

                                ->options(
                                    function () {
                                        $employee = $this->ownerRecord;
                                        // $assigned = $employee->periods->pluck('id')->toArray();
                                        // Only fetch periods NOT assigned to the employee
                                        return WorkPeriod::select('name', 'id')
                                            ->where('branch_id', $employee->branch_id)
                                            // ->whereNotIn('id', $assigned)
                                            ->get()
                                            ->pluck('name', 'id');
                                    }
                                )

                                ->helperText('Select the employee\'s work periods.')->required(),
                            Fieldset::make()->schema([
                                CheckboxList::make('period_days')
                                    ->label('Days of Work')
                                    ->columns(3)
                                    ->options(DayOfWeek::options())
                                    ->required()->columnSpanFull()
                                    ->bulkToggleable()
                                    ->helperText('Select the days this period applies to.'),
                            ]),
                        ])]
                    )
                    ->button()
                    ->databaseTransaction()
                    ->action(function ($data) {
                        try {
                            $service = new \App\Services\HR\EmployeeWorkPeriodService();
                            $service->assignPeriodsToEmployee($this->ownerRecord, $data);

                            // Send notification after the operation is complete
                            Notification::make()->title('Done')->success()->send();
                        } catch (Exception $e) {
                            // Handle the exception
                            if ($e->getCode() == 23000) { // Integrity constraint violation
                                Notification::make()
                                    ->title('Duplicate Shift Assignment')
                                    ->body('❌ This employee already has the same work period starting at this date. Please choose another date or period.')
                                    ->danger()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title($e->getMessage() === 'Overlapping periods are not allowed.' ? 'Overlapping Error' : 'Error')
                                    ->body($e->getMessage())
                                    ->danger() // Use danger for easier visibility of errors like overlap
                                    ->send();
                            }
                            Log::alert('Error adding new periods: ' . $e->getMessage());
                        }
                    }),
            ])
            ->recordActions([
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
                $this->assignDaysAction(),
                Action::make('Delete')
                    ->label(__('lang.delete'))
                    ->requiresConfirmation()
                    ->modalHeading(__('lang.end_period_and_delete'))
                    ->modalDescription(__('lang.end_period_confirmation'))
                    ->form(function ($record) {
                        $period = EmployeePeriod::find($record->pivot_id);
                        return [
                            DatePicker::make('end_date')
                                ->label(__('lang.end_date'))
                                ->default($period?->end_date ?? now())
                                ->required(),
                        ];
                    })
                    ->color('warning')
                    ->button()
                    ->databaseTransaction()
                    ->icon('heroicon-o-x-mark')
                    ->action(function ($record, array $data) {
                        try {
                            DB::transaction(function () use ($record, $data) {
                                $period = EmployeePeriod::find($record->pivot_id);

                                $lastAttendance = $this->ownerRecord->attendances()->latest('id')->first();
                                if ($lastAttendance && $lastAttendance->check_type === Attendance::CHECKTYPE_CHECKIN) {
                                    Notification::make()
                                        ->title('Validation Error')
                                        ->body('The employee has a pending check-out. You cannot add new work periods until the check-out is recorded.')
                                        ->warning()
                                        ->send();
                                    return;
                                }
                                if ($period) {
                                    // حذف كل الأيام المرتبطة بهذه الفترة فقط
                                    $period->days()->delete();

                                    // تحديث الهستوري بنفس نطاق التواريخ بدلاً من الحذف
                                    $periodStart = $period->start_date;
                                    // $periodEnd   = $period->end_date; // We rely on start_date match mostly

                                    // Update history end_date
                                    \App\Models\EmployeePeriodHistory::where('employee_id', $record->employee_id)
                                        ->where('period_id', $record->period_id)
                                        ->where('start_date', $periodStart)
                                        // ->when($periodEnd, fn($q) => $q->where('end_date', $periodEnd), fn($q) => $q->whereNull('end_date'))
                                        ->update(['end_date' => $data['end_date']]);

                                    // Delete the active period record
                                    $period->delete();
                                }

                                Notification::make()->title(__('lang.deleted_successfully'))->success()->send();
                            });
                        } catch (Exception $e) {
                            Notification::make()->title('Error')->icon('heroicon-o-x-circle')
                                ->body($e->getMessage())->danger()->persistent()->send();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
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

    private function assignDaysAction()
    {
        return Action::make('AssignDays')
            ->label('Assign Days')
            ->button()
            ->icon('heroicon-o-calendar-days')
            ->form(function ($record) {
                $employeePeriod = \App\Models\EmployeePeriod::find($record->pivot_id);

                if (! $employeePeriod) {
                    return [];
                }

                // الأيام الموجودة حالياً
                $existingDays = $employeePeriod->days()->pluck('day_of_week')->toArray();

                // جميع الأيام
                $allDays = DayOfWeek::options();

                // فقط الأيام الناقصة
                $missingDays = array_diff_key($allDays, array_flip($existingDays));

                return [
                    CheckboxList::make('existing_days')
                        ->label('Already Assigned Days')
                        ->options(array_intersect_key($allDays, array_flip($existingDays)))
                        ->columns(3)
                        ->default($existingDays)
                        ->disabled(),   // 🔒 معطل لا يمكن تغييره

                    CheckboxList::make('days')
                        ->label('Select Missing Days')
                        ->options($missingDays)
                        ->columns(3)
                        ->helperText('You can only assign days not already assigned to this period.'),

                    DatePicker::make('start_date')
                        ->label('Start Date')
                        ->default($employeePeriod->start_date)
                        ->disabled(),   // 🔒 للعرض فقط

                    DatePicker::make('end_date')
                        ->label('End Date')
                        ->default($employeePeriod->end_date)
                        ->disabled(),   // 🔒 للعرض فقط
                ];
            })
            ->action(function (array $data, $record) {
                $employeePeriod = \App\Models\EmployeePeriod::find($record->pivot_id);

                if (! $employeePeriod) {
                    Notification::make()
                        ->title('Assignment Error')
                        ->body('Work period not assigned to employee yet.')
                        ->danger()
                        ->send();
                    return;
                }

                if (empty($data['days'])) {
                    Notification::make()
                        ->title('No Days Selected')
                        ->body('Please select at least one day to assign.')
                        ->warning()
                        ->send();
                    return;
                }

                try {
                    $service = new \App\Services\HR\EmployeeWorkPeriodService();
                    $service->assignDaysToEmployeePeriod($employeePeriod, $data['days']);

                    Notification::make()
                        ->title('Days Assigned Successfully')
                        ->success()
                        ->send();
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Error')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            })
            ->modalHeading('Assign Work Days')
            ->modalSubmitActionLabel('Save')
            ->modalWidth('md');
    }
}
