<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources\PenaltyDeductionResource\Pages;

use App\Filament\Clusters\HRSalaryCluster\Resources\PenaltyDeductionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPenaltyDeductions extends ListRecords
{
    protected static string $resource = PenaltyDeductionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
