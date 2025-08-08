<?php
namespace App\Filament\Resources;

use App\Filament\Clusters\ResellersCluster;
use App\Filament\Resources\Base\ReturnedOrderResource as BaseReturnedOrderResource;
use App\Models\Branch;
use App\Models\Order;
use Filament\Facades\Filament;
use Filament\Pages\SubNavigationPosition;
use Illuminate\Database\Eloquent\Builder;

class ResellerReturnedOrderResource extends BaseReturnedOrderResource
{
    protected static ?string $slug                                = 'resellers-returned-orders';
    protected static ?string $cluster                             = ResellersCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort                         = 2;
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
    protected static function getOrderSearchQuery(string $search)
    {
        return Order::where('id', 'like', "%{$search}%")
            ->whereIn('status', [Order::READY_FOR_DELEVIRY, Order::DELEVIRED])
            ->whereHas('branch', fn($q) => $q->where('type', Branch::TYPE_RESELLER))
            ->limit(5)
            ->pluck('id', 'id');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = static::getModel()::query()->whereHas('order.branch', fn($q) => $q->reseller())
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