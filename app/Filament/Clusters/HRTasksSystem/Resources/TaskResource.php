<?php

namespace App\Filament\Clusters\HRTasksSystem\Resources;

use Illuminate\Support\Str;
use App\Filament\Clusters\HRTasksSystem;
use App\Filament\Clusters\HRTasksSystem\Resources\TaskResource\Pages;
use App\Filament\Clusters\HRTasksSystem\Resources\TaskResource\RelationManagers\StepsRelationManager;
use App\Filament\Clusters\HRTasksSystem\Resources\TaskResource\RelationManagers\TaskMenuRelationManager;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TasksMenu;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Mokhosh\FilamentRating\Components\Rating;
use Mokhosh\FilamentRating\RatingTheme;

use function Laravel\Prompts\form;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = HRTasksSystem::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Grid::make()->columns(3)->schema([
                    Forms\Components\TextInput::make('title')
                        ->required()
                        ->autofocus()
                        ->columnSpan(1)
                        ->maxLength(255),
                    Forms\Components\Select::make('assigned_by')
                        ->label('Assign by')
                        ->required()
                        ->default(auth()->user()->id)
                        ->columnSpan(1)
                        ->options(User::select('name', 'id')->get()->pluck('name', 'id'))->searchable()
                        ->selectablePlaceholder(false),
                    Forms\Components\Select::make('assigned_to')
                        ->label('Assign to')
                        ->required()
                        ->columnSpan(1)
                        ->options(User::select('name', 'id')->get()->pluck('name', 'id'))->searchable()
                        ->selectablePlaceholder(false),

                ]),

                Forms\Components\Textarea::make('description')
                    ->required()
                    ->maxLength(65535)
                    ->columnSpanFull(),

                DatePicker::make('due_date')->label('Due date')->required(false),
                Select::make('task_status')->options(
                    [
                        Task::STATUS_PENDING => Task::STATUS_PENDING,
                        Task::STATUS_IN_PROGRESS => Task::STATUS_IN_PROGRESS,
                        Task::STATUS_REVIEW => Task::STATUS_REVIEW,
                        Task::STATUS_CANCELLED => Task::STATUS_CANCELLED,
                        Task::STATUS_FAILED => Task::STATUS_FAILED,
                        Task::STATUS_COMPLETED => Task::STATUS_COMPLETED,
                    ]
                )->default(Task::STATUS_PENDING)
                    ->disabledOn('create'),
                // CheckboxList::make('menu_tasks')->nullable()->searchable()->options(
                //     TasksMenu::where('active', 1)->select('name', 'id')->get()->pluck('name', 'id')
                // ),
                Hidden::make('created_by')->default(auth()->user()->id),
                Hidden::make('updated_by')->default(auth()->user()->id),



                Rating::make('rating')->hidden()->stars(10)->theme(RatingTheme::HalfStars)->helperText('Rate this from 0 to 10')
                    ->default(0)
                    ->live()
                    ->afterStateUpdated(function (?string $state, ?string $old, $component) {
                        $component->helperText("Your rating: $state/10");
                    })
                    ->hiddenOn('create'),


                FileUpload::make('file_path')
                    ->label('Add photos')
                    ->disk('public')
                    ->directory('tasks')
                    ->visibility('public')
                    ->columnSpanFull()
                    ->imagePreviewHeight('250')
                    ->image()
                    ->resize(5)
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

                    ->previewable()
                    ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                        return (string) str($file->getClientOriginalName())->prepend('task-');
                    }),
                // Hidden::make('file_name')->default('test'),
                // Hidden::make('file_name')->default('test'),

                Hidden::make('created_by')->default(auth()->user()->id),
                Hidden::make('updated_by')->default(auth()->user()->id),


                Repeater::make('steps')
                    ->itemLabel('Steps')
                    ->columnSpanFull()
                    ->relationship('steps')
                    ->columns(1)
                    ->hiddenOn('edit')
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->live(onBlur: true),
                    ])
                    ->collapseAllAction(
                        fn(\Filament\Forms\Components\Actions\Action $action) => $action->label('Collapse all steps'),
                    )
                    ->orderColumn('order')
                    ->reorderable()
                    ->reorderableWithDragAndDrop()
                    ->reorderableWithButtons()
                    ->cloneable()
                    ->collapsible()
                    ->itemLabel(fn(array $state): ?string => $state['name'] ?? null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id'),

                Tables\Columns\TextColumn::make('title')
                    ->description(fn(Task $record): string => $record->description)
                    ->searchable(),
                TextColumn::make('task_status')->label('Status')
                    ->badge()
                    ->icon('heroicon-m-check-badge')
                    ->color(fn(string $state): string => match ($state) {
                        Task::STATUS_PENDING => Task::COLOR_PENDING,
                        Task::STATUS_IN_PROGRESS => Task::COLOR_IN_PROGRESS,
                        Task::STATUS_REVIEW => Task::COLOR_REVIEW,
                        Task::STATUS_CANCELLED => Task::COLOR_CANCELLED,
                        Task::STATUS_FAILED => Task::COLOR_FAILED,
                        Task::STATUS_COMPLETED => Task::COLOR_COMPLETED,
                        // default => 'gray', // Fallback color in case of unknown status
                    }),

                // ColumnGroup::make('Visibility', [
                //     TextColumn::make('task_status'),
                // ]),


                Tables\Columns\TextColumn::make('assigned.name')
                    ->label('Assigned To')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('createdby.name')
                    ->label('Assigned By')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('photos_count'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('task_status')->label('Status')->multiple()->options(
                    [
                        Task::STATUS_PENDING => Task::STATUS_PENDING,
                        Task::STATUS_IN_PROGRESS => Task::STATUS_IN_PROGRESS,
                        Task::STATUS_REVIEW => Task::STATUS_REVIEW,
                        Task::STATUS_CANCELLED => Task::STATUS_CANCELLED,
                        Task::STATUS_FAILED => Task::STATUS_FAILED,
                        Task::STATUS_COMPLETED => Task::STATUS_COMPLETED,
                    ]
                )
            ])
            ->selectable()
            ->actions([

                Action::make('AddPhotos')
                    // ->hidden()
                    ->form([
                        FileUpload::make('file_path')
                            ->label('')
                            ->columnSpanFull()
                            ->default(function ($record) {
                                return storage_path($record->photos[0]->file_name);
                            })
                            ->disk('public')
                            ->directory('tasks')
                            ->image()
                            ->resize(5)
                            ->imagePreviewHeight('250')
                            ->loadingIndicatorPosition('left')
                            ->panelLayout('integrated')
                            ->removeUploadedFileButtonPosition('right')
                            ->uploadButtonPosition('left')
                            ->uploadProgressIndicatorPosition('left')
                            ->multiple()
                            ->panelLayout('grid')
                            ->reorderable()
                            ->openable()
                            ->downloadable()
                            ->previewable()
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                return (string) str($file->getClientOriginalName())->prepend('task-');
                            })
                    ])
                    ->action(function (array $data, Task $record): void {
                        if (isset($data['file_path']) && is_array($data['file_path']) && count($data['file_path']) > 0) {
                            foreach ($data['file_path'] as  $file) {
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

                Action::make('MoveTask')->button()
                    // ->badge()
                    ->form(function ($record) {
                        return [
                            Select::make('task_status')->default(function ($record) {
                                return $record->task_status;
                            })->columnSpanFull()->options(
                                [
                                    Task::STATUS_PENDING => Task::STATUS_PENDING,
                                    Task::STATUS_IN_PROGRESS => Task::STATUS_IN_PROGRESS,
                                    Task::STATUS_REVIEW => Task::STATUS_REVIEW,
                                    Task::STATUS_CANCELLED => Task::STATUS_CANCELLED,
                                    Task::STATUS_FAILED => Task::STATUS_FAILED,
                                    Task::STATUS_COMPLETED => Task::STATUS_COMPLETED,
                                ]
                            )
                        ];
                    })
                    ->icon('heroicon-m-arrows-right-left')
                    ->color('success')
                    ->action(function (array $data, Task $record): void {
                        // dd($data);
                        $record->update([
                            'task_status' => $data['task_status']
                        ]);
                    }),
                Action::make('AddComment')->button()
                    // ->badge()
                    ->form(function ($record) {
                        return [
                            Fieldset::make()->schema([
                                Textarea::make('comment')->columnSpanFull()->required(),
                            ])
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
                Action::make('Rating')
                    ->button()
                    ->hidden(function ($record) {
                        if (!in_array(getCurrentRole(), [1, 2])) {
                            return true;
                        }

                        // Check if the task status is not 'completed'
                        if ($record->task_status !== Task::STATUS_COMPLETED) {
                            return true;
                        }
                        return false;
                    })
                    ->fillForm(
                        fn(Task $record): array => [
                            'task_employee' => $record->assigned->name,
                        ]
                    )
                    ->form(function ($record) {
                        // dd($record->assigned->name);
                        return [
                            TextInput::make('task_employee')->disabled()->columnSpanFull(),
                            Fieldset::make('task_rating')->relationship('task_rating')->label('')->schema([
                                Rating::make('rating_value')
                                ->theme(RatingTheme::HalfStars)
                                ->label('')->theme(RatingTheme::Simple)->stars(10)->size('lg')
                                    ->helperText(function ($record) { 
                                     
                                        if (is_null($record?->rating_value)) { 
                                            return 'Rate this from 0 to 10';
                                        } else {
                                            return "Your rating:" . $record->rating_value . "/10";
                                        }
                                    })
                                    ->live()
                                    ->afterStateUpdated(function (?string $state, ?string $old, $component) {
                                        $component->helperText("Your rating: $state/10");
                                    }),
                                // TextInput::make('user')->default($record->assigned->name)->disabled()->label('Task employee'),
                                Hidden::make('task_user_id_assigned')->default($record->assigned_to),
                                Textarea::make('comment')->columnSpanFull(),
                                Hidden::make('created_by')->default(auth()->user()->id),
                                // Hidden::
                            ]),
                        ];
                    })

                    ->tooltip('Rating the Task for 10 Stars')
                    ->icon('heroicon-m-star')
                    ->color('info'),
                ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\ViewAction::make(),
                ])->iconButton(),
            ], position: ActionsPosition::AfterColumns)
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            StepsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'edit' => Pages\EditTask::route('/{record}/edit'),
            'task_steps' => Pages\TaskStepsPage::route('/{record}/task_steps'),
        ];
    }
    public static function getEloquentQuery(): Builder
    {
        $query = static::getModel()::query();

        if (
            static::isScopedToTenant() &&
            ($tenant = Filament::getTenant())
        ) {
            static::scopeEloquentQueryToTenant($query, $tenant);
        }
        if (!in_array(getCurrentRole(), [1, 2])) {
            $query->where('assigned_to', auth()->user()->id)
                ->orWhere('created_by', auth()->user()->id)
            ;
        }
        return $query;
    }
}
