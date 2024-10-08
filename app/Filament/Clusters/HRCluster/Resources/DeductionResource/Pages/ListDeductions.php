<?php

namespace App\Filament\Clusters\HRCluster\Resources\DeductionResource\Pages;

use App\Filament\Clusters\HRCluster\Resources\DeductionResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListDeductions extends ListRecords
{
    protected static string $resource = DeductionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'General deductions' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('is_specific', 0)),
            'Specific employee deductions' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('is_specific', 1)),
            'Penalties deductions' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('is_penalty', 1)),
        ];
    }
}
