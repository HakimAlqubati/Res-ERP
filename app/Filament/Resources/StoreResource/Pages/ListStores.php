<?php

namespace App\Filament\Resources\StoreResource\Pages;

use App\Filament\Resources\StoreResource;
use App\Models\Store;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListStores extends ListRecords
{
    protected static string $resource = StoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            // 'all' => Tab::make(__('All Stores'))
            //     ->icon('heroicon-o-rectangle-stack')
            //     ->badge(Store::count())
            //     ->badgeColor('gray'),

            'active' => Tab::make(__('Active Stores'))
                ->modifyQueryUsing(fn(Builder $query) => $query->where('active', true))
                ->icon('heroicon-o-check-circle')
                ->badge(Store::where('active', true)->count())
                ->badgeColor('success'),

            'inactive' => Tab::make(__('Inactive Stores'))
                ->modifyQueryUsing(fn(Builder $query) => $query->where('active', false))
                ->icon('heroicon-o-x-circle')
                ->badge(Store::where('active', false)->count())
                ->badgeColor('danger'),
        ];
    }
}
