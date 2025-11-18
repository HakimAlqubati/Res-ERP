<?php

namespace App\Filament\Clusters\POSIntegration\Resources\PosSales\RelationManagers;

use App\Filament\Clusters\POSIntegration\Resources\PosSales\PosSaleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $relatedResource = PosSaleResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->paginated([10, 25, 50, 100, 200])
            ->defaultPaginationPageOption(100)
            ->columns([
                TextColumn::make('product.name'),
                TextColumn::make('unit.name'),
                TextColumn::make('package_size'),
                TextColumn::make('quantity'),
            ])
            ->recordActions([])
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
