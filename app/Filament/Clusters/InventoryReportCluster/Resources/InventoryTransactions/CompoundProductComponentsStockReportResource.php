<?php

namespace App\Filament\Clusters\InventoryReportCluster\Resources\InventoryTransactions;

use App\Filament\Clusters\InventoryReportCluster;
use App\Filament\Clusters\InventoryReportCluster\Resources\InventoryTransactions\Pages\ListCompoundProductComponentsStockReport;
use App\Filament\Clusters\InventoryReportCluster\Resources\InventoryTransactions\Tables\CompoundProductComponentsStockReportTable;
use App\Models\ProductItem; // Using ProductItem as model for simplicity
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CompoundProductComponentsStockReportResource extends Resource
{
    protected static ?string $model = ProductItem::class;
    protected static ?string $slug = 'compound-product-components-stock-report';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static bool $shouldRegisterNavigation = false;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    protected static ?string $cluster = InventoryReportCluster::class;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 13;

    protected static ?string $recordTitleAttribute = 'product_id';

    public static function getLabel(): ?string
    {
        return 'Recipe Ingredients Stock Report';
    }

    public static function getNavigationLabel(): string
    {
        return 'Recipe Ingredients Stock Report';
    }

    public static function getPluralLabel(): ?string
    {
        return 'Recipe Ingredients Stock Report';
    }

    public static function table(Table $table): Table
    {
        return CompoundProductComponentsStockReportTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompoundProductComponentsStockReport::route('/'),
        ];
    }
    
    protected static bool $isGloballySearchable = false;
}
