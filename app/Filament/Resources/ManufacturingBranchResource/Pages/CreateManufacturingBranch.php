<?php

namespace App\Filament\Resources\ManufacturingBranchResource\Pages;

use App\Models\Branch;
use App\Filament\Resources\ManufacturingBranchResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateManufacturingBranch extends CreateRecord
{
    protected static string $resource = ManufacturingBranchResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = Branch::TYPE_RESELLER;
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}