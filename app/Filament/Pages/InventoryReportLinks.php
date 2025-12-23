<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\FifoInventoryReportResource;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InboundOutflowReportResource;

// --- استيراد الـ Resources ---
// تأكد من أن هذه المسارات صحيحة ومطابقة لمشروعك

// من الطلب الأصلي
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionPurchaseReportResource;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionReportResource;

// من الـ Resources الجديدة التي أرسلتها
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionTruckingReportResource;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryWithUsageReportResource;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\ManufacturingInventoryReportResource;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\MinimumProductQtyReportResource;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockAdjustmentReportResource;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockAdjustmentSummaryReportResource;
use App\Filament\Resources\InVSReportResource;
use App\Filament\Resources\StockSupplyOrderReportResource;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class InventoryReportLinks extends Page
{
    protected static string | \BackedEnum | null $navigationIcon  = 'heroicon-o-squares-2x2';
    protected static ?string $slug            = 'inventory-reports-links';
    protected string $view             = 'filament.pages.inventory-report-links';

    public static function getNavigationLabel(): string
    {
        return __('lang.inventory_reports');
    }

    public function getTitle(): string | Htmlable
    {
        return __('lang.inventory_reports');
    }

    public function getReportLinks(): array
    {
        $links = [
            // --- الروابط الأصلية ---
            [
                'title'       => __('lang.inventory_report'),
                'description' => __('lang.inventory_report_desc'),
                'icon'        => 'heroicon-o-building-storefront',
                'url'         => InventoryTransactionReportResource::getUrl(),
            ],
            [
                'title'       => __('lang.fifo_inventory'),
                'description' => __('lang.fifo_inventory_desc'),
                'icon'        => 'heroicon-o-archive-box',
                'url'         => FifoInventoryReportResource::getUrl(),
            ],

            // --- الروابط الجديدة ---
            [
                'title'       => __('lang.inventory_tracking'),
                'description' => __('lang.inventory_tracking_desc'),
                'icon'        => 'heroicon-o-magnifying-glass-circle',
                'url'         => InventoryTransactionTruckingReportResource::getUrl(),
            ],
            [
                'title'       => __('lang.inbound_outflows'),
                'description' => __('lang.inbound_outflows_desc'),
                'icon'        => 'heroicon-o-arrows-right-left',
                'url'         => InboundOutflowReportResource::getUrl(),
            ],
            [
                'title'       => __('lang.manufacturing_store_position'),
                'description' => __('lang.manufacturing_store_position_desc'),
                'icon'        => 'heroicon-o-currency-dollar',
                'url'         => InventoryWithUsageReportResource::getUrl(),
            ],
            [
                'title'       => __('lang.store_position_report'),
                'description' => __('lang.store_position_report_desc'),
                'icon'        => 'heroicon-o-archive-box',
                'url'         => InventoryTransactionPurchaseReportResource::getUrl(),
            ],
            [
                'title'       => __('lang.stock_adjustment_report'),
                'description' => __('lang.stock_adjustment_report_desc'),
                'icon'        => 'heroicon-o-adjustments-horizontal',
                'url'         => StockAdjustmentReportResource::getUrl(),
            ],
            [
                'title'       => __('lang.in_vs_out'),
                'description' => __('lang.in_vs_out_desc'),
                'icon'        => 'heroicon-o-arrows-up-down',
                'url'         => InVSReportResource::getUrl(),
            ],
            [
                'title'       => __('lang.stock_supply_orders'),
                'description' => __('lang.stock_supply_orders_desc'),
                'icon'        => 'heroicon-o-adjustments-horizontal',
                'url'         => StockSupplyOrderReportResource::getUrl(),
            ],
            [
                'title'       => __('lang.manufacturing_fifo_report'),
                'description' => __('lang.manufacturing_fifo_report_desc'),
                'icon'        => 'heroicon-o-magnifying-glass-circle',
                'url'         => ManufacturingInventoryReportResource::getUrl(),
            ],
            [
                'title'       => __('lang.stock_adjustment_summary'),
                'description' => __('lang.stock_adjustment_summary_desc'),
                'icon'        => 'heroicon-o-presentation-chart-bar',
                'url'         => StockAdjustmentSummaryReportResource::getUrl(),
            ],
            // [
            //     'title'       => MinimumProductQtyReportResource::getPluralLabel(),
            //     'description' => '',
            //     'icon'        => 'heroicon-o-presentation-chart-bar',
            //     'url'         => MinimumProductQtyReportResource::getUrl(),
            // ],
            //     [
            //         'title'       => 'Stock Cost Report',
            //         'description' => 'Analyze stock cost in a specific store.',
            //         'icon'        => 'heroicon-o-banknotes',
            //         'url'         => \App\Filament\Resources\StockCostReportResource::getUrl(),
            //     ],
        ];

        usort($links, function ($a, $b) {
            return strcmp($a['title'], $b['title']);
        });
        return $links;
    }
}
