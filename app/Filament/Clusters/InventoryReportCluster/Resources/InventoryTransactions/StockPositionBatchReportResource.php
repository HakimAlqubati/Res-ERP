<?php

namespace App\Filament\Clusters\InventoryReportCluster\Resources\InventoryTransactions;

use App\Filament\Clusters\InventoryReportCluster;
use App\Filament\Clusters\InventoryReportCluster\Resources\InventoryTransactions\Pages\ListStockPositionBatchReport;
use App\Filament\Clusters\InventoryReportCluster\Resources\InventoryTransactions\Tables\InventoryTransactionsTable;
use App\Models\InventoryTransaction;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StockPositionBatchReportResource extends Resource
{
    protected static ?string $model = InventoryTransaction::class;
    protected static ?string $slug = 'stock-position-batch-report';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static bool $shouldRegisterNavigation = false;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    protected static ?string $cluster = InventoryReportCluster::class;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 12;

    protected static ?string $recordTitleAttribute = 'product';

    public static function getLabel(): ?string
    {
        return 'Store Position Batch Report';
    }

    public static function getNavigationLabel(): string
    {
        return 'Store Position Batch Report';
    }

    public static function getPluralLabel(): ?string
    {
        return 'Store Position Batch Report';
    }

    public static function table(Table $table): Table
    {
        return InventoryTransactionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockPositionBatchReport::route('/'),

        ];
    }
}
