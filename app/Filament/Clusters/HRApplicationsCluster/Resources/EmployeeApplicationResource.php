<?php

namespace App\Filament\Clusters\HRApplicationsCluster\Resources;

use App\Filament\Clusters\HRApplicationsCluster;
use App\Filament\Clusters\HRApplicationsCluster\Resources\EmployeeApplicationResource\Pages;
use App\Filament\Pages\AttendanecEmployee2 as AttendanecEmployee;
use App\Models\ApplicationTransaction;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeApplication;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class EmployeeApplicationResource extends Resource
{
    protected static ?string $model = EmployeeApplication::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected ?bool $hasDatabaseTransactions = true;

    protected static ?string $cluster = HRApplicationsCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 1;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make()->label('')->columns(2)->schema([
                    Select::make('employee_id')
                        ->label('Employee')
                        ->searchable()
                        ->required()
                        ->live()
                    // ->afterStateUpdated(function ($get, $set, $state) {
                    //     $employee = Employee::find($state);
                    //     $set('basic_salary', $employee?->salary);
                    // })
                        ->disabled(function () {
                            if (isStuff()) {
                                return true;
                            }
                            return false;
                        })
                        ->default(function () {
                            if (isStuff()) {
                                return auth()->user()->employee->id;
                            }
                        })
                        ->options(Employee::select('name', 'id')

                                ->get()->plucK('name', 'id'))
                    ,

                    DatePicker::make('application_date')
                        ->label('Application date')
                        ->default(date('Y-m-d'))
                        ->required(),

                    ToggleButtons::make('application_type')
                        ->columnSpan(2)
                        ->label('Application type')
                        ->hiddenOn('edit')
                        ->live()
                        ->options(EmployeeApplication::APPLICATION_TYPES)
                        ->icons([
                            EmployeeApplication::APPLICATION_TYPE_ADVANCE_REQUEST => 'heroicon-o-banknotes',
                            EmployeeApplication::APPLICATION_TYPE_LEAVE_REQUEST => 'heroicon-o-clock',
                            EmployeeApplication::APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST => 'heroicon-o-finger-print',
                            EmployeeApplication::APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST => 'heroicon-o-finger-print',
                        ])->inline()
                        ->colors([
                            EmployeeApplication::APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST => 'info',
                            EmployeeApplication::APPLICATION_TYPE_LEAVE_REQUEST => 'warning',
                            EmployeeApplication::APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST => 'success',
                            EmployeeApplication::APPLICATION_TYPE_ADVANCE_REQUEST => 'danger',
                        ])
                    ,
                ]),
                Fieldset::make('')
                    ->label(fn(Get $get): string => EmployeeApplication::APPLICATION_TYPES[$get('application_type')])

                    ->columns(1)
                // ->visible(fn(Get $get): bool => in_array($get('application_type')

                //     , [
                //         EmployeeApplication::APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST,
                //         EmployeeApplication::APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST,
                //     ]))
                    ->visible(fn(Get $get): bool => is_numeric($get('application_type')))

                    ->schema(function ($get, $set) {

                        $form = [];
                        if (in_array($get('application_type'), [
                            EmployeeApplication::APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST,
                            EmployeeApplication::APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST,
                        ])) {
                            $form = [
                                DatePicker::make('detail_date')
                                    ->label('Date')
                                    ->default('Y-m-d'),
                                TimePicker::make('detail_time')
                                    ->label('Time'),
                            ];

                        }
                        if ($get('application_type') == EmployeeApplication::APPLICATION_TYPE_ADVANCE_REQUEST) {
                            $employee = Employee::find($get('employee_id'));
                            $set('basic_salary', $employee?->salary);
                            return [
                                Fieldset::make()->label('')->schema([
                                    Grid::make()->columns(3)->schema([
                                        DatePicker::make('detail_date')
                                            ->label('Date')
                                            ->live()
                                            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                                // Parse the state as a Carbon date, add one month, and set it to the end of the month
                                                $endNextMonth = Carbon::parse($state)->addMonth()->endOfMonth()->format('Y-m-d');
                                                $set('detail_deduction_starts_from', $endNextMonth);
                                            })
                                            ->default('Y-m-d'),
                                        TextInput::make('detail_advance_amount')->numeric()
                                            ->label('Amount'),
                                        TextInput::make('basic_salary')->numeric()->disabled()
                                            ->default(0)
                                            ->label('Basic salary')->helperText('Employee basic salary')
                                        ,

                                    ]),
                                    Grid::make()->columns(3)->schema([
                                        TextInput::make('detail_monthly_deduction_amount')
                                            ->numeric()
                                            ->label('Monthly deduction amount')
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                                $advancedAmount = $get('detail_advance_amount');
                                                if ($advancedAmount > 0) {
                                                    $res = $advancedAmount / $state;

                                                    $set('detail_number_of_months_of_deduction', $res);
                                                    $toMonth = Carbon::now()->addMonths($res)->endOfMonth()->format('Y-m-d');
                                                    $set('detail_deduction_ends_at', $toMonth);
                                                }
                                            })
                                        ,
                                        Fieldset::make()->columnSpan(1)->columns(1)->schema([
                                            DatePicker::make('detail_deduction_starts_from')
                                                ->label('Deduction starts from')
                                                ->default('Y-m-d')
                                                ->live()
                                                ->afterStateUpdated(function ($get, $set, $state) {

                                                    $noOfMonths = (int) $get('detail_number_of_months_of_deduction');

                                                    // $toMonth = Carbon::now()->addMonths($noOfMonths)->endOfMonth()->format('Y-m-d');
                                                    $endNextMonth = Carbon::parse($state)->addMonths($noOfMonths)->endOfMonth()->format('Y-m-d');
                                                    $set('detail_deduction_ends_at', $endNextMonth);
                                                })
                                            ,
                                            DatePicker::make('detail_deduction_ends_at')
                                                ->label('Deduction ends at')
                                                ->default('Y-m-d'),
                                        ]),
                                        TextInput::make('detail_number_of_months_of_deduction')->live(onBlur: true)
                                            ->numeric()
                                            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                                $advancedAmount = $get('detail_advance_amount');

                                                $res = $advancedAmount / $state;
                                                // dd($res,$state);
                                                $set('detail_monthly_deduction_amount', round($res, 2));
                                                $state = (int) $state;
                                                $toMonth = Carbon::now()->addMonths($state)->endOfMonth()->format('Y-m-d');

                                                $set('detail_deduction_ends_at', $toMonth);
                                            })
                                            ->label('Number of months of deduction'),

                                    ]),

                                ]),
                            ];
                        }
                        if ($get('application_type') == EmployeeApplication::APPLICATION_TYPE_LEAVE_REQUEST) {

                            $leaveBalances = LeaveBalance::where('employee_id', $get('employee_id'))->pluck('leave_type_id');
                            $set('from_to_date', date('Y-m-d'));
                            // Get the leave types that are active and have a balance for the employee
                            $leaveTypes = LeaveType::where('active', 1)
                                ->whereIn('id', $leaveBalances)
                                ->whereHas('leaveBalances', function ($query) use ($get) {
                                    $query->where('employee_id', $get('employee_id'))
                                        ->where('balance', '>', 0); // Ensure the balance is greater than 0
                                })
                                ->select('name', 'id')
                                ->get()
                                ->pluck('name', 'id');
                            return [
                                Fieldset::make()->schema(

                                    [
                                        Grid::make()->columns(2)->schema([
                                            Select::make('detail_leave_type_id')->label('Leave type')
                                                ->requiredIf('application_type', EmployeeApplication::APPLICATION_TYPE_LEAVE_REQUEST)
                                                ->live()
                                                ->options(
                                                    $leaveTypes
                                                )
                                                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                                    $leaveBalance = LeaveBalance::getBalanceForEmployee($get('employee_id'), $state);
                                                    $set('detail_balance', $leaveBalance?->balance);
                                                    // $set('detail_days_count.max', $leaveBalance?->balance ?? 0);
                                                }),
                                            TextInput::make('detail_balance')->label('Leave balance')->disabled(),

                                        ]),
                                        Grid::make()->columns(3)->schema([
                                            DatePicker::make('detail_from_date')
                                                ->label('From Date')
                                                ->reactive()
                                                ->default(date('Y-m-d'))
                                                ->required()
                                                ->afterStateUpdated(function ($state, callable $set, $get) {
                                                    $fromDate = $get('detail_from_date');
                                                    $toDate = $get('detail_to_date');

                                                    if ($fromDate && $toDate) {
                                                        $daysDiff = now()->parse($fromDate)->diffInDays(now()->parse($toDate)) + 1;
                                                        $set('detail_days_count', $daysDiff); // Set the detail_days_count automatically
                                                    } else {
                                                        $set('detail_days_count', 0); // Reset if no valid dates are selected
                                                    }
                                                }),

                                            DatePicker::make('detail_to_date')
                                                ->label('To Date')
                                                ->default(\Carbon\Carbon::tomorrow()->addDays(1)->format('Y-m-d'))
                                                ->reactive()
                                                ->required()
                                                ->afterStateUpdated(function ($state, callable $set, $get) {
                                                    $fromDate = $get('detail_from_date');
                                                    $toDate = $get('detail_to_date');

                                                    if ($fromDate && $toDate) {
                                                        $daysDiff = now()->parse($fromDate)->diffInDays(now()->parse($toDate)) + 1;
                                                        $set('detail_days_count', $daysDiff); // Set the detail_days_count automatically
                                                    } else {
                                                        $set('detail_days_count', 0); // Reset if no valid dates are selected
                                                    }
                                                }),

                                            TextInput::make('detail_days_count')
                                            // ->disabled()
                                                ->label('Number of Days')
                                            // ->helperText('Type how many days this leave will be')
                                                ->helperText('Type how many days this leave will be')
                                                ->numeric()
                                            // ->default(2)
                                                ->live()
                                                ->required()
                                                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                                    // Parse the state as a Carbon date, add one month, and set it to the end of the month
                                                    $state = (int) $state;
                                                    $nextDate = Carbon::parse($get('detail_from_date'))->addDays(($state - 1))->format('Y-m-d');
                                                    $set('detail_to_date', $nextDate);
                                                })
                                                ->maxValue(function ($get) {
                                                    $balance = $get('detail_balance') ?? 0;
                                                    return $balance;
                                                })->validationAttribute('ddddd')
                                            ,

                                        ]),
                                    ]

                                ),
                            ];
                        }

                        return [
                            Fieldset::make()->columns(count($form))->schema(
                                $form
                            ),
                        ];
                    })
                ,
                Fieldset::make()->label('')->schema([
                    Textarea::make('notes') // Add the new details field
                        ->label('Notes')
                        ->placeholder('Notes...')
                    // ->rows(5)
                        ->columnSpanFull()
                    ,
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('employee.name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('createdBy.name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('application_date')->label('Application date')
                    ->sortable()
                ,

                TextColumn::make('status')->label('Status')
                    ->badge()
                    ->icon('heroicon-m-check-badge')
                    ->color(fn(string $state): string => match ($state) {
                        EmployeeApplication::STATUS_PENDING => 'warning',
                        EmployeeApplication::STATUS_REJECTED => 'danger',
                        EmployeeApplication::STATUS_APPROVED => 'success',
                    })
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                SelectFilter::make('status')->options([
                    EmployeeApplication::STATUS_PENDING => EmployeeApplication::STATUS_PENDING,
                    EmployeeApplication::STATUS_REJECTED => EmployeeApplication::STATUS_REJECTED,
                    EmployeeApplication::STATUS_APPROVED => EmployeeApplication::STATUS_APPROVED]),
            ])
            ->actions([
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\DeleteAction::make(),

                // static::approveDepartureRequest(),
                // static::rejectDepartureRequest(),

                static::approveDepartureRequest(),
                static::rejectDepartureRequest(),

                static::approveAdvanceRequest(),
                static::rejectAdvanceRequest(),

                static::approveLeaveRequest(),
                static::rejectLeaveRequest(),

                static::approveAttendanceRequest(),
                static::rejectAttendanceRequest(),
                // Approve missed checkin request
                // Action::make('approveMissedCheckinRequest')->label('Approve')->button()
                //     ->databaseTransaction()
                //     ->visible(fn($record): bool => ($record->status == EmployeeApplication::STATUS_PENDING && $record->application_type_id == EmployeeApplication::APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST))
                //     ->color('success')
                //     ->icon('heroicon-o-check')
                //     ->action(function ($record, $data) {

                //         $employeePeriods = $record->employee?->periods;

                //         if (!is_null($record->employee) && count($employeePeriods) > 0) {
                //             $day = \Carbon\Carbon::parse($data['request_check_time'])->format('l');

                //             // Decode the days array for each period
                //             $workTimePeriods = $employeePeriods->map(function ($period) {
                //                 $period->days = json_decode($period->days); // Ensure days are decoded
                //                 return $period;
                //             });

                //             // Filter periods by the day
                //             $periodsForDay = $workTimePeriods->filter(function ($period) use ($day) {
                //                 return in_array($day, $period->days);
                //             });

                //             $closestPeriod = (new AttendanecEmployee())->findClosestPeriod($data['request_check_time'], $periodsForDay);

                //             (new AttendanecEmployee())->createAttendance($record->employee, $closestPeriod, $data['request_check_date'], $data['request_check_time'], 'd', Attendance::CHECKTYPE_CHECKIN);
                //             $record->update([
                //                 'status' => EmployeeApplication::STATUS_APPROVED,
                //                 'approved_by' => auth()->user()->id,
                //                 'approved_at' => now(),
                //             ]);
                //         }
                //         // ApplicationTransaction::createTransactionFromApplication($record);

                //     })
                //     ->disabledForm()
                //     ->form(function ($record) {
                //         // $attendance = Attendance::where('employee_id', $record?->employee_id)
                //         //     ->where('check_date', $record?->detail_date)
                //         //     ->where('check_type', Attendance::CHECKTYPE_CHECKIN)
                //         //     ->first();

                //         return [
                //             // Fieldset::make()->label('Attendance data')->columns(3)->schema([
                //             //     TextInput::make('employee')->default($record?->employee?->name),
                //             //     DatePicker::make('check_date')->default($attendance?->check_date),
                //             //     TimePicker::make('check_time')->default($attendance?->check_time),
                //             //     TextInput::make('period_title')->label('Period')->default($attendance?->period?->name),
                //             //     TextInput::make('start_at')->default($attendance?->period?->start_at),
                //             //     TextInput::make('end_at')->default($attendance?->period?->end_at),
                //             //     Hidden::make('period')->default($attendance?->period),
                //             // ]),
                //             Fieldset::make()->label('Request data')->columns(2)->schema([
                //                 DatePicker::make('request_check_date')->default($record?->detail_date)->label('Date'),
                //                 TimePicker::make('request_check_time')->default($record?->detail_time)->label('Time'),
                //             ]),
                //         ];
                //     }),

                // Action::make('approveAdvanceRequest')->label('Approve')->button()
                //     ->visible(fn($record): bool => ($record->status == EmployeeApplication::STATUS_PENDING && $record->application_type_id == EmployeeApplication::APPLICATION_TYPE_ADVANCE_REQUEST))
                //     ->color('success')
                //     ->icon('heroicon-o-check')
                //     ->action(function ($record, $data) {
                //         //    ApplicationTransaction::createTransactionFromApplication();
                //         $record->update([
                //             'status' => EmployeeApplication::STATUS_APPROVED,
                //             'approved_by' => auth()->user()->id,
                //             'approved_at' => now(),
                //         ]);
                //         ApplicationTransaction::createTransactionFromApplication($record);

                //     })
                //     ->disabledForm()
                //     ->form(function ($record) {
                //         // $details= json_decode($record->details) ;

                //         $detailDate = $record?->detail_date;
                //         $monthlyDeductionAmount = $record?->detail_monthly_deduction_amount;
                //         $advanceAmount = $record?->detail_advance_amount;
                //         $deductionStartsFrom = $record?->detail_deduction_starts_from;
                //         $deductionEndsAt = $record?->detail_deduction_ends_at;
                //         $numberOfMonthsOfDeduction = $record?->detail_number_of_months_of_deduction;

                //         // $details = EmployeeApplicationResource::getDetailsKeysAndValues(json_decode($record->details));
                //         // dd($details);
                //         return [
                //             Fieldset::make()->label('Request data')->columns(3)->schema([
                //                 TextInput::make('employee')->default($record?->employee?->name),
                //                 DatePicker::make('date')->default($detailDate)->label('Advance date'),
                //                 TextInput::make('advance_amount')->default($advanceAmount),
                //                 TextInput::make('deductionStartsFrom')->label('Deducation starts from')->default($deductionStartsFrom),
                //                 TextInput::make('deductionEndsAt')->label('Deducation ends at')->default($deductionEndsAt),
                //                 TextInput::make('numberOfMonthsOfDeduction')->label('numberOfMonthsOfDeduction')->default($numberOfMonthsOfDeduction),
                //                 TextInput::make('monthlyDeductionAmount')->label('monthlyDeductionAmount')->default($monthlyDeductionAmount),

                //             ]),
                //             Fieldset::make()->label('Request data')->columns(2)->schema([
                //                 // DatePicker::make('request_check_date')->default($record?->detail_date)->label('Date'),
                //                 // TimePicker::make('request_check_time')->default($record?->detail_time)->label('Time'),
                //             ]),
                //         ];
                //     }),
                // // Action::make('reject')->label('Reject')->button()
                // //     ->color('warning')
                // //     ->visible(fn($record): bool => $record->status == EmployeeApplication::STATUS_PENDING)
                // //     ->icon('heroicon-o-x-mark')
                // //     ->action(function ($record, $data) {
                // //         $record->update([
                // //             'status' => EmployeeApplication::STATUS_REJECTED,
                // //             'rejected_reason' => $data['rejected_reason'],
                // //             'rejected_by' => auth()->user()->id,
                // //             'rejected_at' => now(),
                // //         ]);

                // //         // Step 2: Create a transaction
                // //         ApplicationTransaction::createTransactionFromApplication($record);

                // //         // Step 3: Calculate the number of leave days and update the leave balance
                // //         $fromDate = $record->detail_from_date;
                // //         $toDate = $record->detail_to_date;
                // //         $daysRequested = $fromDate->diffInDays($toDate) + 1; // Assuming inclusive of the last day

                // //         // Fetch the leave balance for the employee and specific leave type
                // //         $leaveBalance = LeaveBalance::where('employee_id', $record->employee_id)
                // //             ->where('leave_type_id', $record->detail_leave_type_id)
                // //             ->first();

                // //         // Update the balance if found
                // //         if ($leaveBalance) {
                // //             $leaveBalance->decrement('balance', $daysRequested);
                // //         }
                // //     })

                // //     ->form(function ($record) {
                // //         $attendance = Attendance::where('employee_id', $record?->employee_id)
                // //             ->where('check_date', $record?->detail_date)
                // //             ->where('check_type', Attendance::CHECKTYPE_CHECKIN)
                // //             ->first();

                // //         return [
                // //             Fieldset::make()->disabled()->label('Attendance data')->columns(3)->schema([
                // //                 TextInput::make('employee')->default($record?->employee?->name),
                // //                 DatePicker::make('check_date')->default($attendance?->check_date),
                // //                 TimePicker::make('check_time')->default($attendance?->check_time),
                // //                 TextInput::make('period_title')->label('Period')->default($attendance?->period?->name),
                // //                 TextInput::make('start_at')->default($attendance?->period?->start_at),
                // //                 TextInput::make('end_at')->default($attendance?->period?->end_at),
                // //                 Hidden::make('period')->default($attendance?->period),
                // //             ]),
                // //             Fieldset::make()->disabled()->label('Request data')->columns(2)->schema([
                // //                 DatePicker::make('request_check_date')->default($record?->detail_date)->label('Date'),
                // //                 TimePicker::make('request_check_time')->default($record?->detail_time)->label('Time'),
                // //             ]),
                // //             Fieldset::make()->label('Rejected reason')->columns(2)->schema([
                // //                 Textarea::make('rejected_reason')->label('')->columnSpanFull()->required()
                // //                     ->disabled(false)
                // //                     ->helperText('Please descripe reject reason')
                // //                 ,
                // //             ]),

                // //         ];
                // //     })
                // // ,
                // Action::make('approveLeaveRequest')
                //     ->databaseTransaction(true)
                //     ->label('Approve')->button()
                //     ->visible(fn($record): bool => ($record->status == EmployeeApplication::STATUS_PENDING && $record->application_type_id == EmployeeApplication::APPLICATION_TYPE_LEAVE_REQUEST))
                // // ->
                //     ->color('success')
                //     ->icon('heroicon-o-check')
                //     ->action(function ($record, $data) {

                //         $record->update([
                //             'status' => EmployeeApplication::STATUS_APPROVED,
                //             'approved_by' => auth()->user()->id,
                //             'approved_at' => now(),
                //         ]);
                //         $transaction = ApplicationTransaction::createTransactionFromApplicationV2(
                //             $applicationId = $record->id,
                //             $transactionTypeId = 1,
                //             $amount = 0,
                //             $remaining = 0,
                //             $fromDate = $data['from_date'],
                //             $toDate = $data['to_date'],
                //             $createdBy = auth()->user()->id,
                //             $employeeId = $record->employee_id,
                //             $isCanceled = false,
                //             $canceledAt = null,
                //             $cancelReason = null,
                //             $details = json_encode('Approved leave'),
                //             $branchId = $record->branch_id,
                //             $value = $data['days_count'],

                //         );

                //         // Step 3: Calculate the number of leave days and update the leave balance

                //         // Fetch the leave balance for the employee and specific leave type
                //         $leaveBalance = LeaveBalance::where('employee_id', $record->employee_id)
                //             ->where('leave_type_id', $record->detail_leave_type_id)
                //             ->first();

                //         // Update the balance if found
                //         if ($leaveBalance) {
                //             $leaveBalance->decrement('balance', $transaction->value);
                //         }
                //     })
                //     ->disabledForm()
                //     ->form(function ($record) {
                //         $leaveTypeId = $record?->detail_leave_type_id;
                //         $toDate = $record?->detail_to_date;
                //         $fromDate = $record?->detail_from_date;
                //         $daysCount = $record?->detail_days_count;
                //         $leaveType = LeaveType::find($leaveTypeId)->name;

                //         return [
                //             Fieldset::make()->label('Request data')->columns(3)->schema([
                //                 TextInput::make('employee')->default($record?->employee?->name),
                //                 TextInput::make('leave')->default($leaveType),
                //                 DatePicker::make('from_date')->default($fromDate)->label('From date'),
                //                 DatePicker::make('to_date')->default($toDate)->label('To date'),
                //                 TextInput::make('days_count')->default($daysCount),
                //             ]),
                //         ];
                //     })

                // ,
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployeeApplications::route('/'),
            'create' => Pages\CreateEmployeeApplication::route('/create'),
            // 'edit' => Pages\EditEmployeeApplication::route('/{record}/edit'),
        ];
    }

    public static function getDetailsKeysAndValues(array $data)
    {
        // Use array_filter to get the keys starting with 'requr_pattern_'
        $filteredData = array_filter($data, function ($value, $key) {
            return Str::startsWith($key, 'detail_');
        }, ARRAY_FILTER_USE_BOTH);

        return $filteredData;
    }

    public static function getTitleCasePluralModelLabel(): string
    {
        return 'Applications';
    }

    public static function getTitleCaseModelLabel(): string
    {
        return 'Application';
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function canCreate(): bool
    {
        return true;
        return static::can('create');
    }

    public static function canViewAny(): bool
    {
        if (isSystemManager() || isSuperAdmin() || isBranchManager() || isStuff()) {
            return true;
        }
        return false;
    }

    private static function approveDepartureRequest(): Action
    {
        return Action::make('approveDepartureRequest')->label('Approve')->button()
            ->visible(fn($record): bool => ($record->status == EmployeeApplication::STATUS_PENDING && $record->application_type_id == EmployeeApplication::APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST))
            ->color('success')
            ->icon('heroicon-o-check')
            ->action(function ($record, $data) {
                (new AttendanecEmployee())->createAttendance($record->employee, $data['period'], $data['request_check_date'], $data['request_check_time'], 'd', Attendance::CHECKTYPE_CHECKOUT);
                $record->update([
                    'status' => EmployeeApplication::STATUS_APPROVED,
                    'approved_by' => auth()->user()->id,
                    'approved_at' => now(),
                ]);
                ApplicationTransaction::createTransactionFromApplication($record);
            })
            ->disabledForm()
            ->form(function ($record) {
                return [
                    Fieldset::make()->label('Attendance data')->columns(3)->schema([
                        TextInput::make('employee')->default($record?->employee?->name),
                        DatePicker::make('check_date')->default(now()->format('Y-m-d'))->label('Date'),
                        TimePicker::make('check_time')->default(now()->format('H:i'))->label('Time'),
                    ]),
                ];
            });
    }

    private static function rejectDepartureRequest(): Action
    {
        return Action::make('rejectDepartureRequest')->label('Reject')->button()
            ->visible(fn($record): bool => ($record->status == EmployeeApplication::STATUS_PENDING && $record->application_type_id == EmployeeApplication::APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST))
            ->color('danger')
            ->icon('heroicon-o-x-mark')
            ->action(function ($record) {
                $record->update([
                    'status' => EmployeeApplication::STATUS_REJECTED,
                    'rejected_by' => auth()->user()->id,
                    'rejected_at' => now(),
                ]);
            })
            // ->disabledForm()
            ->form(function ($record) {
                return [
                    Textarea::make('rejected_reason')->label('Reason for Rejection')->placeholder('Please provide a reason...')->required(),
                ];
            });
    }
    private static function approveAdvanceRequest(): Action
    {
        return Action::make('approveAdvanceRequest')->label('Approve')->button()
            ->visible(fn($record): bool => ($record->status == EmployeeApplication::STATUS_PENDING && $record->application_type_id == EmployeeApplication::APPLICATION_TYPE_ADVANCE_REQUEST))
            ->color('success')
            ->icon('heroicon-o-check')
            ->action(function ($record) {
                $record->update([
                    'status' => EmployeeApplication::STATUS_APPROVED,
                    'approved_by' => auth()->user()->id,
                    'approved_at' => now(),
                ]);
                ApplicationTransaction::createTransactionFromApplication($record);
            })
            ->disabledForm()
            ->form(function ($record) {
                return [
                    Fieldset::make()->label('Request Data')->columns(2)->schema([
                        TextInput::make('employee')->default($record?->employee?->name)->label('Employee'),
                        TextInput::make('advance_amount')->default($record?->detail_advance_amount)->label('Advance Amount'),
                    ]),
                ];
            });
    }

    private static function rejectAdvanceRequest(): Action
    {
        return Action::make('rejectAdvanceRequest')->label('Reject')->button()
            ->visible(fn($record): bool => ($record->status == EmployeeApplication::STATUS_PENDING && $record->application_type_id == EmployeeApplication::APPLICATION_TYPE_ADVANCE_REQUEST))
            ->color('danger')
            ->icon('heroicon-o-x-mark')
            ->action(function ($record) {
                $record->update([
                    'status' => EmployeeApplication::STATUS_REJECTED,
                    'rejected_by' => auth()->user()->id,
                    'rejected_at' => now(),
                ]);
            })
            ->disabledForm()
            ->form(function ($record) {
                return [
                    Textarea::make('rejected_reason')->label('Reason for Rejection')->placeholder('Please provide a reason...')->required(),
                ];
            });
    }

    private static function approveLeaveRequest(): Action
    {
        return Action::make('approveLeaveRequest')->label('Approve')->button()
            ->visible(fn($record): bool => ($record->status == EmployeeApplication::STATUS_PENDING && $record->application_type_id == EmployeeApplication::APPLICATION_TYPE_LEAVE_REQUEST))
            ->color('success')
            ->icon('heroicon-o-check')
            ->action(function ($record) {
                $record->update([
                    'status' => EmployeeApplication::STATUS_APPROVED,
                    'approved_by' => auth()->user()->id,
                    'approved_at' => now(),
                ]);
                // Add logic for leave balance update if needed
            })
            ->disabledForm()
            ->form(function ($record) {
                return [
                    Fieldset::make()->label('Request Data')->columns(2)->schema([
                        TextInput::make('employee')->default($record?->employee?->name)->label('Employee'),
                        DatePicker::make('from_date')->default($record?->detail_from_date)->label('From Date'),
                        DatePicker::make('to_date')->default($record?->detail_to_date)->label('To Date'),
                    ]),
                ];
            });
    }

    private static function rejectLeaveRequest(): Action
    {
        return Action::make('rejectLeaveRequest')->label('Reject')->button()
            ->visible(fn($record): bool => ($record->status == EmployeeApplication::STATUS_PENDING && $record->application_type_id == EmployeeApplication::APPLICATION_TYPE_LEAVE_REQUEST))
            ->color('danger')
            ->icon('heroicon-o-x-mark')
            ->action(function ($record) {
                $record->update([
                    'status' => EmployeeApplication::STATUS_REJECTED,
                    'rejected_by' => auth()->user()->id,
                    'rejected_at' => now(),
                ]);
            })
            ->disabledForm()
            ->form(function ($record) {
                return [
                    Textarea::make('rejected_reason')->label('Reason for Rejection')->placeholder('Please provide a reason...')->required(),
                ];
            });
    }

    private static function approveAttendanceRequest(): Action
    {
        return Action::make('approveAttendanceRequest')->label('Approve')->button()
            ->visible(fn($record): bool => ($record->status == EmployeeApplication::STATUS_PENDING && $record->application_type_id == EmployeeApplication::APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST))
            ->color('success')
            ->icon('heroicon-o-check')
            ->action(function ($record) {
                // Logic for approving attendance fingerprint requests
                $record->update([
                    'status' => EmployeeApplication::STATUS_APPROVED,
                    'approved_by' => auth()->user()->id,
                    'approved_at' => now(),
                ]);
            })
            ->disabledForm()
            ->form(function ($record) {
                return [
                    Fieldset::make()->label('Request Data')->columns(2)->schema([
                        TextInput::make('employee')->default($record?->employee?->name)->label('Employee'),
                        DatePicker::make('check_date')->default($record?->detail_date)->label('Check Date'),
                    ]),
                ];
            });
    }

    private static function rejectAttendanceRequest(): Action
    {
        return Action::make('rejectAttendanceRequest')->label('Reject')->button()
            ->visible(fn($record): bool => ($record->status == EmployeeApplication::STATUS_PENDING && $record->application_type_id == EmployeeApplication::APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST))
            ->color('danger')
            ->icon('heroicon-o-x-mark')
            ->action(function ($record) {
                $record->update([
                    'status' => EmployeeApplication::STATUS_REJECTED,
                    'rejected_by' => auth()->user()->id,
                    'rejected_at' => now(),
                ]);
            })
            // ->disabledForm()
            ->form(function ($record) {
                return [
                    Textarea::make('rejected_reason')->label('Reason for Rejection')->placeholder('Please provide a reason...')->required(),
                ];
            });
    }

}
