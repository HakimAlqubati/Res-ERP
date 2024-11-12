<?php

namespace App\Filament\Clusters\HrServiceRequestCluster\Resources;

use App\Filament\Clusters\HRServiceRequestCluster;
use App\Filament\Clusters\HRServiceRequestCluster\Resources\ServiceRequestResource\Pages;
use App\Filament\Clusters\HRServiceRequestCluster\Resources\ServiceRequestResource\RelationManagers\CommentsRelationManager;
use App\Filament\Clusters\HRServiceRequestCluster\Resources\ServiceRequestResource\RelationManagers\LogsRelationManager;
use App\Models\Branch;
use App\Models\BranchArea;
use App\Models\Employee;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestLog;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextColumn\TextColumnSize;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ServiceRequestResource extends Resource
{
    protected static ?string $model = ServiceRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = HRServiceRequestCluster::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {

        return $form
            ->schema([
                Fieldset::make()->schema([

                    Fieldset::make()->columns(3)->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->disabled(condition: function ($record) {
                                if(isset($record)){
                                    if ($record->created_by == auth()->user()->id) {
                                        return false;
                                    }
                                    return true;
                                }
                        }),
                        Select::make('branch_id')->label('Branch')
                        ->disabled(function ($record) {
                            if(isset($record)){
                                if ($record->created_by == auth()->user()->id) {
                                    return false;
                                }
                                return true;
                            }
                        })
                            ->options(Branch::select('name', 'id')->pluck('name', 'id'))
                            ->default(function () {
                                if (isStuff()) {
                                    return auth()->user()->branch_id;
                                }
                            })
                            ->live()
                            ->required(),

                        Select::make('branch_area_id')->label('Branch area')->required()
                            ->options(function (Get $get) {
                                return BranchArea::query()
                                    ->where('branch_id', $get('branch_id'))
                                    ->pluck('name', 'id');
                            })
                            ->disabled(function ($record) {
                                if(isset($record)){
                                    if ($record->created_by == auth()->user()->id) {
                                        return false;
                                    }
                                    return true;
                                }
                            })
                        ,
                    ]),
                    Fieldset::make()->label('Descripe your service request')->schema([
                        Textarea::make('description')->label('')->required()
                            ->helperText('Description of service request')
                            ->columnSpanFull()
                            ->maxLength(500),

                    ])
                    ->disabled(function ($record) {
                        if(isset($record)){
                            if ($record->created_by == auth()->user()->id) {
                                return false;
                            }
                            return true;
                        }
                    })                    ,

                    Fieldset::make()->columns(4)->schema([
                        Select::make('assigned_to')
                            ->options(fn(Get $get): Collection => Employee::query()
                                    ->where('active', 1)
                                    ->where('branch_id', $get('branch_id'))
                                    ->pluck('name', 'id'))
                            ->searchable()
                            ->disabledOn('edit')
                            ->helperText(function (Model $record = null) {
                                if ($record) {
                                    return 'To reassign, go to table page ';
                                }
                            })
                            ->nullable(),
                        Select::make('urgency')
                            ->options([
                                ServiceRequest::URGENCY_HIGH => 'High',
                                ServiceRequest::URGENCY_MEDIUM => 'Medium',
                                ServiceRequest::URGENCY_LOW => 'Low',
                            ])
                            ->disabled(function () {
                                if(isset($record)){
                                    if ($record->created_by == auth()->user()->id) {
                                        return false;
                                    }
                                    return true;
                                }
                            })
                            ->required(),

                        Select::make('impact')
                            ->options([
                                ServiceRequest::IMPACT_HIGH => 'High',
                                ServiceRequest::IMPACT_MEDIUM => 'Medium',
                                ServiceRequest::IMPACT_LOW => 'Low',
                            ])
                            ->disabled(function () {
                                if(isset($record)){
                                    if ($record->created_by == auth()->user()->id) {
                                        return false;
                                    }
                                    return true;
                                }
                            })
                            ->required(),
                        Select::make('status')
                            ->default(ServiceRequest::STATUS_NEW)
                            ->options([
                                ServiceRequest::STATUS_NEW => 'New',
                                ServiceRequest::STATUS_PENDING => 'Pending',
                                ServiceRequest::STATUS_IN_PROGRESS => 'In progress',
                                ServiceRequest::STATUS_CLOSED => 'Closed',
                            ])->disabled()
                            ->helperText(function (Model $record = null) {
                                if ($record) {
                                    return 'To change status, go to table page ';
                                }
                            })
                            ->required(),
                    ]),
                    Fieldset::make()->label('ِAdd photos')->schema([

                        FileUpload::make('file_path')
                            ->label('')
                            ->disk('public')
                            ->directory('service_requests')
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
                        // ->hiddenOn('create')
                            ->previewable()
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                return (string) str($file->getClientOriginalName())->prepend('service-request-');
                            }),

                    ]),
                ]),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table->defaultSort('id','desc')
        ->paginated([10, 25, 50, 100])
            ->columns([
                TextColumn::make('id')->sortable()->searchable(isIndividual: true)->sortable(),
                TextColumn::make('name')->searchable(isIndividual: true)->sortable()
                    ->color(Color::Blue)
                    ->size(TextColumnSize::Large)
                    ->weight(FontWeight::ExtraBold)
                    ->description('Click')
                    ->searchable()

                ,
                TextColumn::make('status')
                    ->badge()
                    ->sortable()
                    ->searchable()
                    ->icon('heroicon-m-check-badge')
                    ->searchable(isIndividual: false)
                    ->colors([
                        'primary' => ServiceRequest::STATUS_NEW,
                        'warning' => ServiceRequest::STATUS_PENDING,
                        'info' => ServiceRequest::STATUS_IN_PROGRESS,
                        'success' => ServiceRequest::STATUS_CLOSED,
                    ]),

                TextColumn::make('urgency')
                    ->badge()
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-check-badge')
                    ->colors([
                        'danger' => ServiceRequest::URGENCY_HIGH,
                        'warning' => ServiceRequest::URGENCY_MEDIUM,
                        'success' => ServiceRequest::URGENCY_LOW,
                    ])
                    ->toggleable(isToggledHiddenByDefault: false)
                ,

                Tables\Columns\ImageColumn::make('first_photo_url')
                ->label('Photo')
                ->width(50)
                ->height(50)->disabledClick(true)
                ->toggleable(isToggledHiddenByDefault: false)
                // ->extraAttributes(['class' => 'cursor-pointer']) // Make it look clickable
                ->getStateUsing(fn($record) => $record->photos()->first()?->image_path) // Assuming 'first_photo_url' is derived from the first image
                // ->action(function ($record) {
                    
                //     // Define the action to open the modal
                //     return Action::make('viewImage')
                //         ->modalHeading('Service Request Photos')
                //         ->modalWidth('lg')
                //         ->modalContent(function () use ($record) {
                //             dd($record->photos());
                //             // Return a view that shows all images
                //             return view('filament.resources.service_requests.gallery', [
                //                 'photos' => $record->photos()->orderBy('id', 'desc')->get(),
                //             ]);
                //         });
                // })
                ,
                TextColumn::make('impact')
                    ->badge()
                    ->icon('heroicon-m-check-badge')
                    ->searchable()
                    ->sortable()
                    ->colors([
                        'danger' => ServiceRequest::IMPACT_HIGH,
                        'warning' => ServiceRequest::IMPACT_MEDIUM,
                        'success' => ServiceRequest::IMPACT_LOW,
                    ])
                    ->toggleable(isToggledHiddenByDefault: false)
                ,

                TextColumn::make('branch.name')->label('Branch')->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                ,
                TextColumn::make('branchArea.name')->label('Branch Area')
                    ->toggleable(isToggledHiddenByDefault: true)
                ,
                TextColumn::make('createdBy.name')->label('Created By')->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                ,
                TextColumn::make('assignedTo.name')->label('Assigned To')->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                ,
                TextColumn::make('created_at')->label('Created At')->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                ,
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('Move')->button()
                ->disabled(function($record){
                    if($record->status == ServiceRequest::STATUS_CLOSED){
                        return true;
                    }
                    return false;
                })
                // ->hidden(function ($record) {
                //     if (!isSuperAdmin() && !auth()->user()->can('move_status_task')) {
                //         return true;
                //     }
                // })
                    ->form(function ($record) {
                        return [
                            Select::make('status')->default(function ($record) {
                                return $record->status;
                            })->columnSpanFull()
                                ->disableOptionWhen(fn(string $value): bool => ($value === ServiceRequest::STATUS_NEW ))
                                
                                ->disabled(function ($record) {
                                    if($record->status == ServiceRequest::STATUS_NEW){
                                        if($record->created_by == auth()->user()->id){
                                            return false;
                                        }
                                    }
                                    if(isset(auth()->user()?->employee)){
                                        if($record->assigned_to == auth()->user()?->employee?->id){
                                            return false;
                                        }
                                    }
                                    // if($record->status == S)
                                    return true;
                                    // Get the logs for the status change
                                    $statusChangeLogs = $record->logs->where('log_type', ServiceRequestLog::LOG_TYPE_STATUS_CHANGED);

                                    // Check if there are any logs and get the last one
                                    if ($statusChangeLogs->isNotEmpty()) {
                                        $lastLog = $statusChangeLogs->last();

                                        // Check if the last log's created_by matches the current user
                                        if($record->status == ServiceRequest::STATUS_PENDING){
                                            return $lastLog->created_by == auth()->user()->id;
                                        }
                                    }

                                    // If there are no logs, do not disable the field
                                    return false;

                                })
                                ->options(
                                    [
                                        ServiceRequest::STATUS_NEW => 'New',
                                        ServiceRequest::STATUS_PENDING => 'Pending',
                                        ServiceRequest::STATUS_IN_PROGRESS => 'In progress',
                                        ServiceRequest::STATUS_CLOSED => 'Closed',
                                    ]
                                ),
                        ];
                    })
                    ->icon('heroicon-m-arrows-right-left')
                    ->color('success')
                    ->action(function (array $data, $record): void {
                        // dd($data);
                        $prevStatus = $record->status;
                        $move = $record->update([
                            'status' => $data['status'],
                        ]);
                        if ($move) {
                            $record->logs()->create([
                                'created_by' => auth()->user()->id,
                                'description' => 'status changed from ' . $prevStatus . ' to ' . $record->status,
                                'log_type' => ServiceRequestLog::LOG_TYPE_STATUS_CHANGED,
                            ]);
                        }
                    }),
                Action::make('ReAssign')
                ->disabled(function($record){
                    if($record->status == ServiceRequest::STATUS_CLOSED){
                        return true;
                    }
                    return false;
                })
                ->button()
                    ->hidden(function () {
                        if (isStuff()) {
                            return true;
                        }
                        return false;
                    })
                    ->form(function ($record) {
                        return [
                            Fieldset::make()->schema([
                                Select::make('assigned_to')->label('')->columnSpanFull()
                                    ->options(Employee::query()
                                            ->where('active', 1)
                                            ->where('branch_id', $record->branch_id)
                                            ->pluck('name', 'id'))
                                    ->searchable()
                                    ->nullable(),
                            ]),
                        ];
                    })
                    ->icon('heroicon-m-arrows-right-left')
                    ->color('info')
                    ->action(function (array $data, $record): void {

                        $prevAssigned = null;
                        if (!is_null($record?->assigned_to)) {
                            $prevAssigned = $record?->assignedTo?->name;
                        }
                        $newAssigned = Employee::find($data['assigned_to'])?->name;
                        $reassign = $record->update([
                            'assigned_to' => $data['assigned_to'],
                        ]);

                        if ($reassign) {
                            $description = 'Assigned to ' . $newAssigned;
                            if (!is_null($prevAssigned)) {
                                $description = 'Reassigned from ' . $prevAssigned . ' to ' . $newAssigned;
                            }
                            $record->logs()->create([
                                'created_by' => auth()->user()->id,
                                'description' => $description,
                                'log_type' => ServiceRequestLog::LOG_TYPE_REASSIGN_TO_USER,
                            ]);
                        }
                    }),
                Action::make('AddComment')->button()->disabled(function($record){
                    if($record->status == ServiceRequest::STATUS_CLOSED){
                        return true;
                    }
                    return false;
                })
                    ->form(function ($record) {
                        return [
                            Fieldset::make()->schema([
                                Textarea::make('comment')->columnSpanFull()->required(),
                                FileUpload::make('image_path')
                                    ->disk('public')
                                    ->label('')
                                    ->directory('service_comments')
                                    ->columnSpanFull()
                                    ->image()
                                    ->multiple()
                                    ->resize(5)
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
                                        return (string) str($file->getClientOriginalName())->prepend('comment-');
                                    })
                                ,

                            ]),
                        ];
                    })
                    ->icon('heroicon-m-chat-bubble-bottom-center-text')
                    ->color('info')
                    ->action(function (array $data, $record): void {
                        // dd($data);
                        $comment = $record->comments()->create([
                            'comment' => $data['comment'],
                            'created_by' => auth()->user()->id,
                        ]);

                        if ($comment) {
                            $record->logs()->create([
                                'created_by' => auth()->user()->id,
                                'description' => 'Comment added: ' . $data['comment'],
                                'log_type' => ServiceRequestLog::LOG_TYPE_COMMENT_ADDED,
                            ]);
                        }
                        // If there are photos, save them after the comment is created
                        if (isset($data['image_path']) && is_array($data['image_path']) && count($data['image_path']) > 0) {
                            foreach ($data['image_path'] as $file) {
                                $comment->photos()->create([
                                    'image_name' => $file,
                                    'image_path' => $file,
                                    'created_by' => auth()->user()->id,
                                ]);
                            }
                        }
                    }),
                Action::make('AddPhotos')->disabled(function($record){
                    if($record->status == ServiceRequest::STATUS_CLOSED){
                        return true;
                    }
                    return false;
                })
                // ->hidden(function ($record) {
                //     if ($record->task_status == Task::STATUS_COMPLETED) {
                //         return true;
                //     }
                //     if (!isSuperAdmin() && !auth()->user()->can('add_photo_task')) {
                //         return true;
                //     }
                // })
                    ->form([

                        FileUpload::make('image_path')
                            ->disk('public')
                            ->label('')
                            ->directory('service_requests')
                            ->columnSpanFull()
                            ->image()
                            ->multiple()
                            ->resize(5)
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
                                return (string) str($file->getClientOriginalName())->prepend('service-request-');
                            }),
                    ])
                    ->action(function (array $data, $record): void {
                        $serviceRequest = $record;
                        if (isset($data['image_path']) && is_array($data['image_path']) && count($data['image_path']) > 0) {
                            foreach ($data['image_path'] as $file) {
                                $serviceRequest->photos()->create([
                                    'image_name' => $file,
                                    'image_path' => $file,
                                    'created_by' => auth()->user()->id,
                                ]);
                            }
                            $record->logs()->create([
                                'created_by' => auth()->user()->id,
                                'description' => 'Images added',
                                'log_type' => ServiceRequestLog::LOG_TYPE_IMAGES_ADDED,
                            ]);
                        }
                    })
                    ->button()
                    ->icon('heroicon-m-newspaper')
                    ->color('success'),
                Action::make('viewGallery')
                    ->hidden(function ($record) {
                        return $record->photos_count <= 0 ? true : false;
                    })
                    ->label('Browse photos')
                    ->label(function ($record) {
                        return $record->photos_count;
                    })
                    ->modalHeading('Request service photos')
                    ->modalWidth('lg') // Adjust modal size
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                // ->iconButton()
                    ->button()
                    ->icon('heroicon-o-camera')
                    ->modalContent(function ($record) {
                        return view('filament.resources.service_requests.gallery', ['photos' => $record->photos()->orderBy('id', 'desc')->get()]);
                    }),
                Tables\Actions\EditAction::make()->disabled(function($record){
                    if($record->status == ServiceRequest::STATUS_CLOSED){
                        return true;
                    }
                    return false;
                }),
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
            CommentsRelationManager::class,
            LogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServiceRequests::route('/'),
            'create' => Pages\CreateServiceRequest::route('/create'),
            'edit' => Pages\EditServiceRequest::route('/{record}/edit'),
            'view' => Pages\ViewServiceRequest::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        if (auth()->user()->is_branch_manager) {
            return static::getModel()::where('branch_id', auth()->user()->branch->id)->count();
        }
        return static::getModel()::count();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = static::getModel();
        if (auth()->user()->is_branch_manager) {
            // $query = $query::where('branch_id', auth()->user()->branch->id);
            return $query::query()->where('branch_id', auth()->user()->branch->id);
        }
        return $query::query();

    }

    public static function canDelete(Model $record): bool
    {
        if (isMaintenanceManager() || isSystemManager() || isSuperAdmin() || isBranchManager()) {
            return true;
        }
        return false;
        return static::can('delete', $record);
    }

    public static function canCreate(): bool
    {
        // if (isSuperAdmin() || auth()->user()->can('create_task')) {
        if (isFinanceManager()) {
            return false;
        }

        return true;
    }
}
