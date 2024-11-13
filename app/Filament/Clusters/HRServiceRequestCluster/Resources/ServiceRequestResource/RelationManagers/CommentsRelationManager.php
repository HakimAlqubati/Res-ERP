<?php

namespace App\Filament\Clusters\HRServiceRequestCluster\Resources\ServiceRequestResource\RelationManagers;

use App\Models\Task;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Str;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {return $ownerRecord->comments->count();}

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make()->schema([
                    Textarea::make('comment')->columnSpanFull()->required(),
                    Hidden::make('user_id')->default(auth()->user()->id),
                    // FileUpload::make('image_path')
                    //     ->disk('public')
                    //     ->label('')
                    //     ->directory('service_comments')
                    //     ->columnSpanFull()
                    //     ->image()
                    //     ->multiple()
                    //     ->resize(5)
                    //     ->downloadable()
                    //     ->previewable()
                    //     ->imagePreviewHeight('250')
                    //     ->loadingIndicatorPosition('left')
                    //     ->panelLayout('integrated')
                    //     ->removeUploadedFileButtonPosition('right')
                    //     ->uploadButtonPosition('left')
                    //     ->uploadProgressIndicatorPosition('left')
                    //     ->panelLayout('grid')
                    //     ->reorderable()
                    //     ->openable()
                    //     ->downloadable(true)
                    //     ->previewable(true)
                    //     ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                    //         return (string) str($file->getClientOriginalName())->prepend('comment-');
                    //     })
                    // ,

                ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('comment')
            ->columns([
                Tables\Columns\TextColumn::make('comment'),
                Tables\Columns\TextColumn::make('user.name'),
                Tables\Columns\TextColumn::make('created_at'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('New')
                // ->action(function (Model $ownerRecord,array $data, $record): void {

                //     $comment = $record->comments()->create([
                //         'comment' => $data['comment'],
                //         'created_by' => auth()->user()->id,
                //     ]);

                //     if ($comment) {
                //         $record->logs()->create([
                //             'created_by' => auth()->user()->id,
                //             'description' => 'Comment added: ' . $data['comment'],
                //             'log_type' => ServiceRequestLog::LOG_TYPE_COMMENT_ADDED,
                //         ]);
                //     }
                //     // If there are photos, save them after the comment is created
                //     if (isset($data['image_path']) && is_array($data['image_path']) && count($data['image_path']) > 0) {
                //         foreach ($data['image_path'] as $file) {
                //             $comment->photos()->create([
                //                 'image_name' => $file,
                //                 'image_path' => $file,
                //                 'created_by' => auth()->user()->id,
                //             ]);
                //         }
                //     }
                // })
                ,
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Action::make('AddPhotos')
                    ->form([

                        FileUpload::make('image_path')
                            ->disk('public')
                            ->label('')
                            ->directory('comments')
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
                                return Str::random(15) . "." . $file->getClientOriginalExtension();
                            }),
                    ])
                    ->action(function (array $data, $record): void {
                        $comment = $record;
                        if (isset($data['image_path']) && is_array($data['image_path']) && count($data['image_path']) > 0) {
                            foreach ($data['image_path'] as $file) {
                                $comment->photos()->create([
                                    'file_name' => $file,
                                    'file_path' => $file,
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
                    ->modalHeading('Comment photos')
                    ->modalWidth('lg') // Adjust modal size
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                // ->iconButton()
                    ->button()
                    ->icon('heroicon-o-camera')
                    ->modalContent(function ($record) {
                        
                        return view('filament.resources.service_requests.gallery-comment-task', ['photos' => $record->photos]);
                    }),

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected function canEdit(Model $record): bool
    {
        return false;
    }

    protected function canCreate(): bool
    {
        if($this->ownerRecord->task_status == Task::STATUS_CLOSED && auth()->user()?->employee?->id){
            return false;
        }
        return true;
    }

    protected function canDelete(Model $record): bool
    {
        return false;
    }

}
