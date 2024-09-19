<?php

namespace App\Filament\Clusters\HRServiceRequestCluster\Resources\ServiceRequestResource\Pages;

use App\Filament\Clusters\HRServiceRequestCluster\Resources\ServiceRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServiceRequest extends EditRecord
{
    protected static string $resource = ServiceRequestResource::class;

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
