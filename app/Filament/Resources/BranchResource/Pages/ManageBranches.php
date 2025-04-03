<?php

namespace App\Filament\Resources\BranchResource\Pages;

use App\Filament\Resources\BranchResource;
use App\Models\Branch;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ManageRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ManageBranches extends ManageRecords
{
    protected static string $resource = BranchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
    public function getTabs(): array
    {
        return [
            // 'all' => Tab::make(__('All Branches'))
            //     ->modifyQueryUsing(fn(Builder $query) => $query)
            //     ->icon('heroicon-o-building-office-2')
            //     ->badge(Branch::query()->count())
            //     ->badgeColor('gray'),

            Branch::TYPE_BRANCH => Tab::make(__('Branches'))
                ->modifyQueryUsing(fn(Builder $query) => $query->branches())
                ->icon('heroicon-o-building-storefront')
                ->badge(Branch::branches()->count())
                ->badgeColor('success')
            // ->url(fn() => url()->current() . '?activeTab=' . Branch::TYPE_BRANCH)
            ,

            Branch::TYPE_CENTRAL_KITCHEN => Tab::make(__('Central Kitchens'))
                ->modifyQueryUsing(fn(Builder $query) => $query->centralKitchens())
                ->icon('heroicon-o-fire')
                ->badge(Branch::centralKitchens()->count())
                ->badgeColor('warning')
            // ->url(fn() => url()->current() . '?activeTab=' . Branch::TYPE_CENTRAL_KITCHEN)
            ,

            Branch::TYPE_HQ => Tab::make(__('Head Office'))
                ->modifyQueryUsing(fn(Builder $query) => $query->HQBranches())
                ->icon('heroicon-o-building-library')
                ->badge(Branch::HQBranches()->count())
                ->badgeColor('blue')
            // ->url(fn() => url()->current() . '?activeTab=' . Branch::TYPE_HQ)
            ,
        ];
    }
}
