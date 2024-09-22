<?php

namespace App\Filament\Clusters\HrServiceRequestCluster\Resources;

use App\Filament\Clusters\HRServiceRequestCluster;
use App\Filament\Clusters\HRServiceRequestCluster\Resources\ServiceRequestResource\Pages;
use App\Filament\Clusters\HRServiceRequestCluster\Resources\ServiceRequestResource\RelationManagers\CommentsRelationManager;
use App\Models\Branch;
use App\Models\BranchArea;
use App\Models\Employee;
use App\Models\ServiceRequest;
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
use Illuminate\Database\Eloquent\Relations\HasMany;
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
                            ->maxLength(255),
                        Select::make('branch_id')->label('Branch')
                            ->options(Branch::query()->where('active', 1)
                                    ->select('id', 'name')->pluck('name', 'id'))
                            ->live()
                            ->required(),

                        Select::make('branch_area_id')->label('Branch area')->required()
                            ->options(fn(Get $get): Collection => BranchArea::query()
                                    ->where('branch_id', $get('branch_id'))
                                    ->pluck('name', 'id')),
                    ]),
                    Fieldset::make()->label('Descripe your service request')->schema([
                        Textarea::make('description')->label('')->required()
                            ->helperText('Description of service request')
                            ->columnSpanFull()
                            ->maxLength(500),

                    ]),

                    Fieldset::make()->columns(4)->schema([
                        Select::make('assigned_to')
                            ->options(fn(Get $get): Collection => Employee::query()
                                    ->where('active', 1)
                                    ->where('branch_id', $get('branch_id'))
                                    ->pluck('name', 'id'))
                            ->searchable()
                            ->nullable(),
                        Select::make('urgency')
                            ->options([
                                ServiceRequest::URGENCY_HIGH => 'High',
                                ServiceRequest::URGENCY_MEDIUM => 'Medium',
                                ServiceRequest::URGENCY_LOW => 'Low',
                            ])
                            ->required(),

                        Select::make('impact')
                            ->options([
                                ServiceRequest::IMPACT_HIGH => 'High',
                                ServiceRequest::IMPACT_MEDIUM => 'Medium',
                                ServiceRequest::IMPACT_LOW => 'Low',
                            ])
                            ->required(),
                        Select::make('status')
                            ->default(ServiceRequest::STATUS_NEW)
                            ->options([
                                ServiceRequest::STATUS_NEW => 'New',
                                ServiceRequest::STATUS_PENDING => 'Pending',
                                ServiceRequest::STATUS_IN_PROGRESS => 'In progress',
                                ServiceRequest::STATUS_CLOSED => 'Closed',
                            ])->disabled()
                            ->required(),
                    ]),

                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
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
                // ->hidden(function ($record) {
                //     if (!isSuperAdmin() && !auth()->user()->can('move_status_task')) {
                //         return true;
                //     }
                // })
                    ->form(function ($record) {
                        return [
                            Select::make('status')->default(function ($record) {
                                return $record->status;
                            })->columnSpanFull()->options(
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
                        $record->update([
                            'status' => $data['status'],
                        ]);
                    }),
                Action::make('AddComment')->button()
                // ->hidden(function ($record) {
                //     if (!isSuperAdmin() && !auth()->user()->can('add_comment_task')) {
                //         return true;
                //     }
                // })
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
                Action::make('AddPhotos')
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
                        return view('filament.resources.service_requests.gallery', ['photos' => $record->photos]);
                    }),
                Tables\Actions\EditAction::make(),
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
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServiceRequests::route('/'),
            'create' => Pages\CreateServiceRequest::route('/create'),
            'edit' => Pages\EditServiceRequest::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}