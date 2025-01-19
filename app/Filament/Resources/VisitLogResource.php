<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VisitLogResource\Pages;
use App\Filament\Resources\VisitLogResource\RelationManagers;
use App\Models\VisitLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VisitLogResource extends Resource
{
    protected static ?string $model = VisitLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('User')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->required(),
                Forms\Components\TextInput::make('route_name')
                    ->required(),
                Forms\Components\TextInput::make('date')
                    ->required(),
                Forms\Components\TextInput::make('time')
                    ->required(),
                Forms\Components\DateTimePicker::make('visited_at')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('User'),
                Tables\Columns\TextColumn::make('route_name')->label('Route Name'),
                Tables\Columns\TextColumn::make('date')->label('Date'),
                Tables\Columns\TextColumn::make('time')->label('Time'),
                Tables\Columns\TextColumn::make('visited_at')->label('Visited At'),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVisitLogs::route('/'),
            // 'create' => Pages\CreateVisitLog::route('/create'),
            // 'edit' => Pages\EditVisitLog::route('/{record}/edit'),
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
