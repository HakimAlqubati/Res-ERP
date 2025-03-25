<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    public function getTitle(): string
    {
        return __('lang.orders');
    }
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getTableRecordsPerPageSelectOptions(): array
    {
        return [10, 25, 30, 50];
    }


    /**
     * Define tabs for all delivery statuses.
     */
    public function getTabs(): array
    {
        $statuses = Order::getStatusLabels();

        // "All Orders" tab
        $tabs = [
            'all' => Tab::make(__('All Orders'))
                ->modifyQueryUsing(fn(Builder $query) => $query) // No filtering
                ->icon('heroicon-o-circle-stack')
                ->badge(Order::query()->count())
                ->badgeColor('gray'),
        ];

        // Add status-based tabs
        foreach ($statuses as $status => $label) {
            $tabs[$status] = Tab::make($label)
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', $status))
                ->icon(Order::getStatusIcon($status))
                ->badge(Order::query()->where('status', $status)->count())
                ->badgeColor(Order::getBadgeColor($status));
        }

        return $tabs;
    }
}
