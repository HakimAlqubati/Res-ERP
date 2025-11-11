<?php

namespace App\Filament\Clusters\HRTasksSystem\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Spatie\Permission\Models\Role;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use DateTime;
use Filament\Actions\Action;
use Filament\Support\Enums\TextSize;
use Throwable;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\CreateAction;
use App\Filament\Clusters\HRTasksSystem\Resources\TaskResource\Pages\ListTasks;
use App\Filament\Clusters\HRTasksSystem\Resources\TaskResource\Pages\CreateTask;
use App\Filament\Clusters\HRTasksSystem\Resources\TaskResource\Pages\TaskStepsPage;
use App\Filament\Clusters\HRAttendanceReport\Resources\EmployeeTaskReportResource\Widgets\TaskWidgetChart;
use App\Filament\Clusters\HRServiceRequestCluster\Resources\ServiceRequestResource\RelationManagers\CommentsRelationManager;
use App\Filament\Clusters\HRTasksSystem;
use App\Filament\Clusters\HRTasksSystem\Resources\TaskLogRelationManagerResource\RelationManagers\LogsRelationManager;
use App\Filament\Clusters\HRTasksSystem\Resources\TaskResource\Pages;
use App\Filament\Clusters\HRTasksSystem\Resources\TaskResource\Pages\EditTask;
use App\Filament\Clusters\HRTasksSystem\Resources\TaskResource\RelationManagers\StepsRelationManager;
use App\Models\DailyTasksSettingUp;
use App\Models\Employee;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskCard;
use App\Models\TaskLog;
use App\Models\User;
use App\Models\UserType;
// use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use function Laravel\Prompts\form;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Mokhosh\FilamentRating\Components\Rating;
use Mokhosh\FilamentRating\RatingTheme;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static string | \BackedEnum | null $navigationIcon =  Heroicon::ListBullet;

    protected static ?string $cluster = HRTasksSystem::class;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
        $query = static::getModel();

        if (!in_array(getCurrentRole(), [1, 3])) {
            return $query::where('assigned_to', auth()->user()->id)
                ->orWhere('assigned_to', auth()->user()?->employee?->id)
                ->orWhere('created_by', auth()->user()->id)->count();
        }

        return $query::count();
    }

    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'view_own',
            // 'view_assigned_by',
            'create',
            'update',
            // 'delete',
            // 'delete_any',
            // 'forse_delete',
            'publish',
            'rating',
            'add_comment',
            'add_photo',
            'move_status',
        ];
    }
    public static function form(Schema $schema): Schema
    {

        return $schema
            ->components([
                Fieldset::make()->columnSpanFull()->schema([


                    Wizard::make()->columnSpanFull()->skippable(true)->schema([
                        Step::make('Basic')->columnSpanFull()->schema([
                            Grid::make()->columnSpanFull()->columns(7)->schema([
                                TextInput::make('title')
                                    ->required()
                                    ->disabledOn('edit')
                                    ->autofocus()
                                    ->columnSpan(2)
                                    ->maxLength(255),
                                Select::make('assigned_by')
                                    ->label('Assign by')
                                    ->disabledOn('edit')
                                    ->required()
                                    ->default(auth()->user()->id)
                                    ->columnSpan(2)
                                    ->options(User::select('name', 'id')
                                        // ->active()
                                        // ->whereIn('user_type',[1,2,3])
                                        ->get()->pluck('name', 'id'))->searchable()
                                    ->selectablePlaceholder(false),
                                // Select Role to assign task
                                Select::make('role')
                                    ->label('Select Role')
                                    ->hiddenOn('edit')
                                    ->options(function () {
                                        return UserType::where('active', 1)->select('id', 'name')->get()->pluck('name', 'id');
                                        // Fetch all available roles (assuming you have a Role model)
                                        return Role::pluck('name', 'id')->toArray();
                                    })->live()
                                    ->afterStateUpdated(function (Get $get, Set $set, $state) {

                                        $employees = Employee::where('employee_type', $state)->active()->pluck('id')->toArray();

                                        $set('assigned_to_multi', $employees); // Populate the 'assigned_to' field with these users
                                        // // After role is selected, fetch users assigned to that role
                                        // $users = \App\Models\User::whereHas('roles', function ($q) use ($state) {
                                        //     $q->where('id', $state); // Filter users by the selected role
                                        // })->get(['name', 'id'])->pluck('id')->toArray();
                                        // // dd($users, $state);
                                        // $set('assigned_to_multi', $users); // Populate the 'assigned_to' field with these users
                                    }),


                                // Forms\Components\Select::make('assigned_to')
                                //     ->hidden(fn($get): bool =>  !is_null($get('role')))
                                //     ->label('Assign to')
                                //     ->disabledOn('edit')
                                //     ->required()
                                //     ->columnSpan(2)
                                //     ->options(Employee::where('active', 1)->select('name', 'id')->get()->pluck('name', 'id'))->searchable()
                                //     ->selectablePlaceholder(false),
                                Toggle::make('is_daily')->live()->default(0)->label('Scheduled task?')
                                    ->disabledOn('edit')->inline(false)
                                    ->hidden(fn(): bool => isStuff()),
                                Select::make('assigned_to')
                                    ->options(Employee::where('active', 1)->select('name', 'id')->get()->pluck('name', 'id'))->searchable()
                                    ->label('Assign to')
                                    ->columnSpan(2)
                                    ->required()
                                    ->disabled()
                                    ->hiddenOn('create'),
                                // The assigned_to field that will populate with users based on the selected role
                                Select::make('assigned_to_multi')
                                    ->label('Assign to')
                                    ->columnSpanFull()
                                    ->required()
                                    ->options(Employee::where('active', 1)->select('name', 'id')->get()->pluck('name', 'id'))->searchable()
                                    ->multiple()  // Allow multiple users to be selected
                                    ->searchable()
                                    ->hidden(fn() => isStuff())
                                    ->hiddenOn('edit'),

                            ]),
                        ]),
                        Step::make('Details')->columnSpanFull()
                            ->hiddenOn('edit')
                            ->visible(
                                fn(Get $get): bool => $get('is_daily')
                            )->schema([
                                Fieldset::make()->columnSpanFull()
                                    ->label('Set schedule task type and start date of scheduele task')->schema([
                                        Grid::make()->columnSpanFull()->columns(4)->schema([
                                            ToggleButtons::make('schedule_type')
                                                ->label('')
                                                ->columnSpan(2)
                                                ->inline()
                                                ->default(DailyTasksSettingUp::TYPE_SCHEDULE_DAILY)
                                                ->options(DailyTasksSettingUp::getScheduleTypes())
                                                ->live()
                                                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                                    if ($state == DailyTasksSettingUp::TYPE_SCHEDULE_MONTHLY) {
                                                        $set('end_date', date('Y-m-d', strtotime('+1 months')));
                                                        $set('recur_count', 1);
                                                    } elseif ($state == DailyTasksSettingUp::TYPE_SCHEDULE_WEEKLY) {
                                                        $set('end_date', date('Y-m-d', strtotime('+2 weeks')));
                                                        $set('recur_count', 2);
                                                    } elseif ($state == DailyTasksSettingUp::TYPE_SCHEDULE_DAILY) {
                                                        $set('end_date', date('Y-m-d', strtotime('+9 days')));
                                                        $set('recur_count', 7);
                                                    }
                                                }),
                                            Grid::make()->columnSpanFull()->columns(1)->columnSpan(1)->schema([
                                                DatePicker::make('start_date')
                                                    ->default(date('Y-m-d', strtotime('+1 days')))
                                                    ->columnSpan(1)
                                                    ->minDate(date('Y-m-d'))->live()
                                                    ->native(false)
                                                    ->displayFormat('d/m/Y')
                                                    ->live()->afterStateUpdated(function (Get $get, Set $set, $state) {

                                                        $date1 = new DateTime($state);
                                                        $date2 = new DateTime($get('end_date'));

                                                        $interval = $date1->diff($date2);

                                                        if ($get('schedule_type') == DailyTasksSettingUp::TYPE_SCHEDULE_DAILY) {

                                                            $set('recur_count', $interval->days + 2);
                                                        } elseif ($get('schedule_type') == DailyTasksSettingUp::TYPE_SCHEDULE_WEEKLY) {
                                                            $weeks = floor($interval->days / 7);
                                                            $set('recur_count', $weeks);
                                                        } elseif ($get('schedule_type') == DailyTasksSettingUp::TYPE_SCHEDULE_MONTHLY) {
                                                            $months = ($interval->y * 12) + $interval->m;
                                                            $set('recur_count', $months);
                                                        }
                                                    }),
                                                TextInput::make('recur_count')
                                                    ->default(7)
                                                    ->label(function (Get $get) {
                                                        if ($get('schedule_type') == DailyTasksSettingUp::TYPE_SCHEDULE_DAILY) {
                                                            return 'Number of days';
                                                        } elseif ($get('schedule_type') == DailyTasksSettingUp::TYPE_SCHEDULE_WEEKLY) {
                                                            return 'Number of weeks';
                                                        } elseif ($get('schedule_type') == DailyTasksSettingUp::TYPE_SCHEDULE_MONTHLY) {
                                                            return 'Number of months';
                                                        }
                                                    })->live()->afterStateUpdated(function (Get $get, Set $set, $state) {
                                                        if ($get('schedule_type') == DailyTasksSettingUp::TYPE_SCHEDULE_DAILY) {
                                                            $state += 1;
                                                            // $state= (integer) $state;
                                                            $set('end_date', date('Y-m-d', strtotime("+$state days")));
                                                        } elseif ($get('schedule_type') == DailyTasksSettingUp::TYPE_SCHEDULE_WEEKLY) {
                                                            $set('end_date', date('Y-m-d', strtotime("+$state weeks")));
                                                        } elseif ($get('schedule_type') == DailyTasksSettingUp::TYPE_SCHEDULE_MONTHLY) {
                                                            $set('end_date', date('Y-m-d', strtotime("+$state months")));
                                                        }
                                                    })->required(),
                                            ]),
                                            DatePicker::make('end_date')
                                                ->native(false)
                                                ->displayFormat('d/m/Y')

                                                ->default(date('Y-m-d', strtotime('+7 days')))->columnSpan(1)
                                                // ->minDate(date('Y-m-d'))
                                                ->minDate(fn(Get $get) => $get('start_date') ?? date('Y-m-d')) // Dynamically set minDate based on start_date
                                                ->live()->afterStateUpdated(function (Get $get, Set $set, $state) {

                                                    $date1 = new DateTime($get('start_date'));
                                                    $date2 = new DateTime($state);

                                                    $interval = $date1->diff($date2);

                                                    if ($get('schedule_type') == DailyTasksSettingUp::TYPE_SCHEDULE_DAILY) {

                                                        $set('recur_count', $interval->days + 2);
                                                    } elseif ($get('schedule_type') == DailyTasksSettingUp::TYPE_SCHEDULE_WEEKLY) {
                                                        $weeks = floor($interval->days / 7);
                                                        $set('recur_count', $weeks);
                                                    } elseif ($get('schedule_type') == DailyTasksSettingUp::TYPE_SCHEDULE_MONTHLY) {
                                                        $months = ($interval->y * 12) + $interval->m;
                                                        $set('recur_count', $months);
                                                    }
                                                }),

                                        ]),
                                        Fieldset::make('requrrence_pattern')
                                            ->columnSpanFull()
                                            ->label('Recurrence pattern')->schema([
                                                Fieldset::make()->columnSpanFull()->label('')->visible(fn(Get $get): bool => ($get('schedule_type') == 'daily'))->schema([
                                                    Grid::make()->columnSpanFull()->columns(2)->schema([
                                                        Radio::make('requr_pattern_set_days')->label('')
                                                            ->options([
                                                                'specific_days' => 'Every',
                                                                'every_day' => 'Every weekday',
                                                            ])->live(),
                                                        TextInput::make('requr_pattern_day_recurrence_each')->minValue(1)->maxValue(7)->numeric()->hidden(fn(Get $get): bool => ($get('requr_pattern_set_days') == 'every_day'))->label('Day(s)')->required(),
                                                    ]),
                                                ]),
                                                Fieldset::make()->columnSpanFull()->label('')->visible(fn(Get $get): bool => ($get('schedule_type') == 'weekly'))->schema([
                                                    Grid::make()->columnSpanFull()->columns(2)->schema([
                                                        TextInput::make('requr_pattern_week_recur_every')->minValue(1)->maxValue(5)->numeric()->label('Recur every')->helperText('Week(s) on:')->required(),
                                                        ToggleButtons::make('requr_pattern_weekly_days')->label('')->inline()->options(getDays())->multiple(),
                                                    ]),
                                                ]),
                                                Fieldset::make()->columnSpanFull()->label('')->visible(fn(Get $get): bool => ($get('schedule_type') == 'monthly'))->schema([
                                                    Grid::make()
                                                        ->columnSpanFull()
                                                        ->columns(3)->schema([
                                                            Radio::make('requr_pattern_monthly_status')->label('')
                                                                ->columnSpan(1)
                                                                ->options([
                                                                    'day' => 'Day',
                                                                    'the' => 'The',
                                                                ])->live()->default('day'),
                                                            Grid::make()->columnSpanFull()->columns(2)->columnSpan(2)->visible(fn(Get $get): bool => ($get('requr_pattern_monthly_status') == 'day'))->schema([
                                                                TextInput::make('requr_pattern_the_day_of_every')->default(15)->numeric()->label('')->helperText('Of every'),
                                                                TextInput::make('requr_pattern_months')->label('')->default(1)->numeric()->helperText('Month(s)'),
                                                            ]),
                                                            Grid::make()->columnSpanFull()->columns(2)->visible(fn(Get $get): bool => ($get('requr_pattern_monthly_status') == 'the'))->columnSpan(2)->schema([
                                                                Select::make('requr_pattern_order_name')->label('')->options([
                                                                    'first' => 'first',
                                                                    'second' => 'second',
                                                                    'third' => 'third',
                                                                    'fourth' => 'fourth',
                                                                    'fifth' => 'fifth'
                                                                ])->default('first'),
                                                                Select::make('requr_pattern_order_day')->label('')->options(getDays())->default('Saturday'),
                                                            ]),
                                                        ]),
                                                ]),
                                            ]),
                                    ]),

                            ]),
                        Step::make('Steps')->columnSpanFull()->schema([


                            Textarea::make('description')
                                // ->required()
                                ->disabledOn('edit')
                                ->maxLength(65535)
                                ->columnSpanFull(),

                            Grid::make()->columnSpanFull()->visible(fn(Get $get): bool => !$get('is_daily'))->columns(2)->schema([
                                DatePicker::make('due_date')->label('Due date')->required(false)
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->disabled(function ($record) {
                                        if (isset($record, auth()->user()->employee)) {
                                            if ($record->assigned_to == auth()->user()->employee->id) {
                                                return true;
                                            }
                                        }
                                        return false;
                                    })
                                    // ->minDate(now()->toDateString())
                                    ->helperText('Set due date for this task'),
                                Select::make('task_status')->options(
                                    [
                                        Task::STATUS_NEW => Task::STATUS_NEW,
                                        // Task::STATUS_PENDING => Task::STATUS_PENDING,
                                        Task::STATUS_IN_PROGRESS => Task::STATUS_IN_PROGRESS,
                                        Task::STATUS_CLOSED => Task::STATUS_CLOSED,
                                        Task::STATUS_REJECTED => Task::STATUS_REJECTED,
                                    ]
                                )->default(Task::STATUS_NEW)
                                    ->disabledOn('create')
                                    ->disabled(),

                            ]),
                            Hidden::make('created_by')->default(auth()->user()->id),
                            Hidden::make('updated_by')->default(auth()->user()->id),

                            Fieldset::make('task_rating')->columnSpanFull()->relationship('task_rating')
                                // ->hiddenOn('create')
                                ->hidden(function ($record) {
                                    if (isset($record)) {
                                        if ($record->task_status != Task::STATUS_CLOSED) {
                                            return true;
                                        }
                                    }
                                })
                                ->disabled(function ($record) {
                                    if (isset($record)) {
                                        if (($record->assigned_to == auth()?->user()?->id) || ($record->assigned_to == auth()->user()?->employee?->id)) {
                                            return true;
                                        }
                                        return false;
                                    }
                                })
                                ->visibleOn('edit')
                                ->label('')->schema([
                                    // Rating::make('rating_value')
                                    //     ->theme(RatingTheme::HalfStars)
                                    //     ->label('')->theme(RatingTheme::Simple)->stars(10)->size('lg')
                                    //     ->helperText(function ($record) {

                                    //         if (is_null($record?->rating_value)) {
                                    //             return 'Rate this from 0 to 10';
                                    //         } else {
                                    //             return "Your rating:" . $record->rating_value . "/10";
                                    //         }
                                    //     })
                                    //     ->live()
                                    //     ->afterStateUpdated(function (?string $state, ?string $old, $component) {
                                    //         $component->helperText("Your rating: $state/10");
                                    //     }),
                                ])->hidden(),

                            SpatieMediaLibraryFileUpload::make('file_path')
                                ->label('Add photos')->columnSpanFull()
                                ->disk('public')
                                ->directory('tasks')
                                ->visibility('public')
                                ->columnSpanFull()
                                ->imagePreviewHeight('250')
                                ->image()
                                // ->resize(5)
                                ->loadingIndicatorPosition('left')
                                // ->panelAspectRatio('2:1')
                                ->panelLayout('integrated')
                                ->removeUploadedFileButtonPosition('right')
                                ->uploadButtonPosition('left')
                                ->uploadProgressIndicatorPosition('left')
                                ->multiple()
                                ->panelLayout('grid')
                                ->reorderable()
                                ->openable()
                                ->downloadable()
                                ->hiddenOn('create')
                                ->previewable()
                                ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                    return (string) str($file->getClientOriginalName())->prepend('task-');
                                }),

                            Hidden::make('created_by')->default(auth()->user()->id),
                            Hidden::make('updated_by')->default(auth()->user()->id),
                            Repeater::make('steps')
                                ->itemLabel('Steps')
                                ->columnSpanFull()
                                ->relationship('steps')
                                ->columns(1)
                                ->hiddenOn('edit')
                                ->table([
                                    TableColumn::make('Title'),
                                ])
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
                    ])

                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        // $task = Task::countMovesToStatus(299,Task::STATUS_REJECTED);
        // dd($task);
        return $table->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultSort('id', 'desc')
            ->columns([
                SpatieMediaLibraryImageColumn::make('attachments')->label('media')
                    ->circular()->alignCenter(true)
                     ->conversion('thumb')
                     ->collection('attachments') 
                    ->limit(3),
                TextColumn::make('id')->sortable()->alignCenter(true)
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('title')->sortable()->wrap()->words(4)
                    ->color(Color::Blue)
                    ->size(TextSize::Large)
                    ->weight(FontWeight::ExtraBold)
                    ->description('Click')
                    ->searchable(),
                TextColumn::make('step_count')->label('Steps')
                    ->color(Color::Blue)->alignCenter(true)
                    ->searchable(),
                TextColumn::make('views')->label('Views')->sortable()
                    ->color(Color::Blue)->alignCenter(true)
                    ->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('task_status')->label('Status')
                    ->badge()->alignCenter(true)
                    ->icon(fn(string $state): string => match ($state) {
                        Task::STATUS_NEW =>  Task::ICON_NEW,
                        // Task::STATUS_PENDING =>  Task::ICON_PENDING,
                        Task::STATUS_IN_PROGRESS =>  Task::ICON_IN_PROGRESS,
                        Task::STATUS_CLOSED =>  Task::ICON_CLOSED,
                        Task::STATUS_REJECTED =>  Task::ICON_REJECTED,
                    })
                    ->color(fn(string $state): string => match ($state) {
                        Task::STATUS_NEW => Task::STATUS_NEW,
                        // Task::STATUS_PENDING => Task::COLOR_PENDING,
                        Task::STATUS_IN_PROGRESS => Task::COLOR_IN_PROGRESS,

                        Task::STATUS_CLOSED => Task::COLOR_CLOSED,
                        Task::STATUS_REJECTED => Task::COLOR_REJECTED,
                        // default => 'gray', // Fallback color in case of unknown status
                    })
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('assigned.name')
                    ->label('Assigned To')
                    ->searchable()->wrap()->limit(20)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('createdby.name')
                    ->label('created By')
                    ->searchable()->wrap()->limit(20)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('assignedby.name')
                    ->label('Assigned By')
                    ->searchable()->wrap()->limit(20)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Tables\Columns\TextColumn::make('photos_count')

                // ->icon('heroicon-o-camera')

                // ->toggleable(isToggledHiddenByDefault: false)
                // ,
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('due_date')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total_spent_time')
                    ->label('Time Spent')->toggleable(isToggledHiddenByDefault: true)
                    ->alignCenter(true)
                    ->formatStateUsing(fn($state) => $state ?? 'N/A'),
                TextColumn::make('rejection_count')
                    ->label('Rejection times')->toggleable(isToggledHiddenByDefault: true)
                    ->alignCenter(true)

            ])
            ->filters([
                SelectFilter::make('task_status')->label('Status')->multiple()->options(
                    [
                        Task::STATUS_NEW => Task::STATUS_NEW,
                        // Task::STATUS_PENDING => Task::STATUS_PENDING,
                        Task::STATUS_IN_PROGRESS => Task::STATUS_IN_PROGRESS,
                        Task::STATUS_REJECTED => Task::STATUS_REJECTED,
                        Task::STATUS_CLOSED => Task::STATUS_CLOSED,
                    ]
                ),
                // Tables\Filters\Filter::make('is_daily')
                // ->label('Scheduled Task')
                // ->query(fn(Builder $query) => $query->where('is_daily', 0)) // Default to show non-scheduled tasks
                // // ->toggleable() // Allow toggling to show both true and false values
                // ->default(0),
            ])
            ->selectable()
            ->recordActions([

                Action::make('viewGallery')
                    ->hidden(function ($record) {
                        return $record->photos_count <= 0 ? true : false;
                    })
                    ->label('Browse photos')
                    ->label(function ($record) {
                        return $record->photos_count;
                    })
                    ->modalHeading('Task photos')
                    ->modalWidth('lg') // Adjust modal size
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    // ->iconButton()
                    ->button()
                    ->icon('heroicon-o-camera')
                    ->modalContent(function ($record) {
                        return view('filament.resources.task.gallery', ['photos' => $record->photos]);
                    }),
                Action::make('AddPhotos')
                    ->hidden(function ($record) {
                        if ($record->is_daily) {
                            return true;
                        }
                        // if ($record->task_status == Task::STATUS_CLOSED) {
                        if (in_array($record->task_status, [Task::STATUS_REJECTED, Task::STATUS_CLOSED])) {
                            return true;
                        }
                        if (!isSuperAdmin() && !auth()->user()->can('add_photo_task')) {
                            return true;
                        }
                    })
                    ->schema([

                        FileUpload::make('file_path')
                            ->disk('public')
                            ->label('')
                            ->directory('tasks')
                            ->columnSpanFull()
                            ->image()
                            ->multiple()
                            // ->resize(5)
                            ->downloadable()
                            ->previewable()
                            ->imagePreviewHeight('250')
                            ->loadingIndicatorPosition('left')
                            ->panelLayout('integrated')
                            ->removeUploadedFileButtonPosition('right')
                            ->uploadButtonPosition('left')
                            ->uploadProgressIndicatorPosition('left')
                            ->panelLayout('grid')
                            ->reorderable()
                            ->openable()
                            ->downloadable(true)
                            ->previewable(true)
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                return (string) str($file->getClientOriginalName())->prepend('task-');
                            }),
                    ])
                    ->action(function (array $data, Task $record): void {
                        if (isset($data['file_path']) && is_array($data['file_path']) && count($data['file_path']) > 0) {
                            foreach ($data['file_path'] as $file) {
                                TaskAttachment::create([
                                    'task_id' => $record->id,
                                    'file_name' => $file,
                                    'file_path' => $file,
                                    'created_by' => auth()->user()->id,
                                    'updated_by' => auth()->user()->id,
                                ]);
                            }
                        }
                    })
                    ->button()
                    ->icon('heroicon-m-newspaper')
                    ->color('success'),
                Action::make('Rating')
                    ->label(function ($record) {
                        if ($record->task_rating()->exists()) {
                            return 'Rating done';
                        } else {
                            return 'Rating';
                        }
                    })
                    ->button()
                    ->hidden(function ($record) {
                        if ($record->is_daily) {
                            return true;
                        }

                        if (!$record->task_status != Task::STATUS_REJECTED) {
                            return true;
                        }
                        // // if (!isSystemManager() && !isSuperAdmin() ||
                        // if ((isBranchManager() && $record->assigned_to == auth()?->user()?->employee?->id)) {
                        //     return true;
                        // }

                        // // if (!in_array(getCurrentRole(), [1, 2])) {
                        // //     return true;
                        // // }

                        // // Check if the task status is not 'completed'
                        // if ($record->task_status !== Task::STATUS_CLOSED) {
                        //     return true;
                        // }
                        return false;
                    })
                    ->fillForm(
                        fn(Task $record): array => [
                            'task_employee' => $record->assigned->name,
                            // 'task_rating.employee_id' => $record->assigned_to,
                        ]
                    )
                    ->visible(function ($record) {
                        if (
                            $record->task_status == Task::STATUS_CLOSED
                            && ($record->assigned_by == auth()->user()->id || $record->created_by == auth()->user()->id) || isSuperAdmin()
                        ) {
                            return true;
                        }
                        return false;
                    })
                    // ->requiresConfirmation()
                    ->schema(function ($record) {
                        // dd($record->assigned->name);
                        return [
                            TextInput::make('task_employee')->disabled()->columnSpanFull(),
                            Fieldset::make('task_rating')->relationship('task_rating')->label('')->schema([

                                Hidden::make('employee_id')->default(function () use ($record) {
                                    return $record->assigned_to;
                                }),
                                Hidden::make('created_by')->default(function () use ($record) {
                                    return auth()->user()->id;
                                }),
                                Rating::make('rating_value')

                                    ->default(1)
                                    ->theme(RatingTheme::HalfStars)
                                    ->label('')->theme(RatingTheme::Simple)->stars(10)->size('lg')
                                    ->helperText(function ($record) {

                                        if (is_null($record?->rating_value)) {
                                            return 'Rate this from 1 to 10';
                                        } else {
                                            return "Your rating:" . $record->rating_value . "/10";
                                        }
                                    })
                                    ->live()
                                    ->afterStateUpdated(function (?string $state, ?string $old, $component) {
                                        $component->helperText("Your rating: $state/10");
                                    }),
                                // TextInput::make('user')->default($record->assigned->name)->disabled()->label('Task employee'),

                                Textarea::make('comment')->columnSpanFull(),

                                // Hidden::
                            ]),
                        ];
                    })

                    ->tooltip('Rating the Task for 10 Stars')
                    ->icon('heroicon-m-star')
                    ->color('info'),
                Action::make('MoveTask')
                    ->button()->requiresConfirmation()
                    ->hidden(function ($record) {
                        if ($record->is_daily) {
                            return true;
                        }
                        return false;
                    })
                    ->label(function ($record) {
                        if ($record->task_status == Task::STATUS_CLOSED) {
                            return 'Closed';
                        } elseif ($record->task_status == Task::STATUS_REJECTED) {
                            return 'Reopen';
                        } else {
                            return 'Move task';
                        }
                    })
                    // ->icon(fn($record): string => $record->task_status == Task::STATUS_CLOSED ? 'heroicon-m-check-badge' : 'heroicon-m-arrows-right-left')
                    ->icon(function ($record) {
                        if ($record->task_status == Task::STATUS_CLOSED) {
                            return 'heroicon-m-check-badge';
                        } elseif ($record->task_status == Task::STATUS_REJECTED) {
                            return 'heroicon-m-lock-open';
                        } else {
                            return 'heroicon-m-arrows-right-left';
                        }
                    })
                    // ->color(fn($record): string => ($record->task_status == Task::STATUS_CLOSED || $record->task_status == Task::STATUS_NEW) ? 'gray' : 'success')
                    ->color(function ($record) {
                        // if($record->task_status == Task::STATUS_CLOSED || $record->task_status == Task::STATUS_NEW){
                        if ($record->task_status == Task::STATUS_CLOSED || $record->views == 0) {
                            return 'gray';
                        } else {
                            return 'success';
                        }
                    })
                    ->schema(function () {
                        return [
                            Fieldset::make()->columns(2)->schema([
                                TextInput::make('task_status')->label('From') // current status

                                    ->default(function ($record) {
                                        return $record->task_status;
                                    })
                                    ->disabled(),
                                // Input for next status with placeholder showing allowed statuses
                                TextInput::make('next_status')
                                    ->label('To')
                                    ->default(function ($record) {
                                        return implode(', ', array_keys($record->getNextStatuses()));
                                    })->disabled(),

                            ]),

                        ];
                    })

                    ->databaseTransaction()
                    ->action(function ($record) {

                        // dd($record->steps->update(['done'=>1]));
                        DB::beginTransaction();
                        try {
                            $currentStatus = $record->task_status;
                            $nextStatus = implode(', ', array_keys($record->getNextStatuses()));
                            $record->update(['task_status' => $nextStatus]);
                            if ($nextStatus == Task::STATUS_CLOSED) {
                                $record->steps()->update(['done' => 1]);
                            }
                            $record->createLog(
                                createdBy: auth()->id(), // ID of the user performing the action
                                description: "Task moved to {$nextStatus}", // Log description
                                logType: TaskLog::TYPE_MOVED, // Log type as "moved"
                                details: [
                                    'from' => $currentStatus, // Previous status
                                    'to' => $nextStatus, // New status
                                ]
                            );
                            Notification::make()
                                ->title('Status moved')
                                ->body("Task successfully moved to {$nextStatus}")
                                ->success()
                                ->send();
                            DB::commit();
                        } catch (Throwable $th) {
                            //throw $th;

                            Notification::make()
                                ->title('Error')
                                ->body('There was an error moving the task. Please try again.')
                                ->danger()
                                ->send();
                            DB::rollBack();
                        }
                        // Add a log entry for the "moved" action
                    })
                    ->disabled(function ($record) {
                        // if ($record->task_status == Task::STATUS_CLOSED || $record->task_status == Task::STATUS_NEW) {
                        if (
                            $record->task_status == Task::STATUS_CLOSED || $record->views == 0
                            // || ($record->task_status && !$record->is_all_done)
                            || ($record->task_status == Task::STATUS_REJECTED && $record->rejection_count == setting('task_rejection_times_red_card'))
                        ) {
                            return true;
                        }
                        return false;
                    }),

                Action::make('AddComment')->button()
                    ->hidden(function ($record) {
                        if ($record->is_daily) {
                            return true;
                        }
                        if ($record->task_status == Task::STATUS_CLOSED && auth()->user()?->employee?->id) {
                            return true;
                        }
                        if (in_array($record->task_status, [Task::STATUS_REJECTED, Task::STATUS_CLOSED])) {
                            return true;
                        }
                        if (!isSuperAdmin() && !auth()->user()->can('add_comment_task')) {
                            return true;
                        }
                    })
                    ->schema(function ($record) {
                        return [
                            Fieldset::make()->schema([
                                Textarea::make('comment')->columnSpanFull()->required(),
                            ]),
                        ];
                    })
                    ->icon('heroicon-m-chat-bubble-bottom-center-text')
                    ->color('info')
                    ->action(function (array $data, Task $record): void {
                        // dd($data);
                        $record->comments()->create([
                            'comment' => $data['comment'],
                            'user_id' => auth()->user()->id,
                        ]);
                    }),

                Action::make('Reject')->button()->color(Color::Red)
                    ->icon('heroicon-m-backspace')
                    ->visible(function ($record) {
                        if (
                            $record->task_status == Task::STATUS_CLOSED
                            && ($record->assigned_by == auth()->user()->id || $record->created_by == auth()->user()->id) || isSuperAdmin()
                        ) {
                            return true;
                        }
                        return false;
                    })
                    ->color(Task::COLOR_REJECTED)
                    ->schema(function ($record) {
                        $defaultForm = [
                            Fieldset::make()->visible(fn(): bool => (setting('show_warning_message') && $record->rejection_count == (setting('task_rejection_times_red_card') - 1)))->schema([
                                TextInput::make('message')->label('')
                                    ->extraInputAttributes(['style' => 'font-size:15px;color:red !important;padding:0.75em;font-weight:bold;'])
                                    ->disabled()->columnSpanFull()
                                    ->prefixIcon('heroicon-m-exclamation-circle')
                                    ->inputMode('decimal')
                                    ->prefixIconColor('warning')
                                    ->default('Rejecting this time will result in a penalty being applied to the employee handling it')
                            ]),
                            Fieldset::make()->schema([
                                Textarea::make('reject_reason')
                                    ->label('Reject reason')->required()
                                    ->columnSpanFull(),
                            ]),
                        ];
                        return $defaultForm;;
                    })->modalIcon('heroicon-m-backspace')
                    ->action(function ($record, $data) {
                        DB::beginTransaction();
                        // dd($record->steps);
                        try {
                            $record->update(['task_status' => Task::STATUS_REJECTED]);
                            $record->steps()->update(['done' => 0]);

                            $record->createLog(
                                createdBy: auth()->id(), // ID of the user performing the action
                                description: "Task is rejected", // Log description
                                logType: TaskLog::TYPE_MOVED, // Log type as "moved"
                                details: [
                                    'reject_reason' => $data['reject_reason'], // Previous status

                                ]
                            );
                            $record->createLog(
                                createdBy: auth()->id(), // ID of the user performing the action
                                description: "Task is rejected", // Log description
                                logType: TaskLog::TYPE_REJECTED, // Log type as "moved"
                                details: [
                                    'reject_reason' => $data['reject_reason'], // Previous status

                                ]
                            );
                            if ($record->rejection_count == setting('task_rejection_times_yello_card')) {
                                $record->taskCards()->create([
                                    'type' => TaskCard::TYPE_YELLOW,
                                    'employee_id' => $record->assigned_to,
                                    'active' => true,
                                ]);
                            }
                            if ($record->rejection_count == setting('task_rejection_times_red_card')) {
                                $record->taskCards()->create([
                                    'type' => TaskCard::TYPE_RED,
                                    'employee_id' => $record->assigned_to,
                                    'active' => true,
                                ]);
                            }
                            Notification::make()->title('Rejected')->send();
                            DB::commit();
                        } catch (Throwable $th) {
                            //throw $th;
                            DB::rollBack();
                            Notification::make()->title('Error')->warning()->body($th->getMessage())->send();
                        }
                    })->hidden(fn($record): bool => $record->task_status == Task::STATUS_REJECTED),
                // ReplicateAction::make(),
                // ActionGroup::make([
                //     Tables\Actions\EditAction::make(),
                //     Tables\Actions\ViewAction::make(),
                // ])->iconButton(),

            ], position: RecordActionsPosition::AfterColumns)
            ->toolbarActions([
                // Tables\Actions\BulkActionGroup::make([

                ForceDeleteBulkAction::make(),
                DeleteBulkAction::make()
                    ->hidden(fn(): bool => (isStuff() || isBranchManager())),
                // ]),
            ])
            ->emptyStateActions([
                CreateAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            StepsRelationManager::class,
            CommentsRelationManager::class,
            LogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTasks::route('/'),
            'create' => CreateTask::route('/create'),
            'edit' => EditTask::route('/{record}/edit'),
            'task_steps' => TaskStepsPage::route('/{record}/task_steps'),
        ];
    }
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('is_daily', 0)
            ->forBranchManager()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
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
    //         // $query->where('assigned_to', auth()->user()->id)
    //         //     ->orWhere('assigned_to', auth()->user()?->employee?->id)
    //         //         ->orWhere('created_by', auth()->user()->id)
    //         $query->Where('created_by', auth()->user()->id)->orWhere('assigned_to', auth()->user()?->employee?->id);
    //         // $query->Where('assigned_to', auth()->user()->id)
    //         // $query->Where('created_by', auth()->user()->id)
    //         ;
    //     }
    //     return $query;
    // }

    public static function canCreate(): bool
    {
        // if (isSuperAdmin() || auth()->user()->can('create_task')) {
        if (isSuperAdmin() || isBranchManager() || isSystemManager()) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (
            isSuperAdmin() || isSystemManager() || (isBranchManager() && ($record->assigned_by == auth()?->user()?->id || $record->assigned_to == auth()?->user()?->employee?->id))
            // (isSystemManager() && $record->assigned_by == auth()?->user()?->id)
            || isStuff() || isFinanceManager()
        ) {
            return true;
        }
        return false;
    }
    public static function getRequrPatternKeysAndValues(array $data)
    {
        // Use array_filter to get the keys starting with 'requr_pattern_'
        $filteredData = array_filter($data, function ($value, $key) {
            return Str::startsWith($key, 'requr_pattern_');
        }, ARRAY_FILTER_USE_BOTH);

        return $filteredData;
    }

    public static function getLabel(): ?string
    {
        // if (!in_array(getCurrentRole(), [1, 3])) {
        if (isStuff()) {
            return 'My Tasks';
        }
        return static::$label;
    }

    public static function canViewAny(): bool
    {
        if (isSuperAdmin() || isSystemManager() || isBranchManager() || isStuff() || isFinanceManager()) {
            return true;
        }
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (isSuperAdmin() || isSystemManager()) {
            return true;
        }
        return false;
    }

    public static function canDeleteAny(): bool
    {
        if (isSuperAdmin() || isSystemManager()) {
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

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ListTasks::class,
            Pages\CreateTask::class,
            EditTask::class,
        ]);
    }
}
