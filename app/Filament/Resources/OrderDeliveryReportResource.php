<?php

namespace App\Filament\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\Resources\OrderDeliveryReportResource\Pages\DeliveryAndInvoicingReportPage;
use App\Filament\Resources\OrderDeliveryReportResource\Pages\SalesAndPaymentsReportPage;
use App\Filament\Clusters\ResellersCluster;
use App\Filament\Resources\OrderDeliveryReportResource\Pages;
use Filament\Resources\Resource;
use App\Models\Order;
use Filament\Navigation\NavigationItem; // <-- أضف هذا الاستيراد

class OrderDeliveryReportResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static ?string $slug = 'order-delivery-reports';

    // 👇 يمكن تعطيل هذه الأسطر لأننا سنعرفها في الدالة أدناه
    // protected static ?string $navigationIcon = 'heroicon-o-truck';
    // protected static ?string $navigationLabel = 'Delivery & Sales Reports';
    // protected static ?string $navigationGroup = 'Reports';

    // 👇 تضمين داخل نفس الـ Cluster
    protected static ?string $cluster = ResellersCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 2;


    public static function getPages(): array
    {
        return [
            // المسارات تبقى كما هي
            'index' => DeliveryAndInvoicingReportPage::route('/'),
            'sales-payments' => SalesAndPaymentsReportPage::route('/sales-payments'),
        ];
    }

    // 👇 أضف هذه الدالة الجديدة
    public static function getNavigationItems(): array
    {
        return [
            NavigationItem::make('Delivery & Invoicing')
                ->url(DeliveryAndInvoicingReportPage::getUrl())
                ->icon('heroicon-o-truck')
                ->group('Reports')->openUrlInNewTab()
                ->sort(1),

            NavigationItem::make('Sales & Payments')
                ->url(SalesAndPaymentsReportPage::getUrl())
                ->icon('heroicon-o-currency-dollar')
                ->group('Reports')
                ->openUrlInNewTab()
                ->sort(2),
        ];
    }
}
