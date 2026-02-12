<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\HRCluster;
use App\Filament\Resources\FaceRecognitionResource\Pages;
use App\Models\FaceRecognition;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FaceRecognitionResource extends Resource
{
    protected static ?string $model = FaceRecognition::class;

    protected static string | \BackedEnum | null $navigationIcon                      = Heroicon::Photo;

    protected static ?string $navigationLabel = 'Face Recognition';

    protected static ?string $slug = 'face-recognition';

    protected static ?string $cluster                             = HRCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort                         = 5;



    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar_url')
                    ->label('Avatar')
                    ->circular()
                    ->size(60),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->size('md'), // Medium

                Tables\Columns\TextColumn::make('id')
                    ->label('Rekognition Face ID')
                    ->copyable()->alignCenter()
                    ->fontFamily('mono') // Is monospaced
                    ->color('gray')
                    ->limit(20),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Since it's read-only from DynamoDB via Sushi, we probably don't want edit/delete here unless we implement it
                // Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
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
            'index' => Pages\ListFaceRecognitions::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
