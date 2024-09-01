<?php

namespace App\Filament\Clusters\HRTasksSystem\Resources;

use App\Filament\Clusters\HRTasksSystem;
use App\Filament\Clusters\HRTasksSystem\Resources\DailyTasksSettingUpResource\Pages;
use App\Models\DailyTasksSettingUp;
use App\Models\TasksMenu;
use App\Models\User;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DailyTasksSettingUpResource extends Resource
{
    protected static ?string $model = DailyTasksSettingUp::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = HRTasksSystem::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 5;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make()->columns(3)->schema([
                    TextInput::make('title')
                        ->required()
                        ->columnSpan(2)
                        ->autofocus()
                        ->maxLength(255),
                    Checkbox::make('active')->default(1),

                    Textarea::make('description')
                        ->required()
                        ->columnSpan(3)
                        ->autofocus()
                        ->maxLength(255),
                    Select::make('assigned_to_users')->columnSpan(2)
                        ->options(User::all()->pluck('name', 'id'))
                        ->multiple()->searchable()->required(),
                    Select::make('assigned_by')
                        ->options(User::all()->pluck('name', 'id'))
                        ->columnSpan(1)->searchable()->required(),
                    CheckboxList::make('menu_tasks')->nullable()->searchable()->options(
                        TasksMenu::where('active', 1)->select('name', 'id')->get()->pluck('name', 'id')
                    ),

                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable(),
                TextColumn::make('description')->searchable(),

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                // Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListDailyTasksSettingUps::route('/'),
            'create' => Pages\CreateDailyTasksSettingUp::route('/create'),
            'edit' => Pages\EditDailyTasksSettingUp::route('/{record}/edit'),
        ];
    }

}
