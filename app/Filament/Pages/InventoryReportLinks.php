<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

// --- استيراد الـ Resources ---
// تأكد من أن هذه المسارات صحيحة ومطابقة لمشروعك

// من الطلب الأصلي
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionReportResource;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\FifoInventoryReportResource;

// من الـ Resources الجديدة التي أرسلتها
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionTruckingReportResource;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InboundOutflowReportResource;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryWithUsageReportResource;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\InventoryTransactionPurchaseReportResource;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockAdjustmentReportResource;
use App\Filament\Resources\InVSReportResource;
use App\Filament\Resources\StockSupplyOrderReportResource;


class InventoryReportLinks extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    protected static ?string $navigationLabel = 'Inventory Reports';
    protected static ?string $slug = 'inventory-reports-links';
    protected static string $view = 'filament.pages.inventory-report-links';

    public function getTitle(): string | Htmlable
    {
        return '';
        return __('Inventory Reports');
    }

    public function getReportLinks(): array
    {
        return [
            // --- الروابط الأصلية ---
            [
                'title' => 'Inventory Report',
                'description' => 'View current stock levels.',
                'icon' => 'heroicon-o-building-storefront',
                'url' => InventoryTransactionReportResource::getUrl(),
            ],
            [
                'title' => 'Fifo Inventory',
                'description' => 'Fifo inventory report.',
                'icon' => 'heroicon-o-archive-box',
                'url' => FifoInventoryReportResource::getUrl(),
            ],

            // --- الروابط الجديدة ---
            [
                'title' => 'Inventory Tracking',
                'description' => 'Track product movement and history.',
                'icon' => 'heroicon-o-magnifying-glass-circle',
                'url' => InventoryTransactionTruckingReportResource::getUrl(),
            ],
            [
                'title' => 'Inbound → Outflows',
                'description' => 'Analyze inbound vs. outbound stock flows.',
                'icon' => 'heroicon-o-arrows-right-left',
                'url' => InboundOutflowReportResource::getUrl(),
            ],
            [
                'title' => 'Manufacturing Store Position',
                'description' => 'Report on stock position with usage data.',
                'icon' => 'heroicon-o-chart-bar-square',
                'url' => InventoryWithUsageReportResource::getUrl(),
            ],
            [
                'title' => 'Store Position Report',
                'description' => 'Detailed report on store stock positions.',
                'icon' => 'heroicon-o-archive-box',
                'url' => InventoryTransactionPurchaseReportResource::getUrl(),
            ],
            [
                'title' => 'Stock Adjustment Report',
                'description' => 'View and analyze stock adjustments.',
                'icon' => 'heroicon-o-adjustments-horizontal',
                'url' => StockAdjustmentReportResource::getUrl(),
            ],
            [
                'title' => 'In VS Out',
                'description' => 'Compare incoming vs. outgoing stock.',
                'icon' => 'heroicon-o-arrows-up-down',
                'url' => InVSReportResource::getUrl(),
            ],
            [
                'title' => 'Stock Supply Orders',
                'description' => 'Report on stock supply orders.',
                'icon' => 'heroicon-o-adjustments-horizontal',
                'url' => StockSupplyOrderReportResource::getUrl(),
            ],
        ];
    }
}