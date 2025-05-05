<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources;

use App\Filament\Clusters\InventoryManagementCluster;
use App\Filament\Clusters\InventoryReportCluster;
use App\Models\Product; 
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource; 
use Filament\Tables\Table; 
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\MinimumProductQtyReportResource\Pages;

class MinimumProductQtyReportResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = InventoryReportCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 10;

    public static function getPluralLabel(): ?string
    {
        return 'Minimum Product Quantity';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Minimum Product Quantity';
    }


    public static function table(Table $table): Table
    {
        return $table->striped()
            ->columns([])
            ->filters([
                //
            ])
            ->actions([]);
    }



    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMinimumProductQtyReports::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return 'Report';
        return static::getModel()::whereNotNull('minimum_stock_qty')->count();
    }
}
