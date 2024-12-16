<?php

namespace App\Filament\Clusters\HRCluster\Resources\AdministrationResource\Pages;

use App\Filament\Clusters\HRCluster\Resources\AdministrationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAdministration extends CreateRecord
{
    protected static string $resource = AdministrationResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
