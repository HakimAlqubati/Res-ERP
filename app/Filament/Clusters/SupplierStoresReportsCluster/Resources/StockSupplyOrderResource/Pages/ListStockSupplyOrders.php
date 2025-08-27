<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockSupplyOrderResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Schemas\Components\Tabs\Tab;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockSupplyOrderResource;
use App\Models\StockSupplyOrder;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListStockSupplyOrders extends ListRecords
{
    protected static string $resource = StockSupplyOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
    public function getTabs(): array
    {
        return [
            'Active' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('cancelled', 0))
                ->icon('heroicon-o-check-circle')
                ->badge(StockSupplyOrder::query()->where('cancelled', 0)->count())
                ->badgeColor('success'),
            'Cancelled' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('cancelled', 1))
                ->icon('heroicon-o-x-circle')
                ->badge(StockSupplyOrder::query()->where('cancelled', 1)->count())
                ->badgeColor('danger'),

        ];
    }
}