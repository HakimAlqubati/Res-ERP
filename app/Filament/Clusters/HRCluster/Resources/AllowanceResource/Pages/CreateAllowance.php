<?php

namespace App\Filament\Clusters\HRCluster\Resources\AllowanceResource\Pages;

use App\Filament\Clusters\HRCluster\Resources\AllowanceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAllowance extends CreateRecord
{
    protected static string $resource = AllowanceResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
