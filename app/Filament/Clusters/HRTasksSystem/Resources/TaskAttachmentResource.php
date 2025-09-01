<?php

namespace App\Filament\Clusters\HRTasksSystem\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Clusters\HRTasksSystem\Resources\TaskAttachmentResource\Pages\ListTaskAttachments;
use App\Filament\Clusters\HRTasksSystem;
use App\Filament\Clusters\HRTasksSystem\Resources\TaskAttachmentResource\Pages;
use App\Models\TaskAttachment;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TaskAttachmentResource extends Resource
{
    protected static ?string $model = TaskAttachment::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 3;
    // protected static ?string $cluster = HRTasksSystem::class;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index' => ListTaskAttachments::route('/'),
            // 'create' => Pages\CreateTaskAttachment::route('/create'),
            // 'edit' => Pages\EditTaskAttachment::route('/{record}/edit'),
        ];
    }
}
