<?php

namespace App\Filament\Clusters\HRServiceRequestCluster\Resources\ServiceRequestResource\RelationManagers;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Forms\Components\TextInput::make('comment')
                //     ->required()
                //     ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('comment')
            ->columns([
                Tables\Columns\TextColumn::make('comment'),
                Tables\Columns\TextColumn::make('createdBy.name'),
                Tables\Columns\TextColumn::make('created_at'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Action::make('AddPhotos')
                    ->form([

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
        return false;
    }

    protected function canDelete(Model $record): bool
    {
        return false;
    }

}
