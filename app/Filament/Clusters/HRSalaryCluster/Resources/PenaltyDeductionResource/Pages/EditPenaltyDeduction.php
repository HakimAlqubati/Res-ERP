<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources\PenaltyDeductionResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Clusters\HRSalaryCluster\Resources\PenaltyDeductionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPenaltyDeduction extends EditRecord
{
    protected static string $resource = PenaltyDeductionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
