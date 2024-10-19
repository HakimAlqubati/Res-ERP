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
        return $table
            ->columns([
                TextColumn::make('title')->label('Result'),
                // TextColumn::make('description')->sortable()->searchable(),
                ImageColumn::make('image_1')->label('Image'),
                ImageColumn::make('image_3')->label('Target image'),
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
            'edit' => Pages\EditTestImageReco::route('/{record}/edit'),
        ];
    }
}
