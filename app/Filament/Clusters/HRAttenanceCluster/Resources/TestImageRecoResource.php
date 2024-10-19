<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources;

use App\Filament\Clusters\HRAttenanceCluster;
use App\Filament\Clusters\HRAttenanceCluster\Resources\TestImageRecoResource\Pages;
use App\Filament\Clusters\HRAttenanceCluster\Resources\TestImageRecoResource\Pages\CreateTestImageReco;
use App\Models\TestImageReco;
use Aws\Rekognition\RekognitionClient;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TestImageRecoResource extends Resource
{
    protected static ?string $model = TestImageReco::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = HRAttenanceCluster::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    { 
        return $form
            ->schema([
                
                FileUpload::make('image')
                    ->directory('test_image_reco_images')
                    ->label('Source image')
                    ->image()->required()
                ,
                FileUpload::make('image2')
                    ->label('Target image')
                    ->directory('test_image_reco_images')
                    ->image()->required()
                ,
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('id','desc')
        ->striped()
            ->columns([
                TextColumn::make('title')->label('Result'),
                TextColumn::make('details')->sortable()->searchable()
                ->getStateUsing(function ($record) {
                    // Decode JSON to an associative array
                    $detailsArray = json_decode($record?->details, true);
                    
                    // Handle case where details is null or not valid JSON
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        return 'Invalid JSON'; // or handle the error as needed
                    }
                
                    // Check if the decoded array is not empty and has the expected structure
                    if (isset($detailsArray[0]['similarity'])) {
                        // Return the similarity with its key
                        return 'Similarity: ' . round( $detailsArray[0]['similarity'],2) . '%';
                    }
                    
                    // If the expected key doesn't exist, return a fallback message
                    return 'No similarity value available';
                }),
                ImageColumn::make('image_1')->label('Image')->width(180)->height(180)->circular(),
                ImageColumn::make('image_3')->label('Target image')->width(180)->height(180)->circular(),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTestImageRecos::route('/'),
            'create' => Pages\CreateTestImageReco::route('/create'),
            // 'edit' => Pages\EditTestImageReco::route('/{record}/edit'),
        ];
    }
}
