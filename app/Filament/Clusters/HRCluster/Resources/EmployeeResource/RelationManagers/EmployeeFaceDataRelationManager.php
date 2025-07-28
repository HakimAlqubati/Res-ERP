<?php
namespace App\Filament\Clusters\HRCluster\Resources\EmployeeResource\RelationManagers;

use Exception;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\CheckboxColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmployeeFaceDataRelationManager extends RelationManager
{
    protected static string $relationship = 'faceData';

    protected static ?string $recordTitleAttribute = 'image_path';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')->circular()->label('Image')->height(200)->width(200),
                Tables\Columns\TextColumn::make('response_message')->label('Response Message')->wrap()->limit(50)->tooltip(fn($state)=> $state),
                CheckboxColumn::make('active')->label(__('lang.active'))->toggleable()->alignCenter(),
                Tables\Columns\IconColumn::make('face_added')
                    ->label('Has Embedding')->alignCenter()
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make(),
                Tables\Actions\Action::make('refresh')->icon('heroicon-m-arrow-path'),
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                BulkAction::make('Reset Embeddings')
                    ->label('Reset Embeddings')
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation()
                    ->modalHeading('Reset Face Embeddings')
                    ->modalSubheading('This will remove all stored embeddings and mark them as not added.')
                    ->action(function ($records) {
                        foreach ($records as $record) {
                            $record->update([
                                'embedding'        => '[]',
                                'response_message' => 'Embedding reset.',
                                'face_added'       => false,
                            ]);
                        }
                        showSuccessNotifiMessage('Embeddings have been reset successfully.');
                    }),

                BulkAction::make('Generate Embeddings')
                // ->action(fn($records) => self::generateEmbeddings($records))
                    ->action(function ($records) {
                        foreach ($records as $record) {
                            \App\Jobs\GenerateFaceEmbeddingJob::dispatch($record->id);
                        }
                        showSuccessNotifiMessage('Embedding Jobs have been queued.');
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Queueing Embeddings...')
                    ->modalSubheading('Embeddings will be processed in background.')
                    ->label('Queue Embeddings'),

            ]);
    }

    protected static function generateEmbeddings($records): void
    {
        foreach ($records as $record) {
            try {
                $imageUrl = $record->image_url;

                $response = Http::timeout(180)
                    ->withOptions(['verify' => false])
                    ->post('https://54.251.132.76:5000/api/represent', [
                        'img'               => $imageUrl,
                        'model_name'        => 'Facenet',
                        'detector_backend'  => 'opencv',
                        'enforce_detection' => false,
                    ]);
                Log::info('result_embedding', [$response]);

                if ($response->ok()) {

                    showSuccessNotifiMessage('done');
                    $json = $response->json();
                    if (isset($json['results'][0]['embedding'])) {
                        $record->update([
                            'embedding' => $json['results'][0]['embedding'],
                        ]);
                    }
                }
            } catch (Exception $e) {
                Log::error('face_embeddings', [$e->getMessage()]);
                showWarningNotifiMessage($e->getMessage());
                continue;
            }
        }
    }

    public static function getRelationshipName(): string
    {
        return 'faceData';
    }
}