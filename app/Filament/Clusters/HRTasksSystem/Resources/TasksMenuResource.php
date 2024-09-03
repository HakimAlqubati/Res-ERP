<?php

namespace App\Filament\Clusters\HRTasksSystem\Resources;

use App\Filament\Clusters\HRTasksSystem;
use App\Filament\Clusters\HRTasksSystem\Resources\TasksMenuResource\Pages;
use App\Filament\Clusters\HRTasksSystem\Resources\TasksMenuResource\RelationManagers;
use App\Models\TasksMenu;
use Filament\Forms;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TasksMenuResource extends Resource
{
    protected static ?string $model = TasksMenu::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    // protected static ?string $cluster = HRTasksSystem::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 5;
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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
            ->actions([
                Tables\Actions\EditAction::make(),
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

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTasksMenus::route('/'),
            'create' => Pages\CreateTasksMenu::route('/create'),
            'edit' => Pages\EditTasksMenu::route('/{record}/edit'),
        ];
    }
}
