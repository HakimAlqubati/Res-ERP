<?php

namespace App\Filament\Clusters\HRTasksSystem\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use DateTime;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Clusters\HRTasksSystem\Resources\DailyTasksSettingUpResource\Pages\ListDailyTasksSettingUps;
use App\Filament\Clusters\HRTasksSystem\Resources\DailyTasksSettingUpResource\Pages\CreateDailyTasksSettingUp;
use App\Filament\Clusters\HRTasksSystem\Resources\DailyTasksSettingUpResource\Pages\EditDailyTasksSettingUp;
use App\Filament\Clusters\HRTasksSystem\Resources\DailyTasksSettingUpResource\Pages\ViewDailyTasksSettingUp;
use App\Filament\Clusters\HRTasksSystem;
use App\Filament\Clusters\HRTasksSystem\Resources\DailyTasksSettingUpResource\Pages;
use App\Models\Branch;
use App\Models\DailyTasksSettingUp;
use App\Models\Employee;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class DailyTasksSettingUpResource extends Resource
{
    protected static ?string $model = DailyTasksSettingUp::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = HRTasksSystem::class;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 5;

    public static function getTitleCasePluralModelLabel(): string
    {
        return 'Scheduled Task Setup';
    }
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Fieldset::make()->schema([

                    Grid::make()->columns(4)->schema([
                        TextInput::make('title')
                            ->required()
                            ->autofocus()
                            ->columnSpan(1)
                            ->maxLength(255),
                        Select::make('assigned_by')
                            ->label('Assign by')
                            ->required()
                            ->default(auth()->user()->id)
                            ->columnSpan(1)
                            ->options(User::select('name', 'id')->get()->pluck('name', 'id'))->searchable()
                            ->selectablePlaceholder(false),
                        Select::make('assigned_to')
                            ->label('Assign to')
                            ->required()
                            ->columnSpan(1)
                            ->options(Employee::select('name', 'id')->get()->pluck('name', 'id'))->searchable()
                            ->selectablePlaceholder(false),
                        Toggle::make('active')->default(1)->inline(false)->columnSpan(1),

                    ]),

                    Fieldset::make()->label('Set schedule task type and start date of scheduele task')->schema([
                        Grid::make()->columns(4)->schema([
                            ToggleButtons::make('schedule_type')
                                ->label('')
                                ->columnSpan(2)
                                ->inline()
                                ->default(DailyTasksSettingUp::TYPE_SCHEDULE_DAILY)
                                ->options(DailyTasksSettingUp::getScheduleTypes())
                                ->live()
                                ->afterStateUpdated(function (Get $get, Set $set, $state, ?Model $record = null) {
                                    if (isset($record) && !is_null($record) && !is_null($record->taskScheduleRequrrencePattern)) {
                                        $pattern = $record->taskScheduleRequrrencePattern;
                                        $set('recur_count', $pattern->recur_count);
                                        $set('end_date', $pattern->end_date);
                                        $set('start_date', $pattern->start_date);

                                    } else {

                                        if ($state == DailyTasksSettingUp::TYPE_SCHEDULE_MONTHLY) {
                                            $set('end_date', date('Y-m-d', strtotime('+1 months')));
                                            $set('recur_count', 1);
                                        } elseif ($state == DailyTasksSettingUp::TYPE_SCHEDULE_WEEKLY) {
                                            $set('end_date', date('Y-m-d', strtotime('+2 weeks')));
                                            $set('recur_count', 2);
                                        } elseif ($state == DailyTasksSettingUp::TYPE_SCHEDULE_DAILY) {
                                            $set('end_date', date('Y-m-d', strtotime('+7 days')));
                                            $set('recur_count', 7);
                                        }
                                    }
                                })
                            ,
                            Grid::make()->columns(1)->columnSpan(1)->schema([
                                DatePicker::make('start_date')->default(date('Y-m-d', strtotime('+1 days')))->columnSpan(1)->minDate(date('Y-m-d'))->live()
                                ,
                                TextInput::make('recur_count')->label(function (Get $get) {
                                    if ($get('schedule_type') == DailyTasksSettingUp::TYPE_SCHEDULE_DAILY) {
                                        return 'Count days';
                                    } elseif ($get('schedule_type') == DailyTasksSettingUp::TYPE_SCHEDULE_WEEKLY) {
                                        return 'Count weeks';
                                    } elseif ($get('schedule_type') == DailyTasksSettingUp::TYPE_SCHEDULE_MONTHLY) {
                                        return 'Count months';
                                    }

                                })->live()->afterStateUpdated(function (Get $get, Set $set, $state) {
                                    if ($get('schedule_type') == DailyTasksSettingUp::TYPE_SCHEDULE_DAILY) {
                                        $set('end_date', date('Y-m-d', strtotime("+$state days")));
                                    } elseif ($get('schedule_type') == DailyTasksSettingUp::TYPE_SCHEDULE_WEEKLY) {
                                        $set('end_date', date('Y-m-d', strtotime("+$state weeks")));
                                    } elseif ($get('schedule_type') == DailyTasksSettingUp::TYPE_SCHEDULE_MONTHLY) {
                                        $set('end_date', date('Y-m-d', strtotime("+$state months")));
                                    }

                                }),
                            ]),
                            DatePicker::make('end_date')->default(date('Y-m-d', strtotime('+7 days')))->columnSpan(1)->minDate(date('Y-m-d'))
                                ->live()->afterStateUpdated(function (Get $get, Set $set, $state) {

                                $date1 = new DateTime($get('start_date'));
                                $date2 = new DateTime($state);

                                $interval = $date1->diff($date2);

                                if ($get('schedule_type') == DailyTasksSettingUp::TYPE_SCHEDULE_DAILY) {
                                    $set('recur_count', $interval->days);
                                } elseif ($get('schedule_type') == DailyTasksSettingUp::TYPE_SCHEDULE_WEEKLY) {
                                    $weeks = floor($interval->days / 7);
                                    $set('recur_count', $weeks);
                                } elseif ($get('schedule_type') == DailyTasksSettingUp::TYPE_SCHEDULE_MONTHLY) {
                                    $months = ($interval->y * 12) + $interval->m;
                                    $set('recur_count', $months);
                                }
                            })
                            ,

                        ])

                        ,
                        Fieldset::make('requrrence_pattern')->label('Recurrence pattern')->schema([
                            Fieldset::make()->label('')->visible(fn(Get $get): bool => ($get('schedule_type') == 'daily'))->schema([
                                Grid::make()->label('')->columns(2)->schema([
                                    Radio::make('requr_pattern_set_days')->label('')
                                        ->options([
                                            'specific_days' => 'Every',
                                            'every_day' => 'Every weekday',
                                        ])->live(),
                                    TextInput::make('requr_pattern_day_recurrence_each')->minValue(1)->maxValue(7)->numeric()->hidden(fn(Get $get): bool => ($get('requr_pattern_set_days') == 'every_day'))->label('Day(s)'),
                                ]),
                            ]),
                            Fieldset::make()->label('')->visible(fn(Get $get): bool => ($get('schedule_type') == 'weekly'))->schema([
                                Grid::make()->label('')->columns(2)->schema([
                                    TextInput::make('requr_pattern_week_recur_every')->minValue(1)->maxValue(5)->numeric()->label('Recur every')->helperText('Week(s) on:')
                                    ,
                                    ToggleButtons::make('requr_pattern_weekly_days')->label('')->inline()->options(getDays())->multiple(),
                                ]),
                            ]),
                            Fieldset::make()->label('')->visible(fn(Get $get): bool => ($get('schedule_type') == 'monthly'))->schema([
                                Grid::make()->label('')->columns(3)->schema([
                                    Radio::make('requr_pattern_monthly_status')->label('')
                                        ->columnSpan(1)
                                        ->options([
                                            'day' => 'Day',
                                            'the' => 'The',
                                        ])->live()
                                    // ->default('day')
                                    ,
                                    Grid::make()->columns(2)->columnSpan(2)->visible(fn(Get $get): bool => ($get('requr_pattern_monthly_status') == 'day'))->schema([
                                        TextInput::make('requr_pattern_the_day_of_every')->default(15)->numeric()->label('')->helperText('Of every'),
                                        TextInput::make('requr_pattern_months')->label('')->default(1)->numeric()->helperText('Month(s)'),
                                    ]),
                                    Grid::make()->columns(2)->visible(fn(Get $get): bool => ($get('requr_pattern_monthly_status') == 'the'))->columnSpan(2)->schema([
                                        Select::make('requr_pattern_order_name')->label('')->options([
                                            'first' => 'first',
                                            'second' => 'second',
                                            'third' => 'third',
                                            'fourth' => 'fourth',
                                            'fifth' => 'fifth'])->default('first'),
                                        Select::make('requr_pattern_order_day')->label('')->options(getDays())->default('Saturday'),
                                    ]),
                                ]),
                            ]),
                        ]),
                    ]),
                    Textarea::make('description')
                        ->required()
                        ->maxLength(65535)
                        ->columnSpanFull(),
                    Repeater::make('steps')
                        ->itemLabel('Steps')
                        ->columnSpanFull()
                        ->relationship('steps')
                        ->columns(1)
                        ->schema([
                            TextInput::make('title')
                                ->required()
                                ->live(onBlur: true),
                        ])
                        ->collapseAllAction(
                            fn(Action $action) => $action->label('Collapse all steps'),
                        )
                        ->orderColumn('order')
                        ->reorderable()
                        ->reorderableWithDragAndDrop()
                        ->reorderableWithButtons()
                        ->cloneable()
                        ->collapsible()
                        ->itemLabel(fn(array $state): ?string => $state['name'] ?? null),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->searchable()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('title')->searchable()->alignCenter(true),
                TextColumn::make('schedule_type')->searchable()->sortable()->label('Type')->alignCenter(true),
                TextColumn::make('description')->limit(30)->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('step_count')
                    ->color(Color::Blue)->alignCenter(true)->label('Steps')
                    ->searchable()->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('assignedto.name')->label('Assigned to')
                    ->words(3)->wrap()
                    ->searchable()->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('assignedby.name')
                    ->words(3)->wrap()
                    ->label('Assigned by')->searchable()->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('start_date')->label('Start date')->sortable()->searchable()->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('end_date')->label('End date')->sortable()->searchable()->toggleable(isToggledHiddenByDefault: false),
                ToggleColumn::make('active')->label('Active?')->sortable()->disabled()->searchable()->toggleable(isToggledHiddenByDefault: false),

            ])
            ->filters([
                SelectFilter::make('schedule_type')->label('Schedule type')->multiple()->options(
                    [
                        DailyTasksSettingUp::TYPE_SCHEDULE_DAILY => DailyTasksSettingUp::TYPE_SCHEDULE_DAILY,
                        DailyTasksSettingUp::TYPE_SCHEDULE_WEEKLY => DailyTasksSettingUp::TYPE_SCHEDULE_WEEKLY,
                        DailyTasksSettingUp::TYPE_SCHEDULE_MONTHLY => DailyTasksSettingUp::TYPE_SCHEDULE_MONTHLY,

                    ]
                ),
                SelectFilter::make('branch_id')->label('Branch')->multiple()->options(
                    Branch::select('name', 'id')->pluck('name', 'id')
                ),
            ],FiltersLayout::AboveContent)
            ->recordActions([
                EditAction::make(),
                // ActionGroup::make([
                //     Tables\Actions\EditAction::make(),
                // Tables\Actions\ViewAction::make(),
                // ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
        $query = static::getModel();

        if (!in_array(getCurrentRole(), [1, 16, 3])) {
            return $query::where('assigned_by', auth()->user()->id)
                ->orWhere('assigned_to', auth()->user()->id)
                ->orWhere('assigned_to', auth()->user()?->employee?->id)->count();
        }

        return $query::count();

    }

    public static function getPages(): array
    {
        return [
            'index' => ListDailyTasksSettingUps::route('/'),
            'create' => CreateDailyTasksSettingUp::route('/create'),
            'edit' => EditDailyTasksSettingUp::route('/{record}/edit'),
            'view' => ViewDailyTasksSettingUp::route('/{record}'),
        ];
    }

    // public static function getEloquentQuery(): Builder
    // {
    //     return static::getModel()::query();
    //     $query = static::getModel()::query();

    //     if (
    //         static::isScopedToTenant() &&
    //         ($tenant = Filament::getTenant())
    //     ) {
    //         static::scopeEloquentQueryToTenant($query, $tenant);
    //     }

    //     // if (!isSuperAdmin() && auth()->user()->can('view_own_task')) {
    //     //     $query->where('assigned_to', auth()->user()->id)
    //     //         ->orWhere('assigned_to', auth()->user()?->employee?->id)
    //     //         ->orWhere('created_by', auth()->user()->id)
    //     //     ;
    //     // }

    //     if (!in_array(getCurrentRole(), [1, 3])) {
    //         $query->where('assigned_to', auth()->user()->id)
    //             ->orWhere('assigned_to', auth()->user()?->employee?->id)
    //             ->orWhere('assigned_by', auth()->user()?->employee?->id)
    //             ->orWhere('assigned_by', auth()->user()->id)
    //         // ->orWhere('created_by', auth()->user()->id)
    //         ;
    //     }
    //     return $query;
    // }

    public static function canView(Model $record): bool
    {

        if (isSuperAdmin() || (isBranchManager() && $record->assigned_by == auth()?->user()?->employee?->id) ||
            (isSystemManager() && $record->assigned_by == auth()?->user()?->employee?->id)
        ) {
            return true;
        }
        return false;
    }

    public static function canViewAny(): bool
    {
        if (isSuperAdmin() || isSystemManager() || isBranchManager()) {
            return true;
        }
        return false;
    }

    public static function canEdit(Model $record): bool
    {

        if (isSuperAdmin() || (isBranchManager() && $record->assigned_by == auth()?->user()?->id) ||
            (isSystemManager() && $record->assigned_by == auth()?->user()?->id)
            || isStuff() || isFinanceManager()) {
            return true;
        }
        return false;
    }
}
