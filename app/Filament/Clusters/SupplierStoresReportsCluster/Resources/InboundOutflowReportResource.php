<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources;

use App\Filament\Clusters\InventoryReportCluster;
use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionReportResource\Pages\ListInboundOutflowReport;
use App\Models\Product;
use App\Models\Store;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionReportResource\Pages;
use Filament\Resources\Resource;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

class InboundOutflowReportResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $slug = 'inbound-outflow-report';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrow-right-circle';
    protected static ?string $cluster = InventoryReportCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 4;
    protected static bool $shouldRegisterNavigation = false;
    public static function getLabel(): ?string
    {
        return 'Inbound → Outflows';
    }

    public static function getNavigationLabel(): string
    {
        return 'Inbound → Outflows';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInboundOutflowReport::route('/'),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table->filters([
            Filter::make('transactionable_id')
                ->schema([
                    TextInput::make('transactionable_id')
                        ->label('Transaction ID')
                        ->required(),
                    Select::make('transactionable_type')
                        ->label('Transaction Type')
                        ->options([
                            'App\Models\PurchaseInvoice' => 'Purchase Invoice',
                            'App\Models\GoodsReceivedNote' => 'GRN',
                            'App\Models\StockSupplyOrder' => 'Stock Supply Order',
                            'App\Models\StockAdjustmentDetail' => 'Stock Adjustment (Increase)',
                            'App\Models\ReturnedOrder' => 'Returned Order', // ✅ الإضافة هنا

                        ])
                        ->searchable(),
                ]),
        ], layout: FiltersLayout::AboveContent);
    }
}
