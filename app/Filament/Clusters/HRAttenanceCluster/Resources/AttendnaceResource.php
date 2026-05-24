<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources;

use App\Filament\Clusters\HRAttenanceCluster;
use App\Filament\Clusters\HRAttenanceCluster\Resources\AttendnaceResource\Pages;
use App\Filament\Clusters\HRAttenanceCluster\Resources\AttendnaceResource\Pages\CreateAttendnace;
use App\Filament\Clusters\HRAttenanceCluster\Resources\AttendnaceResource\Pages\ListAttendnaces;
use App\Filament\Clusters\HRAttenanceCluster\Resources\AttendnaceResource\Pages\ViewAttendnace;
use App\Filament\Tables\Columns\SoftDeleteColumn;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Setting;
use Carbon\Carbon;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\ToggleButtons;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AttendnaceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::Identification;

    protected static ?string $cluster = HRAttenanceCluster::class;

    public static function getModelLabel(): string
    {
        return __('lang.attendance_log');
    }

    public static function getPluralLabel(): string
    {
        return __('lang.attendance_log');
    }

    public static function getNavigationLabel(): string
    {
        return __('lang.attendance_log');
    }

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Fieldset::make()->columnSpanFull()->label(__('lang.select_date_time'))->schema([
                    Grid::make()->columnSpanFull()->columns(3)->schema([

                        DatePicker::make('check_date')
                            ->label(__('lang.check_date'))
                            ->required()
                            ->default(date('Y-m-d'))
                            ->live()
                            ->afterStateUpdated(function (?string $state, $component, $set) {
                                $set('day', Carbon::parse($state)->format('l'));
                            }),

                        TimePicker::make('check_time')
                            ->label(__('lang.check_time'))
                            ->default(now())
                            ->required(),
                        TextInput::make('day')->label(__('lang.day'))->disabled()->default(Carbon::parse(date('Y-m-d'))->format('l')),
                    ]),
                ]),

                Fieldset::make()->columnSpanFull()
                    ->label(__('lang.select_employee_check_type'))
                    ->columns(4)
                    ->schema([
                        Select::make('employee_id')
                            ->label(__('lang.employee'))
                            ->live()
                            ->searchable()

                            ->relationship('employee', 'name')

                            ->required(),

                        Select::make('period_id')
                            ->label(__('lang.shift'))
                            ->live()
                            ->searchable()

                            ->relationship('period', 'name')

                            ->required(),

                        ToggleButtons::make('check_type')
                            ->label('Check type')
                            ->inline()
                            ->options(Attendance::getCheckTypes())
                            ->required(),

                        TextInput::make('source')->label(__('lang.attendance_type')),

                    ]),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->paginated([10, 25, 50, 100])
            ->defaultSort('id', 'desc')
            ->striped()
            ->columns([
                SoftDeleteColumn::make(),
                // TextColumn::make('accepted')
                //     ->label('')
                //     ->formatStateUsing(fn($state) => $state ? 'Accepted' : 'Not Accepted')
                //     ->badge()
                //     ->colors([
                //         'danger' => fn($state) => blank($state),
                //         'success' => fn($state) => filled($state),
                //     ]),
                IconColumn::make('accepted')
                    ->label('Appr')
                    ->alignCenter(true)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->boolean(),

                TextColumn::make('id')
                    ->label('#')
                    ->sortable()->toggleable(isToggledHiddenByDefault: true)
                    ->alignCenter()
                    ->searchable(),
                TextColumn::make('employee.name')
                    ->label('Employee')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('check_type')
                    ->label('Type')
                    ->sortable(),
                TextColumn::make('checkinRecord.id')
                    ->label('CheckIn.ID')->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()->alignCenter(),
                TextColumn::make('period.name')
                    ->label('Period')
                    ->tooltip(function ($record) {
                        $period = $record->period;

                        return '('.$period->start_at.' - '.$period->end_at.') _ ('.$period->id.' - '.$period->name.')';
                    }),

                TextColumn::make('check_date')
                    ->label('Check Date')
                    ->sortable(),
                TextColumn::make('real_check_date')
                    ->label('Real Check Date')
                    ->sortable()->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('check_time')
                    ->label('Check Time'),
                TextColumn::make('status')
                    ->label('Status'),
                TextColumn::make('branch.name')
                    ->label('Branch')
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('delay_minutes')
                    ->formatStateUsing(function ($record) {
                        if ($record->delay_minutes <= Setting::getSetting('early_attendance_minutes')) {
                            return 0;
                        } else {
                            return $record->delay_minutes;
                        }
                    })
                    ->label('Delay Minuts')->sortable()->summarize(Sum::make()->query(fn (\Illuminate\Database\Query\Builder $query) => $query->where('delay_minutes', '>', 10)))
                    // ->summarize(fn($record): integer => 11)
                    ->toggleable(isToggledHiddenByDefault: true)->alignCenter(true),
                TextColumn::make('day')
                    ->label('Day')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('late_departure_minutes')
                    ->toggleable(isToggledHiddenByDefault: true)->alignCenter(true),
                TextColumn::make('message')
                    ->toggleable(isToggledHiddenByDefault: true)->alignCenter(true)->limit(50)->tooltip(fn ($state): string => $state ?? 'null'),
                TextColumn::make('early_departure_minutes')
                    ->label('Early departure minutes')->alignCenter(true)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->summarize(Sum::make()->query(fn (\Illuminate\Database\Query\Builder $query) => $query->where('early_departure_minutes', '>', 20))),
                // TextColumn::make('attendance_type')->alignCenter(true),
                TextColumn::make('created_at')->alignCenter(true)->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('source_label')
                    ->label(__('lang.attendance_type'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignCenter(true),

            ])
            ->filtersFormColumns(3)
            ->filters([
                Filter::make('id')
                    ->form([
                        TextInput::make('id')
                            ->label('ID')
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['id'],
                            fn (Builder $query, $id): Builder => $query->where('id', $id)
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['id']) {
                            return null;
                        }

                        return 'ID: '.$data['id'];
                    }),
                TrashedFilter::make(),

                SelectFilter::make('accepted')->searchable()->label('Rejected')->options([
                    0 => 'Yes',
                    1 => 'No',
                ])->default(1),
                SelectFilter::make('employee_id')->searchable()->label('Employee')->options(function (Get $get) {
                    return Employee::query()
                        ->active()
                        ->pluck('name', 'id');
                }),
                SelectFilter::make('branch_id')->searchable()->label('Branch')
                    ->options(function (Get $get) {
                        return Branch::query()
                            ->pluck('name', 'id');
                    }),

                Filter::make('month')
                    ->label('Filter by Month')
                    ->schema([

                        Select::make('year')
                            ->label('Year')
                            ->options(function () {
                                $years = range(Carbon::now()->year, Carbon::now()->year - 1); // Last 10 years

                                return array_combine($years, $years);
                            })
                            ->placeholder('Select a year'),
                        Select::make('month')
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

                        DatePicker::make('check_date')
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
                            return 'Month: '.Carbon::createFromDate($data['year'], $data['month'], 1)->format('F Y');
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
            ], FiltersLayout::Modal)
            ->recordActions([

                ActionGroup::make([
                    ViewAction::make(),
                    DeleteAction::make(),
                    // ForceDeleteAction::make(),
                    RestoreAction::make(),

                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    // ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
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
            'index' => ListAttendnaces::route('/'),
            'create' => CreateAttendnace::route('/create'),
            // 'edit' => Pages\EditAttendnace::route('/{record}/edit'),
            'view' => ViewAttendnace::route('/{record}'),
            // 'employee-attendance' => Pages\EmployeeAttendance::route('/employee-attendance'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        // return static::getModel()::where('employee_id',auth()->user()?->employee?->id)->count();
        return static::getModel()::whereHas('employee', function ($q) {
            $q->whereNull('deleted_at'); // ignore soft-deleted employees
        })->count();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
        $query->whereHas('employee', function ($q) {
            $q->whereNull('deleted_at'); // ignore soft-deleted employees
        });

        return $query;
    }

    public static function canDelete(Model $record): bool
    {
        if (isSuperAdmin() || isHR()) {
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
        if (isSuperAdmin() && isHakimOrAdel()) {
            return true;
        }

        return false;
    }

    public static function canForceDeleteAny(): bool
    {
        if (isSuperAdmin() && isHakimOrAdel()) {
            return true;
        }

        return false;
    }

    public static function canViewAny(): bool
    {
        if (isSystemManager() || isSuperAdmin() || isHR()) {
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
