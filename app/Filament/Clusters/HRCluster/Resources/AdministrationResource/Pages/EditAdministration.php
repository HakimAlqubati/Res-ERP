<?php

namespace App\Filament\Clusters\HRCluster\Resources\AdministrationResource\Pages;

use App\Filament\Clusters\HRCluster\Resources\AdministrationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAdministration extends EditRecord
{
    protected static string $resource = AdministrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
