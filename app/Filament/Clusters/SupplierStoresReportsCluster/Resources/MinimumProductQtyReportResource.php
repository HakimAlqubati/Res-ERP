<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\MinimumProductQtyReportResource\Pages\ListMinimumProductQtyReports;
use App\Filament\Clusters\InventoryManagementCluster;
use App\Filament\Clusters\InventoryReportCluster;
use App\Models\Product;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\MinimumProductQtyReportResource\Pages;

class MinimumProductQtyReportResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = InventoryReportCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 10;
    protected static bool $shouldRegisterNavigation = false;
    public static function getPluralLabel(): ?string
    {
        return 'Minimum Product Qty';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Minimum Product Qty';
    }


    public static function table(Table $table): Table
    {
        return $table->striped()
            ->columns([])
            ->filters([
                //
            ])
            ->recordActions([]);
    }



    public static function getPages(): array
    {
        return [
            'index' => ListMinimumProductQtyReports::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return 'Report';
        return static::getModel()::whereNotNull('minimum_stock_qty')->count();
    }
}
