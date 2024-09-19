<?php

namespace App\Filament\Clusters\HRServiceRequestCluster\Resources\ServiceRequestResource\Pages;

use App\Filament\Clusters\HRServiceRequestCluster\Resources\ServiceRequestResource;
use App\Models\ServiceRequest;
use Filament\Resources\Pages\CreateRecord;

class CreateServiceRequest extends CreateRecord
{
    protected static string $resource = ServiceRequestResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = ServiceRequest::STATUS_NEW;
        $data['created_by'] = auth()->user()->id;
        return $data;
    }
}
