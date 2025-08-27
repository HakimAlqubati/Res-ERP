<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use App\Models\Unit;
use App\Models\UnitPrice;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                TextColumn::make('unit.name'),
                TextColumn::make('price'),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->using(function (HasRelationshipTable $livewire, array $data): Model {
                        // dd($data);
                        return $livewire->getRelationship()->create($data);
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
                RestoreBulkAction::make(),
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
