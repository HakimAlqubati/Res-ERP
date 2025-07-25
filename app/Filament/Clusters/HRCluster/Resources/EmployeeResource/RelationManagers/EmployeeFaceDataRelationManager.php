<?php
namespace App\Filament\Clusters\HRCluster\Resources\EmployeeResource\RelationManagers;

use Exception;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\BulkAction;
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
                Tables\Columns\ImageColumn::make('image_url')->circular()->label('Image'),
                Tables\Columns\TextColumn::make('employee_email'),
                Tables\Columns\IconColumn::make('active')->boolean(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                BulkAction::make('Generate Embeddings')
                    ->action(fn($records) => self::generateEmbeddings($records))
                    ->requiresConfirmation()
                    ->label('Generate Embeddings'),
            ]);
    }

    protected static function generateEmbeddings($records): void
    {
        foreach ($records as $record) {
            try {
                $imageUrl = $record->image_url;

                $response = Http::timeout(10)
                    ->withOptions(['verify' => false])
                    ->post('https://54.251.132.76:5000/api/represent', [
                        'img'               => $imageUrl,
                        'model_name'        => 'Facenet',
                        'detector_backend'  => 'opencv',
                        'enforce_detection' => false,
                    ]);
                    Log::info('result_embedding',[$response]);

                if ($response->ok()) {
                    
                    $json = $response->json();
                    if (isset($json['results'][0]['embedding'])) {
                        $record->update([
                            'embedding' => $json['results'][0]['embedding'],
                        ]);
                    }
                }
            } catch (Exception $e) {
                Log::error('face_embeddings', [$e->getMessage()]);
                continue;
            }
        }
    }

    public static function getRelationshipName(): string
    {
        return 'faceData';
    }
}