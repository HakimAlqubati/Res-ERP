<?php

namespace App\Filament\Clusters\HRCluster\Resources\AllowanceResource\Pages;

use App\Filament\Clusters\HRCluster\Resources\AllowanceResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListAllowances extends ListRecords
{
    protected static string $resource = AllowanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
    public function getTabs(): array
    {
        return [
            'Apply to all allownces' => Tab::make()
                ->icon('heroicon-o-rectangle-stack')
                ->badge(fn(Builder $query) => $query->where('is_specific', 1)->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('is_specific', 0)),
            'Custom allownces' => Tab::make()
                ->icon('heroicon-o-rectangle-stack')
                ->badge(fn(Builder $query) => $query->where('is_specific', 0)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('is_specific', 1)),
        ];
    }
}
