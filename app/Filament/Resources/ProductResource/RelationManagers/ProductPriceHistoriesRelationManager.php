<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Models\ProductPriceHistory;
use App\Models\Unit;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductPriceHistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'productPriceHistories';
    protected static ?string $label = 'Product Price History';
    protected static ?string $pluralLabel = 'Product Price History';
    protected static ?string $title = 'Product Price History';
    protected static ?string $recordTitleAttribute = 'id';

    public function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('old_price')
                ->label('Old Price')
                ->numeric()
                ->required(),

            TextInput::make('new_price')
                ->label('New Price')
                ->numeric()
                ->required(),

            Select::make('unit_id')
                ->label('Unit')
                ->options(Unit::pluck('name', 'id'))
                ->searchable()
                ->required(),

            Textarea::make('note')
                ->label('Note')
                ->rows(2)
                ->maxLength(255),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->striped()
            ->columns([

                Tables\Columns\TextColumn::make('old_price')
                    ->label('Old Price')
                    ->formatStateUsing(fn($state) => number_format($state, 2)),

                Tables\Columns\TextColumn::make('new_price')
                    ->label('New Price')
                    ->formatStateUsing(fn($state) => number_format($state, 2)),
                // Tables\Columns\TextColumn::make('source_id')
                //     ->label('source_id'),

                Tables\Columns\TextColumn::make('unit.name')
                    ->label('Unit')
                    ->sortable(),

                Tables\Columns\TextColumn::make('note')
                    ->label('Note')
                    ->limit(100)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([])
            ->headerActions([])
            ->actions([
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
                // Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\DeleteBulkAction::make(),
                // Tables\Actions\RestoreBulkAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
