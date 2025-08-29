<?php
namespace App\Filament\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\Clusters\MainOrdersCluster;
use App\Filament\Resources\Base\BaseReturnedOrderResource ;
use App\Models\Branch;
use App\Models\Order;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;

class ReturnedOrderResource extends BaseReturnedOrderResource
{

    protected static ?string $cluster                             = MainOrdersCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort                         = 2;
    
    protected static function getOrderSearchQuery(string $search)
    {
        return Order::where('id', 'like', "%{$search}%")
            ->whereIn('status', [Order::READY_FOR_DELEVIRY, Order::DELEVIRED])
            ->whereHas('branch', fn($q) => $q->where('type', '!=', Branch::TYPE_RESELLER))
            ->limit(5)
            ->pluck('id', 'id');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = static::getModel()::query()->whereHas('order.branch', fn($q) => $q->notReseller())
;

        if (
            static::isScopedToTenant() &&
            ($tenant = Filament::getTenant())
        ) {
            static::scopeEloquentQueryToTenant($query, $tenant);
        }

        return $query;
    }
}