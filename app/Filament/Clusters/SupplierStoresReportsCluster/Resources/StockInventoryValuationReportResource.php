<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources;

use App\Filament\Clusters\InventoryReportCluster;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryValuationReportResource\Pages\ListStockInventoryValuationReport;
use App\Models\StockInventory;
use App\Models\Store;
use App\Modules\Stock\Reports\StockInventoryValuationReport\Contracts\StockInventoryValuationServiceInterface;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

class StockInventoryValuationReportResource extends Resource
{
    protected static ?string $model = StockInventory::class;

    protected static ?string $slug = 'stock-inventory-valuation-report';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $cluster = InventoryReportCluster::class;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 14;

    protected static bool $shouldRegisterNavigation = false;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getLabel(): ?string
    {
        return 'Stocktake Valuation Report';
    }

    public static function getNavigationLabel(): string
    {
        return 'Stocktake Valuation Report';
    }

    public static function getPluralLabel(): ?string
    {
        return 'Stocktake Valuation Report';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->deferFilters(false)
            ->filters([
                Filter::make('valuation_filter')
                ->columnSpanFull()
                    ->schema([
                        Select::make('store_id')
                            ->label('Store')
                            ->placeholder('Select Store')
                            ->options(Store::active()->pluck('name', 'id')->toArray())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (callable $set) => $set('inventory_date', null)),

                        Select::make('inventory_date')
                            ->label('Date')
                            ->placeholder('Select Inventory Date')
                            ->searchable()
                            ->live()
                            ->options(function (callable $get) {
                                $storeId = $get('store_id');
                                if (! $storeId) {
                                    return [];
                                }

                                return app(StockInventoryValuationServiceInterface::class)
                                    ->getAvailableDatesByStore((int) $storeId);
                            }),
                    ])
                    ->columns(2),
            ], layout: FiltersLayout::AboveContent);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockInventoryValuationReport::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return 'Report';
    }

    public static function canViewAny(): bool
    {
        if (isSuperAdmin() || isSystemManager() || isBranchManager() || isStoreManager() || isFinanceManager()) {
            return true;
        }

        return false;
    }
}
