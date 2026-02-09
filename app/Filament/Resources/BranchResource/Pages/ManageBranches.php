<?php

namespace App\Filament\Resources\BranchResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Schemas\Components\Tabs\Tab;
use App\Filament\Resources\BranchResource;
use App\Models\Branch;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ManageRecords;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ManageBranches extends ListRecords
{
    protected static string $resource = BranchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
    public function getTabs(): array
    {
        return [
            Branch::TYPE_BRANCH => Tab::make(__('Standard Branches'))
                ->modifyQueryUsing(fn(Builder $query) => $query->normal())
                ->icon('heroicon-o-building-storefront')
                ->badge(Branch::normal()->forBranchManager('id')->count())
                ->badgeColor('success'),

            // Branch::TYPE_CENTRAL_KITCHEN => Tab::make(__('Manufacturing Branches'))
            //     ->modifyQueryUsing(fn(Builder $query) => $query->centralKitchens())
            //     ->icon('heroicon-o-fire')
            //     ->badge(Branch::centralKitchens()->count())
            //     ->badgeColor('warning'),
            Branch::TYPE_POPUP => Tab::make(__('Popup Branches'))
                ->modifyQueryUsing(fn(Builder $query) => $query->popups())
                ->icon('heroicon-o-sparkles')
                ->badge(Branch::popups()->forBranchManager('id')->count())
                ->badgeColor('info'),
            // Branch::TYPE_RESELLER => Tab::make(__('Reseller Locations'))
            //     ->modifyQueryUsing(fn(Builder $query) => $query->where('type', Branch::TYPE_RESELLER))
            //     ->icon('heroicon-o-user-group')
            //     ->badge(Branch::where('type', Branch::TYPE_RESELLER)->count())
            //     ->badgeColor('gray'),

        ];
    }
}
