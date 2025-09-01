<?php

namespace App\Filament\Clusters\HRTasksSystem\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Clusters\HRTasksSystem\Resources\TasksMenuResource\Pages\ListTasksMenus;
use App\Filament\Clusters\HRTasksSystem\Resources\TasksMenuResource\Pages\CreateTasksMenu;
use App\Filament\Clusters\HRTasksSystem\Resources\TasksMenuResource\Pages\EditTasksMenu;
use App\Filament\Clusters\HRTasksSystem;
use App\Filament\Clusters\HRTasksSystem\Resources\TasksMenuResource\Pages;
use App\Filament\Clusters\HRTasksSystem\Resources\TasksMenuResource\RelationManagers;
use App\Models\TasksMenu;
use Filament\Forms;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TasksMenuResource extends Resource
{
    protected static ?string $model = TasksMenu::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    // protected static ?string $cluster = HRTasksSystem::class;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 5;
    
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Title')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description'),
                Checkbox::make('active')->default(1),
                Hidden::make('created_by')->default(auth()->user()->id),
                Hidden::make('updated_by')->default(auth()->user()->id),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->searchable(),
                TextColumn::make('name')->searchable()->label('Title'),
                TextColumn::make('description'),
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

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
    
    public static function getPages(): array
    {
        return [
            'index' => ListTasksMenus::route('/'),
            'create' => CreateTasksMenu::route('/create'),
            'edit' => EditTasksMenu::route('/{record}/edit'),
        ];
    }
}
