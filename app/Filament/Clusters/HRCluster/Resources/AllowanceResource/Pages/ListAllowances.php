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
            'General allowances' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('is_specific', 0)),
            'Specific employee allowances' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('is_specific', 1)),
        ];
    }
}
