<?php

namespace App\Filament\Clusters\HRCluster\Resources\AllowanceResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Schemas\Components\Tabs\Tab;
use App\Filament\Clusters\HRCluster\Resources\AllowanceResource;
use App\Models\Allowance;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListAllowances extends ListRecords
{
    protected static string $resource = AllowanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
    public function getTabs(): array
    {
        return [
            // تطبق على الجميع = is_specific = 0
            'Apply to all allowances' => Tab::make()
                ->icon('heroicon-o-rectangle-stack')
                ->badge(Allowance::query()->where('is_specific', 0)->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_specific', 0)),

            // مخصّصة = is_specific = 1
            'Custom allowances' => Tab::make()
                ->icon('heroicon-o-rectangle-stack')
                ->badge(Allowance::query()->where('is_specific', 1)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_specific', 1)),
        ];
    }
}
