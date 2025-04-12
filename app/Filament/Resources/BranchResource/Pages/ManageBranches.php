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
            Branch::TYPE_BRANCH => Tab::make(__('Normal Branches'))
                ->modifyQueryUsing(fn(Builder $query) => $query->normal())
                ->icon('heroicon-o-building-storefront')
                ->badge(Branch::normal()->count())
                ->badgeColor('success'),

            Branch::TYPE_CENTRAL_KITCHEN => Tab::make(__('Manufacturing Branches'))
                ->modifyQueryUsing(fn(Builder $query) => $query->centralKitchens())
                ->icon('heroicon-o-fire')
                ->badge(Branch::centralKitchens()->count())
                ->badgeColor('warning'),
            Branch::TYPE_POPUP => Tab::make(__('Popup Branches'))
                ->modifyQueryUsing(fn(Builder $query) => $query->popups())
                ->icon('heroicon-o-sparkles')
                ->badge(Branch::popups()->count())
                ->badgeColor('info'),

        ];
    }
}
