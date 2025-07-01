<?php

namespace App\Filament\Resources\BranchResellerResource\Pages;

use App\Filament\Resources\BranchResellerResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBranchReseller extends CreateRecord
{
    protected static string $resource = BranchResellerResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = \App\Models\Branch::TYPE_RESELLER;
        return $data;
    }
}