<?php

namespace App\Filament\Clusters\HRApplicationsCluster\Resources;

use App\Filament\Clusters\HRApplicationsCluster;
use App\Filament\Clusters\HRApplicationsCluster\Resources\EmployeeApplicationResource\Pages;
use App\Filament\Pages\AttendanecEmployee;
use App\Models\ApplicationTransaction;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeApplication;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
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
                        ->default('Y-m-d')
                        ->required(),
                    // Select::make('application_type')
                    //     ->label('Application type')
                    //     ->hiddenOn('edit')
                    //     ->searchable()
                    //     ->live()
                    //     ->options(EmployeeApplication::APPLICATION_TYPES)
                    ToggleButtons::make('application_type')
                        ->columnSpan(2)
                        ->label('Application type')
                        ->hiddenOn('edit')
                    // ->searchable()
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
                                    ->label('date')
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
                                            ->label('date')
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
                                                ->label('Deduction ends at')->disabled()
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
                                    Textarea::make('detail_advanced_purpose')->columnSpanFull()->label('Advance purpose')->required(),
                                ]),
                            ];
                        }
                        if ($get('application_type') == EmployeeApplication::APPLICATION_TYPE_LEAVE_REQUEST) {
                            $form = [
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
                                            $set('days_count', $daysDiff); // Set the days_count automatically
                                        } else {
                                            $set('days_count', 0); // Reset if no valid dates are selected
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
                                            $set('days_count', $daysDiff); // Set the days_count automatically
                                        } else {
                                            $set('days_count', 0); // Reset if no valid dates are selected
                                        }
                                    }),

                                TextInput::make('days_count')->disabled()
                                    ->label('Number of Days')
                                    ->helperText('Type how many days this leave will be')
                                    ->numeric()
                                    ->default(2)
                                    ->required(),

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
                        ->placeholder('Enter application notes...')
                    // ->rows(5)
                        ->columnSpanFull()
                    ,
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
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
                TextColumn::make('detail_date')->label('Date'),
                TextColumn::make('detail_time')->label('Time')
                // ->toggledHiddenByDefault(false)
                // ->toggleable(function(){
                //     return false;
                //     // return (isToggledHiddenByDefault: true);
                // })
                // ->label(function($record){
                //     dd(static::$recordTitleAttribute);
                // })
                    ->state(function ($record) {
                        // dd($record);
                    })
                // ->getStateUsing(function ($record) {
                //     // dd($record);
                //     return $record;
                //     // return $record->productDescriptions()->first()?->name;
                // })
                    ->hidden(fn($record): bool => ($record?->application_type_id == EmployeeApplication::APPLICATION_TYPE_ADVANCE_REQUEST))
                    ->visible(function () {
                        // dd($record);
                        // if($record ){
                        //     dd($record);

                        // }
                        return true;
                        // if (in_array($record->application_type_id, [EmployeeApplication::APPLICATION_TYPE_ATTENDANCE_FINGERPRINT_REQUEST
                        //     , EmployeeApplication::APPLICATION_TYPE_DEPARTURE_FINGERPRINT_REQUEST,
                        // ])) {
                        //     return true;
                        // }
                        // return false;
                    })
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
                // TextColumn::make('rejectedBy.name')->label('Rejected by'),
                // TextColumn::make('rejected_at')->label('Rejected at'),
                // TextColumn::make('rejected_reason')->label('Rejected reason'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('test')->action(function () {
                    $recipient = auth()->user();

                    Notification::make()
                        ->title('Saved successfully')
                        ->sendToDatabase($recipient, isEventDispatched: true)
                        ->broadcast($recipient)
                        ->send()
                    ;

                }),
                // Tables\Actions\EditAction::make(),
                Action::make('approveDepatureRequest')->label('Approve')->button()
                    ->visible(fn($record): bool => $record->status == EmployeeApplication::STATUS_PENDING)
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->action(function ($record, $data) {
                        // dd($record,$data);

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
                        $attendance = Attendance::where('employee_id', $record?->employee_id)
                            ->where('check_date', $record?->detail_date)
                            ->where('check_type', Attendance::CHECKTYPE_CHECKIN)
                            ->first();

                        return [
                            Fieldset::make()->label('Attendance data')->columns(3)->schema([
                                TextInput::make('employee')->default($record?->employee?->name),
                                DatePicker::make('check_date')->default($attendance?->check_date),
                                TimePicker::make('check_time')->default($attendance?->check_time),
                                TextInput::make('period_title')->label('Period')->default($attendance?->period?->name),
                                TextInput::make('start_at')->default($attendance?->period?->start_at),
                                TextInput::make('end_at')->default($attendance?->period?->end_at),
                                Hidden::make('period')->default($attendance?->period),
                            ]),
                            Fieldset::make()->label('Request data')->columns(2)->schema([
                                DatePicker::make('request_check_date')->default($record?->detail_date)->label('Date'),
                                TimePicker::make('request_check_time')->default($record?->detail_time)->label('Time'),
                            ]),
                        ];
                    }),
                Action::make('reject')->label('Reject')->button()
                    ->color('warning')
                    ->visible(fn($record): bool => $record->status == EmployeeApplication::STATUS_PENDING)
                    ->icon('heroicon-o-x-mark')
                    ->action(function ($record, $data) {
                        $record->update([
                            'status' => EmployeeApplication::STATUS_REJECTED,
                            'rejected_reason' => $data['rejected_reason'],
                            'rejected_by' => auth()->user()->id,
                            'rejected_at' => now(),
                        ]);
                    })

                // ->requiresConfirmation()
                // ->disabledForm()
                    ->form(function ($record) {
                        $attendance = Attendance::where('employee_id', $record?->employee_id)
                            ->where('check_date', $record?->detail_date)
                            ->where('check_type', Attendance::CHECKTYPE_CHECKIN)
                            ->first();

                        return [
                            Fieldset::make()->disabled()->label('Attendance data')->columns(3)->schema([
                                TextInput::make('employee')->default($record?->employee?->name),
                                DatePicker::make('check_date')->default($attendance?->check_date),
                                TimePicker::make('check_time')->default($attendance?->check_time),
                                TextInput::make('period_title')->label('Period')->default($attendance?->period?->name),
                                TextInput::make('start_at')->default($attendance?->period?->start_at),
                                TextInput::make('end_at')->default($attendance?->period?->end_at),
                                Hidden::make('period')->default($attendance?->period),
                            ]),
                            Fieldset::make()->disabled()->label('Request data')->columns(2)->schema([
                                DatePicker::make('request_check_date')->default($record?->detail_date)->label('Date'),
                                TimePicker::make('request_check_time')->default($record?->detail_time)->label('Time'),
                            ]),
                            Fieldset::make()->label('Rejected reason')->columns(2)->schema([
                                Textarea::make('rejected_reason')->label('')->columnSpanFull()->required()
                                    ->disabled(false)
                                    ->helperText('Please descripe reject reason')
                                ,
                            ]),

                        ];
                    })

                ,
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
}
