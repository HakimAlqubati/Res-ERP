<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema; 
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource\Pages\ListStockInventories;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource\Pages\CreateStockInventory;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource\Pages\EditStockInventory;
use App\Models\UnitPrice;
use App\Filament\Clusters\InventoryManagementCluster; 
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource\Schemas\StockInventoryForm;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource\RelationManagers\DetailsRelationManager;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryResource\Tables\StockInventoryTable;
use App\Models\Product;
use App\Models\StockInventory; 
use App\Services\MultiProductsInventoryService; 
use Filament\Facades\Filament; 
use Filament\Pages\Page;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color; 
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StockInventoryResource extends Resource
{
    protected static ?string $model = StockInventory::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster                             = InventoryManagementCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort                         = 9;

    public static function getNavigationLabel(): string
    {
        return 'Stocktakes';
    }
    public static function getPluralLabel(): ?string
    {
        return 'Stocktakes';
    }

    protected static ?string $pluralLabel = 'Stocktake';

    public static function getModelLabel(): string
    {
        return 'Stocktake';
    }
    public static function form(Schema $schema): Schema
    {

        return StockInventoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    { 

       return StockInventoryTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            DetailsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListStockInventories::route('/'),
            // 'new-create' => StockInventoryReactPage::route('/new-create'),
            'create' => CreateStockInventory::route('/create'),
            'edit'   => EditStockInventory::route('/{record}/edit'),
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ListStockInventories::class,
            CreateStockInventory::class,
            // Pages\EditStockInventory::class,
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = static::getModel()::query();

        if (
            static::isScopedToTenant() &&
            ($tenant = Filament::getTenant())
        ) {
            static::scopeEloquentQueryToTenant($query, $tenant);
        }

        return $query;
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getDifference($remaningQty, $physicalQty)
    {
        $remaningQty = (float) $remaningQty;
        $physicalQty = (float) $physicalQty;
        if ($physicalQty === 0) {
            return 0;
        }
        // dd($remaningQty,$physicalQty);
        $difference = round($physicalQty - $remaningQty, 4);
        return $difference;
    }

    public static function canDeleteAny(): bool
    {
        if (isSuperAdmin()) {
            return true;
        }
        return false;
    }

    public static function canForceDelete(Model $record): bool
    {
        if (isSuperAdmin()) {
            return true;
        }
        return false;
    }

    public static function canForceDeleteAny(): bool
    {
        if (isSuperAdmin()) {
            return true;
        }
        return false;
    }
    public static function getNavigationBadgeColor(): string | array | null
    {
        return Color::Green;
    }

    public static function getProductUnits($productId)
    {
        $product = Product::find($productId);
        if (! $product) {
            return collect();
        }
        return $product->supplyOutUnitPrices ?? collect();
    }

    public static function handleUnitSelection(callable $set, callable $get, $unitId)
    {
        $productId = $get('product_id');
        $start     = microtime(true);
        if (! $productId || ! $unitId) {
            return;
        }

        $unitPrice = UnitPrice::where('product_id', $productId)
            ->where('unit_id', $unitId)
            ->first();

        $service = new MultiProductsInventoryService(
            null,
            $productId,
            $unitId,
            $get('../../store_id'),
        );
        // $inventoryFromCache = InventoryProductCacheService::getCachedInventoryForProduct($get('product_id'), $unitId, $get('../../store_id'));

        // $packageSize = $inventoryFromCache['package_size'] ?? 0;
        // $remaningQty = $inventoryFromCache['remaining_qty'] ?? 0;
        $packageSize = $unitPrice->package_size ?? 0;
        $remaningQty = $service->getInventoryForProduct($productId)[0]['remaining_qty'] ?? 0;

        $set('system_quantity', $remaningQty);
        $set('physical_quantity', $remaningQty);
        $difference = static::getDifference($remaningQty, $get('physical_quantity'));
        $set('difference', $difference);
        $set('package_size', $packageSize);
        // Stop timing and calculate duration
        $end          = microtime(true);
        $duration     = $end - $start;
        $seconds      = floor($duration);
        $milliseconds = round(($duration - $seconds) * 1000, 2);
        // showSuccessNotifiMessage('( '. $seconds.'Seconds ) ('. $milliseconds .' Milliseconds)');

    }
}
