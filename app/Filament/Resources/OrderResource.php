<?php

namespace App\Filament\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema; 
use App\Filament\Resources\OrderResource\RelationManagers\OrderDetailsRelationManager;
use App\Filament\Resources\OrderResource\RelationManagers\LogsRelationManager;
use App\Filament\Resources\OrderResource\Pages\ListOrders;
use App\Filament\Resources\OrderResource\Pages\CreateOrder;
use App\Filament\Resources\OrderResource\Pages\ViewOrder;
use App\Filament\Resources\OrderResource\Pages\EditOrder;
use App\Filament\Resources\OrderResource\Pages\OrderReportCustom;
use App\Filament\Clusters\MainOrdersCluster; 
use App\Filament\Resources\OrderResource\Schemas\OrderForm;
use App\Filament\Resources\OrderResource\Tables\OrderTable; 
use App\Models\Branch;
use App\Models\Order; 
use Closure; 
use Filament\Pages\Page;
use Filament\Resources\Resource; 
use Filament\Support\Icons\Heroicon; 
use Filament\Tables\Table;
// use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope; 

class OrderResource extends Resource
{
    protected static ?string $cluster = MainOrdersCluster::class;
    // public static function getPermissionPrefixes(): array
    // {
    //     return [
    //         'view',
    //         'view_any',
    //         'create',
    //         'update',
    //         'delete',
    //         'delete_any',
    //         'publish',
    //     ];
    // }

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 1;
    protected static ?string $model = Order::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::BuildingStorefront;
    // protected static ?string $navigationGroup = 'Orders';
    protected static ?string $recordTitleAttribute = 'id';
    public static function getNavigationLabel(): string
    {
        return __('lang.orders');
    }
    public static function form(Schema $schema): Schema
    {
        return OrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrderTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            OrderDetailsRelationManager::class,
            LogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {

        return [
            'index' => ListOrders::route('/'),
            'create' => CreateOrder::route('/create'),
            'view' => ViewOrder::route('/{record}'),
            'edit' => EditOrder::route('/{record}/edit'),
            'order-report-custom' => OrderReportCustom::route('/order-report-custom'),

        ];
    }
    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ListOrders::class,
            // Pages\CreateOrder::class,
            ViewOrder::class,
            EditOrder::class,
        ]);
    }


    protected function getTableReorderColumn(): ?string
    {
        return 'sort';
    }

    protected function getTableRecordActionUsing(): ?Closure
    {
        return null;
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_purchased', 0)
            ->whereHas('orderDetails')
            ->whereHas('branch', function ($query) {
                $query->where('type', '!=', Branch::TYPE_RESELLER); // غيّر "warehouse" لنوع الفرع الذي تريده
            })
            ->forBranchManager()
            ->count();
    }

    public function isTableSearchable(): bool
    {
        return true;
    }

    protected function applySearchToTableQuery(Builder $query): Builder
    {
        if (filled($searchQuery = $this->getTableSearchQuery())) {
            $query->whereIn('id', Order::search($searchQuery)->keys());
        }

        return $query;
    }
    public static function canCreate(): bool
    {
        return false;
        if (isSuperAdmin()) {
            return true;
        }
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return Order::query()
            ->forBranchManager()
            ->where('is_purchased', 0)
            ->whereHas('orderDetails')
            ->whereHas('branch', function ($query) {
                $query->where('type', '!=', Branch::TYPE_RESELLER); // غيّر "warehouse" لنوع الفرع الذي تريده
            })

            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    // public static function getGlobalSearchResultTitle(Model $record): string
    // {
    //     return $record->id;
    // }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return 'Order #' . $record->id;
    }


    public static function canDelete(Model $record): bool
    {
        // return false;
        if (isSuperAdmin()) {
            return true;
        }
        return false;
    }

    public static function canDeleteAny(): bool
    {
        // return false;
        if (isSuperAdmin()) {
            return true;
        }
        return false;
    }


    public static function canEdit(Model $record): bool
    {
        return false;
        if (isSuperAdmin()) {
            return true;
        }
        return false;
    }

    public static function getGlobalSearchResultsLimit(): int
    {
        return 15;
    }
}
