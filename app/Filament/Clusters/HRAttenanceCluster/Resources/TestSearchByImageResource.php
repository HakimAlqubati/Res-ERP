<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources;

use App\Filament\Clusters\HRAttenanceCluster;
use App\Filament\Clusters\HRAttenanceCluster\Resources\CustomResource\Pages\SearchByCamera;
use App\Filament\Clusters\HRAttenanceCluster\Resources\TestSearchByImageResource\Pages;
use App\Filament\Clusters\HRAttenanceCluster\Resources\TestSearchByImageResource\RelationManagers;
use App\Models\TestSearchByImage;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Storage;

class TestSearchByImageResource extends Resource
{
    protected static ?string $model = TestSearchByImage::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = HRAttenanceCluster::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 21;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                FileUpload::make('image')
                ->label('Upload Image')
                ->uploadingMessage('Uploading attachment...')
                ->required()
                ->disk('s3') // Ensure this matches your disk configuration
                ->directory('uploads')
                ->visibility('private')
                ->preserveFilenames()
                ->image(),
            ]);
    }

    public static function table(Table $table): Table
    {     
        return $table->defaultSort('id','desc')
            ->columns([
                ImageColumn::make('image_url')->label('Image ')->circular(),
                TextColumn::make('rekognition_id')->label('Rekognition ID'),
                TextColumn::make('name')->label('Name'),
                TextColumn::make('created_at')->label('Created At')->dateTime(),
                TextColumn::make('updated_at')->label('Updated At')->dateTime(),
         
            ])
            ->filters([
                //
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListTestSearchByImages::route('/'),
            'create' => Pages\CreateTestSearchByImage::route('/create'),
            'camera' => SearchByCamera::route('/camera'),
            // 'edit' => Pages\EditTestSearchByImage::route('/{record}/edit'),
        ];
    }
}
