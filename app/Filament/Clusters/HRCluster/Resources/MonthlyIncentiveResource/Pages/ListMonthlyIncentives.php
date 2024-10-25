<?php

namespace App\Filament\Clusters\HRCluster\Resources\MonthlyIncentiveResource\Pages;

use App\Filament\Clusters\HRCluster\Resources\MonthlyIncentiveResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMonthlyIncentives extends ListRecords
{
    protected static string $resource = MonthlyIncentiveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    
}
