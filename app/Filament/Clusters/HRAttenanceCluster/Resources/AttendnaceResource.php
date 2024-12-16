<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources;

use App\Filament\Clusters\HRAttenanceCluster;
use App\Filament\Clusters\HRAttenanceCluster\Resources\AttendnaceResource\Pages;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Setting;
use Carbon\Carbon;
use DateTime;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Average;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;

class AttendnaceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = HRAttenanceCluster::class;
    protected static ?string $modelLabel = 'Attendance Log';
    protected static ?string $pluralLabel = 'Attendance Log';

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 2;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make()->label('Select date & time')->schema([
                    Grid::make()->columns(3)->schema([

                        Forms\Components\DatePicker::make('check_date')
                            ->label('Check date')
                            ->required()
                            ->default(date('Y-m-d'))
                            ->live()
                            ->afterStateUpdated(function (?string $state, $component, $set) {
                                $set('day', Carbon::parse($state)->format('l'));
                            }),

                        Forms\Components\TimePicker::make('check_time')
                            ->label('Check time')
                            ->default(now())
                            ->required(),
                        TextInput::make('day')->label('Day')->disabled()->default(Carbon::parse(date('Y-m-d'))->format('l')),
                    ]),
                ]),

                Fieldset::make()->label('Select employee and check type')->schema([
                    Forms\Components\Select::make('employee_id')
                        ->label('Employee')
                        ->live()
                        ->searchable()
                        // ->default(auth()->user()?->employee?->id)
                        // ->disabled()
                        ->relationship('employee', 'name')
                        ->afterStateUpdated(function (Get $get, Set $set) {
                            $employee_id = $get('employee_id');
                            $check_date = $get('check_date');
                            $check_time = $get('check_time');
                            // $employee_periods = Employee::find($employee_id)?->periods->select('id')->pluck('id')->toArray();
                            // dd( $employee_periods);
                            $employee_attendance = Attendance::where('employee_id', $employee_id)->where('check_date', $check_date)->select('check_type', 'check_time', 'check_date')->get()->toArray();
                            if (count($employee_attendance) == 0) {
                                $set('check_type', Attendance::CHECKTYPE_CHECKIN);
                            } else if (count($employee_attendance) == 1) {
                                $set('check_type', Attendance::CHECKTYPE_CHECKOUT);
                            } else if (count($employee_attendance) == 2) {
                                $set('check_type', Attendance::CHECKTYPE_CHECKIN);
                            } else if (count($employee_attendance) == 3) {
                                $set('check_type', Attendance::CHECKTYPE_CHECKOUT);
                            } else if (count($employee_attendance) == 4) {
                                $set('check_type', Attendance::CHECKTYPE_CHECKIN);
                            } else if (count($employee_attendance) == 5) {
                                $set('check_type', Attendance::CHECKTYPE_CHECKOUT);
                            }
                        })
                        ->required(),
                    Forms\Components\ToggleButtons::make('check_type')
                        ->label('Check type')
                        ->inline()
                        // ->default(function(Get $get,Set $set){
                        //     $employee_id = $get('employee_id');
                        //     $set('notes',$employee_id);
                        //     // dd()
                        // })
                        ->options(Attendance::getCheckTypes())
                        ->required(),

                ]),

                Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->columnSpanFull()
                    ->nullable(),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->paginated([10, 25, 50, 100])
            ->defaultSort('id', 'desc')
            ->striped()
            ->columns([
                Tables\Columns\TextColumn::make('employee.name')
                    ->label('Employee')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('check_type')
                    ->label('Type')
                    ->sortable(),
                Tables\Columns\TextColumn::make('period.name')
                    ->label('Period')
                    ->tooltip(function ($record) {
                        return $record->period->start_at . ' - ' . $record->period->end_at;
                    }),

                Tables\Columns\TextColumn::make('check_date')
                    ->label('Check Date')
                    ->sortable(),

                Tables\Columns\TextColumn::make('check_time')
                    ->label('Check Time'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status'),
                Tables\Columns\TextColumn::make('delay_minutes')
                    ->formatStateUsing(function ($record) {
                        if ($record->delay_minutes <= Setting::getSetting('early_attendance_minutes')) {
                            return 0;
                        } else {
                            return $record->delay_minutes;
                        }
                    })
                    ->label('Delay Minuts')->sortable()->summarize(Sum::make()->query(fn(\Illuminate\Database\Query\Builder $query) => $query->where('delay_minutes', '>', 10)))
                    // ->summarize(fn($record): integer => 11)
                    ->toggleable(isToggledHiddenByDefault: true)->alignCenter(true),
                Tables\Columns\TextColumn::make('day')
                    ->label('Day'),
                Tables\Columns\TextColumn::make('early_departure_minutes')
                    ->label('Early departure minutes')->alignCenter(true)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->summarize(Sum::make()->query(fn(\Illuminate\Database\Query\Builder $query) => $query->where('early_departure_minutes', '>', 20))),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                SelectFilter::make('employee_id')->searchable()->label('Employee')->options(function (Get $get) {
                    return Employee::query()
                        ->pluck('name', 'id');
                }),
                SelectFilter::make('branch_id')->searchable()->label('Branch')
                    ->options(function (Get $get) {
                        return Branch::query()
                            ->pluck('name', 'id');
                    }),

                Filter::make('month')
                    ->label('Filter by Month')
                    ->form([

                        Forms\Components\Select::make('year')
                            ->label('Year')
                            ->options(function () {
                                $years = range(Carbon::now()->year, Carbon::now()->year - 1); // Last 10 years
                                return array_combine($years, $years);
                            })
                            ->placeholder('Select a year'),
                        Forms\Components\Select::make('month')
                            ->label('Month')
                            ->options([
                                '01' => 'January',
                                '02' => 'February',
                                '03' => 'March',
                                '04' => 'April',
                                '05' => 'May',
                                '06' => 'June',
                                '07' => 'July',
                                '08' => 'August',
                                '09' => 'September',
                                '10' => 'October',
                                '11' => 'November',
                                '12' => 'December',
                            ])
                            ->placeholder('Select a month'),



                        Forms\Components\DatePicker::make('check_date')
                            ->label('Date')
                            ->placeholder('Choose date'),



                    ])->query(function (Builder $query, array $data) {
                        if ($data['month'] && $data['year']) {
                            $startDate = Carbon::createFromDate($data['year'], $data['month'], 1)->startOfMonth();
                            $endDate = $startDate->copy()->endOfMonth();

                            $query->whereBetween('check_date', [$startDate, $endDate]);
                            if ($data['check_date']) {
                                $query->where('check_date', $data['check_date']);
                            }
                        }
                    })

                    ->indicateUsing(function (array $data): ?string {
                        if ($data['month'] && $data['year']) {
                            return 'Month: ' . Carbon::createFromDate($data['year'], $data['month'], 1)->format('F Y');
                        }
                        return null;
                    }),
                SelectFilter::make('check_type')
                    ->label('Type')
                    ->options([
                        Attendance::CHECKTYPE_CHECKIN => Attendance::CHECKTYPE_CHECKIN,
                        Attendance::CHECKTYPE_CHECKOUT => Attendance::CHECKTYPE_CHECKOUT,
                    ]),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(Attendance::getStatuses()),
            ], FiltersLayout::AboveContent)
            ->actions([

                Tables\Actions\Action::make('fixCheckout')->visible(fn($record): bool => (isSuperAdmin() && $record->check_type == Attendance::CHECKTYPE_CHECKOUT))
                    ->button()->form(function ($record) {
                        $checkInData = $record->checkinRecord;

                        $checkInTime = $checkInData?->check_time;
                        $checkInDate = $checkInData?->check_date;

                        $checkOutTime = $record?->check_time;
                        $checkOutDate = $record?->check_date;
                        $periodStartAt = $record?->period?->start_at;
                        $periodEndAt = $record?->period?->end_at;
                        $checkINTimeDate = Carbon::parse($checkInDate . ' ' . $checkInTime);
                        $checkOutTimeDate = Carbon::parse($checkOutDate . ' ' . $checkOutTime);
                        $periodEndAtTimeDate = Carbon::parse($checkOutDate . ' ' . $periodEndAt)->addDay();


                        $earlyMinutsDepature = round($checkOutTimeDate->diffInMinutes($periodEndAtTimeDate), 2);
                        return [
                            Grid::make()->disabled()->label('Checkin')->columns(2)->schema([
                                TextInput::make('check_in_time')->default($checkInTime),
                                TextInput::make('check_in_date')->default($checkInDate),
                            ]),

                            Grid::make()->disabled()->label('Checkout')->columns(2)->schema([
                                TextInput::make('check_time')->default($checkOutTime),
                                TextInput::make('check_date')->default($checkOutDate),
                            ]),
                            Grid::make()->disabled()->label('Period')->columns(2)->schema([
                                TextInput::make('period_start_at')->default($periodStartAt),
                                TextInput::make('period_end_at')->default($periodEndAt),
                            ]),
                            TextInput::make('early_minuts_departure')->default($earlyMinutsDepature)->columnSpanFull()->disabled(false),
                            Select::make('status_2')->label('Status')->options(Attendance::getStatuses())
                                ->default(Attendance::STATUS_EARLY_DEPARTURE),
                        ];
                    })->modalCancelAction(false)
                    ->action(function ($record, $data) {
                        DB::beginTransaction();

                        try {
                            //code...
                            $record->update([
                                'status' => $data['status_2'],
                                'early_departure_minutes' => $data['early_minuts_departure']
                            ]);
                            DB::commit();
                            showSuccessNotifiMessage('Done');
                        } catch (\Exception $th) {
                            DB::rollBack();
                            showWarningNotifiMessage($th->getMessage());
                            throw $th;
                        }
                    }),
                Tables\Actions\Action::make('fixCheckin')
                    ->visible(fn($record): bool => (isSuperAdmin() && $record->check_type == Attendance::CHECKTYPE_CHECKIN))
                    ->button()
                    ->form(function ($record) {

                        $checkInTime = $record?->check_time;
                        
                        $checkInDate = $record?->check_date;


                        $periodStartAt = $record?->period?->start_at;
                        $periodEndAt = $record?->period?->end_at;
                        $checkINTimeDate = Carbon::parse($checkInDate . ' ' . $checkInTime);
                        $periodStartAtTimeDate = Carbon::parse($checkInDate . ' ' . $checkInTime);

                        $delayMinutes = round($checkINTimeDate->diffInMinutes($periodStartAtTimeDate), 2);
                        return [
                            

                            Grid::make()->label('Checkout')->columns(4)->schema([
                                TextInput::make('check_time')->default($checkInTime),
                                TextInput::make('delay_minutes')->default($record->delay_minutes),
                                TextInput::make('check_in_date_')->default($checkInDate),
                                Select::make('status_2')->label('Status')->options(Attendance::getStatuses())
                                ->default($record->status),
                            ]),
                            Grid::make()->disabled()->label('Period')->columns(2)->schema([
                                TextInput::make('period_start_at')->default($periodStartAt),
                                TextInput::make('period_end_at')->default($periodEndAt),
                            ]),

                            
                        ];
                    })->modalCancelAction(false)
                    ->action(function ($record, $data) {
                        DB::beginTransaction();

                        try {
                            $record->update([
                                'status' => $data['status_2'],
                                'delay_minutes' => $data['delay_minutes'],
                                'check_date' => $data['check_in_date_'],
                                'check_time' => $data['check_time'],
                            ]);
                            DB::commit();
                            showSuccessNotifiMessage('Done');
                        } catch (\Exception $th) {
                            DB::rollBack();
                            showWarningNotifiMessage($th->getMessage());
                            throw $th;
                        }
                    }),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
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
            'index' => Pages\ListAttendnaces::route('/'),
            'create' => Pages\CreateAttendnace::route('/create'),
            // 'edit' => Pages\EditAttendnace::route('/{record}/edit'),
            'view' => Pages\ViewAttendnace::route('/{record}'),
            // 'employee-attendance' => Pages\EmployeeAttendance::route('/employee-attendance'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        // return static::getModel()::where('employee_id',auth()->user()?->employee?->id)->count();
        return static::getModel()::count();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function canDelete(Model $record): bool
    {
        if (isSuperAdmin()) {
            return true;
        }
        return false;
    }

    public static function canDeleteAny(): bool
    {
        if (isSuperAdmin()) {
            return true;
        }
        return false;
    }

    public static function canForceDelete(Model $record): bool
    {
        if (isSuperAdmin()) {
            return true;
        }
        return false;
    }

    public static function canForceDeleteAny(): bool
    {
        if (isSuperAdmin()) {
            return true;
        }
        return false;
    }

    public static function canViewAny(): bool
    {
        if (isSystemManager() || isSuperAdmin()) {
            return true;
        }
        return false;
    }

    public static function canCreate(): bool
    {
        return false;
        return static::can('create');
    }
}
