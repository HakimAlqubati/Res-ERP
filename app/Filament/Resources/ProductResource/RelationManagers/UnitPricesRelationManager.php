<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Models\Unit;
use App\Models\UnitPrice;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Contracts\HasRelationshipTable;
use Illuminate\Database\Eloquent\Model;

class UnitPricesRelationManager extends RelationManager
{
    protected static string $relationship = 'unitPrices';
    protected static ?string $model = UnitPrice::class;
    protected static ?string $recordTitleAttribute = 'product_id';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('product_id')->hidden(1),
                Select::make('unit_id')
                    ->searchable()
                    ->options(function () {
                        return Unit::pluck('name', 'id');
                    })->searchable(),
                TextInput::make('price')->type('number')->default(1)
                    // ->mask(
                    //     fn (TextInput\Mask $mask) => $mask
                    //         ->numeric()
                    //         ->decimalPlaces(2)
                    //         ->thousandsSeparator(',')
                    // ),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('unit.name'),
                Tables\Columns\TextColumn::make('price'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->using(function (HasRelationshipTable $livewire, array $data): Model {
                        // dd($data);
                        return $livewire->getRelationship()->create($data);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make(),
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
