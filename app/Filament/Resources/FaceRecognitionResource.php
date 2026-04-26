<?php

namespace App\Filament\Resources;

use Aws\DynamoDb\DynamoDbClient;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;


use App\Filament\Clusters\HRCluster;
use App\Filament\Resources\FaceRecognitionResource\Pages;
use App\Models\FaceRecognition;
use Filament\Actions\Action;
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

                Tables\Columns\TextColumn::make('base_url')
                    ->label('Base URL')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->limit(30),
            ])
            ->filters([
                // Tables\Filters\SelectFilter::make('base_url')
                //     ->label('Base URL')
                //     ->options(fn() => FaceRecognition::query()->pluck('base_url', 'base_url')->filter()->unique()->toArray()),
            ])
            ->recordActions([
                Action::make('delete_from_aws')
                    ->label('Delete From AWS')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn(FaceRecognition $record) => static::deleteFromAws($record))
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
        return FaceRecognition::where('base_url', url('/'))->count();
        return
            static::getModel()::where('base_url', url('/'))->count();;
    }
    public static function canViewAny(): bool
    {
        // return false;
        if (isSuperAdmin()) {
            return true;
        }
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            // ->where('base_url', url('/'))
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
    protected static bool $shouldRegisterNavigation = true;


    public static function deleteFromAws(FaceRecognition $record): void
    {
        try {
            $client = new DynamoDbClient([
                'region'  => env('AWS_DEFAULT_REGION'),
                'version' => 'latest',
            ]);

            // 1. Delete from AWS DynamoDB
            $client->deleteItem([
                'TableName' => 'face_recognition',
                'Key' => [
                    'RekognitionId' => ['S' => $record->id]
                ]
            ]);

            // 2. Delete from local Sushi cache (SQLite) to update UI
            $record->delete();

            Notification::make()
                ->title('Deleted successfully')
                ->body('The face record has been removed from AWS and the local system.')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Log::error('AWS DynamoDB Deletion Failed: ' . $e->getMessage(), [
                'rekognition_id' => $record->id
            ]);

            Notification::make()
                ->title('Deletion failed')
                ->body('An error occurred while removing the record from AWS. Check the logs for details.')
                ->danger()
                ->send();
        }
    }
}
