<?php

namespace App\Filament\Clusters\HRTasksSystem\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Clusters\HRTasksSystem\Resources\TaskStatusResource\Pages\ListTaskStatuses;
use App\Filament\Clusters\HRTasksSystem;
use App\Filament\Clusters\HRTasksSystem\Resources\TaskStatusResource\Pages;
use App\Models\TaskStatus;
use Filament\Forms;
use Filament\Forms\Components\Textarea;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TaskStatusResource extends Resource
{
    protected static ?string $model = TaskStatus::class;
    // protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    // protected static ?int $navigationSort = 4;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    // protected static ?string $cluster = HRTasksSystem::class;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('description')->searchable(),
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
            'index' => ListTaskStatuses::route('/'),
            // 'create' => Pages\CreateTaskStatus::route('/create'),
            // 'edit' => Pages\EditTaskStatus::route('/{record}/edit'),
        ];
    }
}
