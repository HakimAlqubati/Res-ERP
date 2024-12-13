<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources\PenaltyDeductionResource\Pages;

use App\Filament\Clusters\HRSalaryCluster\Resources\PenaltyDeductionResource;
use App\Models\Employee;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePenaltyDeduction extends CreateRecord
{
    protected static string $resource = PenaltyDeductionResource::class;
    protected ?bool $hasDatabaseTransactions = true;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->user()->id;
        return $data;
    }


    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
