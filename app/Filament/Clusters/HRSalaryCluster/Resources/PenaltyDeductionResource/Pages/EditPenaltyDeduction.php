<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources\PenaltyDeductionResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Clusters\HRSalaryCluster\Resources\PenaltyDeductionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPenaltyDeduction extends EditRecord
{
    protected static string $resource = PenaltyDeductionResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        abort_if(
            in_array($this->record->status, [
                \App\Models\PenaltyDeduction::STATUS_APPROVED,
                \App\Models\PenaltyDeduction::STATUS_REJECTED,
            ]),
            403,
            'This record cannot be edited because it has already been approved or rejected.'
        );  
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
