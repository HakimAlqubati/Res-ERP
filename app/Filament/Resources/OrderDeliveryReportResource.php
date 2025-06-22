<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\ResellersCluster;
use App\Filament\Resources\OrderDeliveryReportResource\Pages;
use Filament\Resources\Resource;
use App\Models\Order;
use Filament\Pages\SubNavigationPosition;

class OrderDeliveryReportResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    // 👇 تضمين داخل نفس الـ Cluster
    protected static ?string $cluster = ResellersCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'تقرير التسليم والفوترة';
    protected static ?string $navigationGroup = 'Reports';

    public static function getPages(): array
    {
        return [
            'index' => Pages\OrderDeliveryReportPage::route('/'),
        ];
    }
}
