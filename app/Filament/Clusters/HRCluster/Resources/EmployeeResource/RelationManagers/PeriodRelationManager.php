<?php
namespace App\Filament\Clusters\HRCluster\Resources\EmployeeResource\RelationManagers;

use App\Enums\DayOfWeek;
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
    protected static ?string $title       = 'Shifts';
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
                    ->date()
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label('End Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('creator.name')->label('Created by')->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\Action::make('createOne')->label('Add shifts')
                    ->icon('heroicon-o-plus')
                    ->form(

                        [Grid::make()->columnSpanFull()->columns(1)->schema([
                            Fieldset::make()->columns(2)->columnSpanFull()
                                ->label('Choose the period duration')
                                ->schema([
                                    DatePicker::make('start_date')->label('Start period date')

                                        ->default(function ($record) {
                                            return $this->ownerRecord->join_date ?? now()->toDateString();
                                        })
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
                            //     ->default(function () {
                            //     return $this->ownerRecord?->periods?->plucK('id')?->toArray();
                            // })
                            // ->disabled(function(){
                            //     return [1,2,3,4];
                            // })
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
                        DB::beginTransaction();
                        $employee = $this->ownerRecord;
                        // $employeeHistories = $employee->periodHistories->select('period_id', 'start_date', 'end_date') ?? null;
                        // foreach ($employeeHistories as $history) {
                        //     if (isset($history['end_date']) && $history['end_date'] == $data['end_date']) {
                        //         Notification::make()
                        //             ->title('Validation Error')
                        //             ->body('Cannot start period on ended period date')
                        //             ->warning()
                        //             ->send();
                        //         return;
                        //     }
                        // }
                        try {
                            // Retrieve the existing periods associated with the owner record
                            // $existPeriods = array_map('intval', $this->ownerRecord?->periods?->pluck('id')->toArray());
                            $dataPeriods = array_map('intval', $data['periods']);

                            // Find the periods that are not currently associated
                            // $result = array_values(array_diff($dataPeriods, $existPeriods));

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
                            foreach ($dataPeriods as $value) {
                                $workPeriod    = WorkPeriod::find($value);
                                $periodStartAt = $workPeriod?->start_at;
                                $periodEndAt   = $workPeriod?->end_at;

                                // أيام الفترة المراد إدخالها
                                $periodDays = $data['period_days'] ?? [];

                                if ($this->isOverlappingDays_(
                                    $this->ownerRecord->id,
                                    $periodDays,
                                    $periodStartAt,
                                    $periodEndAt,
                                    $data['start_date'],
                                    $data['end_date'] ?? null,
                                )) {
                                    throw new \Exception('Overlapping periods are not allowed.');

                                    // Notification::make()->title('Error')->body('Overlapping periods are not allowed.')->warning()->send();
                                    // return;
                                }

                                $employeePeriod              = new EmployeePeriod();
                                $employeePeriod->employee_id = $this->ownerRecord->id;
                                $employeePeriod->period_id   = $value;
                                $employeePeriod->start_date  = $data['start_date'];
                                $employeePeriod->end_date    = $data['end_date'] ?? null;
                                $employeePeriod->save();

                                foreach ($data['period_days'] as $dayOfWeek) {

                                    $employeePeriod->days()->create([
                                        'day_of_week' => $dayOfWeek,

                                    ]);

                                    EmployeePeriodHistory::create([
                                        'employee_id' => $this->ownerRecord->id,
                                        'period_id'   => $value,
                                        'start_date'  => $data['start_date'],
                                        'end_date'    => $data['end_date'] ?? null,
                                        'start_time'  => $periodStartAt,
                                        'end_time'    => $periodEndAt,
                                        'day_of_week' => $dayOfWeek,
                                    ]);
                                }

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
                $this->assignDaysAction(),
                Action::make('Delete')->requiresConfirmation()
                    ->color('warning')
                    ->button()

                    ->databaseTransaction()
                    ->icon('heroicon-o-x-mark')
                    ->action(function ($record) {

                        try {

                            // Update the end_date in hr_employee_period_histories
                            // Validate the employee's last attendance
                            // $lastAttendance = $this->ownerRecord->attendances()->latest('id')->first();
                            // if ($lastAttendance && $lastAttendance->check_type === Attendance::CHECKTYPE_CHECKIN) {
                            //     Notification::make()
                            //         ->title('Validation Error')
                            //         ->body('The employee has a pending check-out. You cannot add new work periods until the check-out is recorded.')
                            //         ->warning()
                            //         ->send();
                            //     return;
                            // }
                            DB::transaction(function () use ($record) {
                                $period = EmployeePeriod::find($record->pivot_id);

                                if ($period) {
                                    // حذف كل الأيام المرتبطة بهذه الفترة فقط
                                    $period->days()->delete();
                                    // حذف الهستوري بنفس نطاق التواريخ
                                    $periodStart = $period->start_date;
                                    $periodEnd   = $period->end_date;
                                    \App\Models\EmployeePeriodHistory::where('employee_id', $record->employee_id)
                                        ->where('period_id', $record->period_id)
                                        ->where('start_date', $periodStart)
                                        ->when($periodEnd, fn($q) => $q->where('end_date', $periodEnd), fn($q) => $q->whereNull('end_date'))
                                        ->delete();
                                }

                                $period->delete();
                            });
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

    private function assignDaysAction()
    {
        return Action::make('AssignDays')
            ->label('Assign Days')->button()
            ->icon('heroicon-o-calendar-days')
            ->form(fn($record) => [
                CheckboxList::make('days')
                    ->label('Select Days')
                    ->options(DayOfWeek::options())
                    ->columns(3)
                    ->required()
                    ->default(function () use ($record) {
                        $employee = $this->ownerRecord;

                        return \App\Models\EmployeePeriodDay::where('employee_period_id', $record->pivot_id)
                            ->pluck('day_of_week')
                            ->toArray();
                    })
                ,
                DatePicker::make('start_date')
                    ->label('Start Date')
                    ->required()->readOnly()
                    ->default(function () use ($record) {
                        // تاريخ بداية الفترة للموظف أو اليوم الحالي
                        $employee = $this->ownerRecord;
                        $period   = \App\Models\EmployeePeriod::where('employee_id', $employee->id)
                            ->where('period_id', $record->id)
                            ->first();
                        return $period?->start_date ?? $employee->join_date ?? now()->toDateString();
                    }),
                DatePicker::make('end_date')
                    ->label('End Date')
                    ->after('start_date')
                    ->nullable()
                    ->default(function () use ($record) {
                        $employee = $this->ownerRecord;
                        $period   = \App\Models\EmployeePeriod::where('employee_id', $employee->id)
                            ->where('period_id', $record->id)
                            ->first();
                        return $period?->end_date;
                    })
                ,
            ])
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

                try {
                    DB::transaction(function () use ($data, $employeePeriod, $record) {
                        $existingDays = $employeePeriod->days()->pluck('day_of_week')->toArray();
                        $selectedDays = $data['days'];

                        // الأيام الجديدة التي أضيفت
                        $daysToAdd = array_diff($selectedDays, $existingDays);
                        // الأيام التي أزيلت
                        $daysToRemove = array_diff($existingDays, $selectedDays);

                        // إضافة الأيام الجديدة
                        foreach ($daysToAdd as $day) {
                            $employeePeriod->days()->create([
                                'day_of_week' => $day,
                            ]);
                            EmployeePeriodHistory::create([
                                'employee_id' => $employeePeriod->employee_id,
                                'period_id'   => $employeePeriod->period_id,
                                'start_date'  => $data['start_date'],
                                'end_date'    => $data['end_date'] ?? null,
                                'start_time'  => $employeePeriod->workPeriod->start_at,
                                'end_time'    => $employeePeriod->workPeriod->end_at,
                                'day_of_week' => $day,
                            ]);
                        }

                        // إنهاء الأيام المحذوفة (تحديث end_date فقط وليس حذف!)
                        foreach ($daysToRemove as $day) {
                            $periodStart = $data['start_date'];       // بداية الفترة المحذوفة
                            $periodEnd   = $data['end_date'] ?? null; // نهاية الفترة المحذوفة (قد تكون null = مفتوحة)
                            // تحديث end_date في جدول الأيام وفي الهستوري
                            $employeePeriod->days()->where('day_of_week', $day)->delete();
                            EmployeePeriodHistory::where('employee_id', $employeePeriod->employee_id)
                                ->where('period_id', $employeePeriod->period_id)
                                ->where('day_of_week', $day)
                                ->where(function ($q) use ($periodStart, $periodEnd) {
                                    $q->where(function ($query) use ($periodEnd) {
                                        if ($periodEnd) {
                                            $query->whereNull('end_date')
                                                ->orWhere('end_date', '>=', $periodEnd);
                                        } else {
                                            $query->whereNull('end_date');
                                        }
                                    })
                                        ->where('start_date', '<=', $periodStart);
                                })
                                ->delete();
                        }

                        Notification::make()
                            ->title('Days Assigned Successfully')
                            ->success()
                            ->send();
                    });
                } catch (\Exception $e) {
                    DB::rollBack();
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
    private function isOverlappingDays($employeeId, $periodDays, $periodStartAt, $periodEndAt, $excludePeriodId = null)
    {
        // Query all employee periods that overlap in time, excluding current one (for edit)
        $query = EmployeePeriod::query()
            ->with('days')
            ->where('employee_id', $employeeId)
            ->whereHas('workPeriod', function ($query) use ($periodStartAt, $periodEndAt) {
                $query->where(function ($q) use ($periodStartAt, $periodEndAt) {
                    $q->whereBetween('start_at', [$periodStartAt, $periodEndAt])
                        ->orWhereBetween('end_at', [$periodStartAt, $periodEndAt])
                        ->orWhere(function ($q) use ($periodStartAt, $periodEndAt) {
                            $q->where('start_at', '<=', $periodStartAt)
                                ->where('end_at', '>=', $periodEndAt);
                        });
                });
            });

        // Exclude the current period if editing
        if ($excludePeriodId) {
            $query->where('id', '!=', $excludePeriodId);
        }

        $overlappingPeriods = $query->get();

        foreach ($overlappingPeriods as $period) {
            $existingDays = $period->days->pluck('day_of_week')->toArray();
            if (count(array_intersect($periodDays, $existingDays)) > 0) {
                return true;
            }
        }

        return false;
    }

    private function isOverlappingDays_($employeeId, $periodDays, $periodStartAt, $periodEndAt, $periodStartDate, $periodEndDate = null, $excludePeriodId = null)
    {
        $query = EmployeePeriod::query()
            ->with(['days' => function ($q) use ($periodDays) {
                $q->whereIn('day_of_week', $periodDays);
            }])
            ->where('employee_id', $employeeId)
            ->where(function ($q) use ($periodStartDate, $periodEndDate) {
                $q->where(function ($q2) use ($periodStartDate, $periodEndDate) {
                    // شرط تقاطع الفترات
                    $q2->whereNull('end_date')->orWhere(function ($q3) use ($periodStartDate, $periodEndDate) {
                        if ($periodEndDate) {
                            $q3->where('start_date', '<=', $periodEndDate)
                                ->where(function ($q4) use ($periodStartDate) {
                                    $q4->whereNull('end_date')->orWhere('end_date', '>=', $periodStartDate);
                                });
                        } else {
                            // الفترة الجديدة مفتوحة
                            $q3->where('end_date', '>=', $periodStartDate)->orWhereNull('end_date');
                        }
                    });
                });
            })
            ->whereHas('workPeriod', function ($query) use ($periodStartAt, $periodEndAt) {
                $query->where(function ($q) use ($periodStartAt, $periodEndAt) {
                    $q->whereBetween('start_at', [$periodStartAt, $periodEndAt])
                        ->orWhereBetween('end_at', [$periodStartAt, $periodEndAt])
                        ->orWhere(function ($q) use ($periodStartAt, $periodEndAt) {
                            $q->where('start_at', '<=', $periodStartAt)
                                ->where('end_at', '>=', $periodEndAt);
                        });
                });
            });

        if ($excludePeriodId) {
            $query->where('id', '!=', $excludePeriodId);
        }

        $overlappingPeriods = $query->get();

        foreach ($overlappingPeriods as $period) {
            foreach ($period->days as $day) {
                return true;
            }
        }
        return false;
    }

}