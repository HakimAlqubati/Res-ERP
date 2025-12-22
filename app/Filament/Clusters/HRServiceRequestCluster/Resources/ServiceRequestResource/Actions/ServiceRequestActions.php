<?php

namespace App\Filament\Clusters\HRServiceRequestCluster\Resources\ServiceRequestResource\Actions;

use App\Models\Employee;
use App\Models\EquipmentLog;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestLog;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Fieldset;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ServiceRequestActions
{
    /**
     * Get all record actions - جميع إجراءات السجل
     */
    public static function getRecordActions(): array
    {
        return [
            ActionGroup::make([
                static::getMoveStatusAction(),
                static::getReAssignAction(),
                static::getAddCommentAction(),
                static::getAddPhotosAction(),
                static::getViewGalleryAction(),
                EditAction::make()->disabled(function ($record) {
                    if ($record->status == ServiceRequest::STATUS_CLOSED) {
                        return true;
                    }
                    return false;
                }),
            ]),
        ];
    }

    /**
     * Move status action - إجراء تغيير الحالة
     */
    public static function getMoveStatusAction(): Action
    {
        return Action::make('Move')->button()
            ->disabled(function ($record) {
                if ($record->status == ServiceRequest::STATUS_CLOSED) {
                    return true;
                }
                return false;
            })
            ->schema(function ($record) {
                return [
                    Select::make('status')->default(function ($record) {
                        return $record->status;
                    })
                        ->columnSpanFull()
                        ->options([
                            ServiceRequest::STATUS_NEW         => 'New',
                            ServiceRequest::STATUS_PENDING     => 'Pending',
                            ServiceRequest::STATUS_IN_PROGRESS => 'In progress',
                            ServiceRequest::STATUS_CLOSED      => 'Closed',
                        ]),
                ];
            })
            ->icon('heroicon-m-arrows-right-left')
            ->color('success')
            ->action(function (array $data, $record): void {
                $prevStatus = $record->status;
                $move       = $record->update([
                    'status' => $data['status'],
                ]);
                if ($move) {
                    $record->logs()->create([
                        'created_by'  => auth()->user()->id,
                        'description' => 'status changed from ' . $prevStatus . ' to ' . $record->status,
                        'log_type'    => ServiceRequestLog::LOG_TYPE_STATUS_CHANGED,
                    ]);
                }
            });
    }

    /**
     * ReAssign action - إجراء إعادة التعيين
     */
    public static function getReAssignAction(): Action
    {
        return Action::make('ReAssign')
            ->disabled(function ($record) {
                if ($record->status == ServiceRequest::STATUS_CLOSED) {
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
            ->schema(function ($record) {
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
                if (! is_null($record?->assigned_to)) {
                    $prevAssigned = $record?->assignedTo?->name;
                }
                $newAssigned = Employee::find($data['assigned_to'])?->name;
                $reassign    = $record->update([
                    'assigned_to' => $data['assigned_to'],
                ]);

                if ($reassign) {
                    $description = 'Assigned to ' . $newAssigned;
                    if (! is_null($prevAssigned)) {
                        $description = 'Reassigned from ' . $prevAssigned . ' to ' . $newAssigned;
                    }
                    $record->logs()->create([
                        'created_by'  => auth()->user()->id,
                        'description' => $description,
                        'log_type'    => ServiceRequestLog::LOG_TYPE_REASSIGN_TO_USER,
                    ]);
                }
            });
    }

    /**
     * Add comment action - إجراء إضافة تعليق
     */
    public static function getAddCommentAction(): Action
    {
        return Action::make('AddComment')->button()
            ->disabled(function ($record) {
                if ($record->status == ServiceRequest::STATUS_CLOSED) {
                    return true;
                }
                return false;
            })
            ->schema(function ($record) {
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
                            }),
                    ]),
                ];
            })
            ->icon('heroicon-m-chat-bubble-bottom-center-text')
            ->color('info')
            ->action(function (array $data, $record): void {
                $comment = $record->comments()->create([
                    'comment'    => $data['comment'],
                    'created_by' => auth()->user()->id,
                ]);

                if ($comment) {
                    $record->logs()->create([
                        'created_by'  => auth()->user()->id,
                        'description' => 'Comment added: ' . $data['comment'],
                        'log_type'    => ServiceRequestLog::LOG_TYPE_COMMENT_ADDED,
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
            });
    }

    /**
     * Add photos action - إجراء إضافة صور
     */
    public static function getAddPhotosAction(): Action
    {
        return Action::make('AddPhotos')
            ->disabled(function ($record) {
                if ($record->status == ServiceRequest::STATUS_CLOSED) {
                    return true;
                }
                return false;
            })
            ->schema([
                // Schema can be added here if needed
            ])
            ->action(function (array $data, $record): void {
                // إضافة الصور إلى media collection
                if (isset($data['images']) && is_array($data['images'])) {
                    foreach ($data['images'] as $file) {
                        $record->addMedia($file)->toMediaCollection('images');
                    }
                }
                $record->logToEquipment(
                    EquipmentLog::ACTION_UPDATED,
                    'New Images added to service request #' . $record->id
                );
                $record->logs()->create([
                    'created_by'  => auth()->user()->id,
                    'description' => 'Images added',
                    'log_type'    => ServiceRequestLog::LOG_TYPE_IMAGES_ADDED,
                ]);
            })
            ->button()
            ->icon('heroicon-m-newspaper')
            ->color('success');
    }

    /**
     * View gallery action - إجراء عرض المعرض
     */
    public static function getViewGalleryAction(): Action
    {
        return Action::make('viewGallery')
            ->hidden(function ($record) {
                return $record->photos_count <= 0 ? true : false;
            })
            ->label('Browse photos')
            ->label(function ($record) {
                return $record->photos_count;
            })
            ->modalHeading('Request service photos')
            ->modalWidth('lg')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->button()
            ->icon('heroicon-o-camera')
            ->modalContent(function ($record) {
                return view('filament.resources.service_requests.gallery', [
                    'photos' => $record->photos()->orderBy('id', 'desc')->get()
                ]);
            });
    }
}
